#!/usr/bin/env bash
# ============================================================
# Winstartek — Migration Export Script（WPvivid 版）
# SEO 優化完成後，建立備份以還原回 winstartek.com
# ============================================================
set -euo pipefail

WP_CONTAINER="winstartek_wordpress"
EXPORT_DIR="./exports"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
EXPORT_NAME="winstartek-seo-optimized-${TIMESTAMP}"

GREEN='\033[0;32m'; NC='\033[0m'
info() { echo -e "${GREEN}[INFO]${NC} $*"; }

mkdir -p "$EXPORT_DIR"

info "建立 SEO 優化後的備份..."

# 用 WPvivid WP-CLI 建立備份
docker exec "$WP_CONTAINER" wp wpvivid backup --allow-root 2>/dev/null || true

# 取得最新備份檔（WPvivid 備份為 .zip）
LATEST=$(docker exec "$WP_CONTAINER" bash -c \
  "ls -t /var/www/html/wp-content/uploads/wpvivid/*.zip 2>/dev/null | head -1")

if [ -z "$LATEST" ]; then
  info "找不到 WPvivid 備份，嘗試從後台手動備份..."
  echo ""
  echo "  請手動操作："
  echo "  1. 開啟 http://localhost:${WP_PORT:-8080}/wp-admin"
  echo "  2. 左側 → WPvivid Backup → 點「Backup」"
  echo "  3. 等待完成後，再執行一次此腳本"
  echo ""
  exit 1
fi

OUTFILE="${EXPORT_DIR}/${EXPORT_NAME}.zip"
docker cp "${WP_CONTAINER}:${LATEST}" "$OUTFILE"

info "=============================================="
info " 備份已儲存至: $OUTFILE"
info "=============================================="
info " 還原回 winstartek.com 步驟："
info "  1. 登入 winstartek.com/wp-admin"
info "  2. WPvivid Backup → Upload & Restore"
info "  3. 上傳 ${EXPORT_NAME}.zip"
info "  4. 點「Restore」→ 確認"
info "=============================================="
