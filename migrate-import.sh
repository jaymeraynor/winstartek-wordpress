#!/usr/bin/env bash
# ============================================================
# Winstartek — Migration Import Script（支援 WPvivid 備份）
# Usage: ./migrate-import.sh <path-to-backup.zip>
# ============================================================
set -euo pipefail

BACKUP_FILE="${1:-}"
WP_CONTAINER="winstartek_wordpress"
WP_PORT="${WP_PORT:-8080}"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'

info()  { echo -e "${GREEN}[INFO]${NC} $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $*"; }
error() { echo -e "${RED}[ERROR]${NC} $*"; exit 1; }

# ── Step 0: 檢查參數 ─────────────────────────────────────
if [ -z "$BACKUP_FILE" ]; then
  echo ""
  echo "  用法: ./migrate-import.sh <備份檔案路徑>"
  echo ""
  echo "  範例:"
  echo "    ./migrate-import.sh ~/Downloads/wpvivid-backup.zip"
  echo "    ./migrate-import.sh backups/winstartek-20250101.zip"
  echo ""
  exit 1
fi

[ -f "$BACKUP_FILE" ] || error "找不到檔案: $BACKUP_FILE"

FILENAME=$(basename "$BACKUP_FILE")
EXT="${FILENAME##*.}"

info "備份檔案: $FILENAME"

# ── Step 1: 啟動 Docker 容器 ─────────────────────────────
info "啟動 Docker 容器..."
docker compose up -d
info "等待 WordPress 啟動..."
sleep 15

# ── Step 2: 安裝 WP-CLI ──────────────────────────────────
info "安裝 WP-CLI..."
docker exec "$WP_CONTAINER" bash -c '
  if ! command -v wp &>/dev/null; then
    curl -sO https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
    chmod +x wp-cli.phar
    mv wp-cli.phar /usr/local/bin/wp
  fi
'

# ── Step 3: 等待 WordPress 初始化完成 ────────────────────
info "確認 WordPress 安裝狀態..."
MAX=30; COUNT=0
until docker exec "$WP_CONTAINER" wp core is-installed --allow-root 2>/dev/null; do
  COUNT=$((COUNT+1))
  [ $COUNT -ge $MAX ] && error "WordPress 尚未安裝。請先到 http://localhost:${WP_PORT} 完成安裝精靈。"
  info "等待 WordPress... ($COUNT/$MAX)"
  sleep 5
done
info "WordPress 已就緒。"

# ── Step 4: 安裝 WPvivid 外掛 ────────────────────────────
info "安裝 WPvivid Backup 外掛..."
docker exec "$WP_CONTAINER" wp plugin install wpvivid-backuprestore --activate --allow-root

# ── Step 5: 複製備份檔到容器 ─────────────────────────────
WPVIVID_DIR="/var/www/html/wp-content/uploads/wpvivid"
info "建立 WPvivid 上傳目錄..."
docker exec "$WP_CONTAINER" bash -c "mkdir -p ${WPVIVID_DIR} && chown -R www-data:www-data ${WPVIVID_DIR}"

info "複製備份檔進容器..."
docker cp "$BACKUP_FILE" "$WP_CONTAINER:${WPVIVID_DIR}/${FILENAME}"
docker exec "$WP_CONTAINER" chown www-data:www-data "${WPVIVID_DIR}/${FILENAME}"

# ── Step 6: 執行還原 ─────────────────────────────────────
info "開始還原（可能需要數分鐘，請勿關閉）..."

# 先嘗試用 WP-CLI 方式還原
if docker exec "$WP_CONTAINER" wp wpvivid restore "${FILENAME}" --allow-root 2>/dev/null; then
  RESTORED=true
else
  RESTORED=false
fi

if [ "$RESTORED" = false ]; then
  warn "WP-CLI 還原方式無法使用，請改用網頁後台手動匯入。"
  info "已將備份檔複製至容器內，請依以下步驟操作："
  echo ""
  echo "  1. 開啟 http://localhost:${WP_PORT}/wp-admin"
  echo "  2. 左側選單 → WPvivid Backup"
  echo "  3. 點選「Upload & Restore」分頁"
  echo "  4. 點「Upload Backup Files」→ 選擇已放在 uploads/wpvivid/ 的備份"
  echo "     （或直接點「Restore」，系統會偵測到已上傳的備份）"
  echo "  5. 點「Restore」→ 確認 → 等待完成"
  echo ""
else
  info ""
  info "=============================================="
  info " 還原完成！"
  info " WordPress  : http://localhost:${WP_PORT}"
  info " 後台       : http://localhost:${WP_PORT}/wp-admin"
  info "=============================================="
  info " 使用 winstartek.com 原有帳號密碼登入。"
  info "=============================================="
fi
