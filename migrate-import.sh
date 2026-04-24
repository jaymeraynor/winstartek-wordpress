#!/usr/bin/env bash
# ============================================================
# Winstartek — Migration Import Script
# Usage: ./migrate-import.sh <path-to-backup.wpress>
# ============================================================
set -euo pipefail

BACKUP_FILE="${1:-}"
WP_CONTAINER="winstartek_wordpress"
WP_PORT="${WP_PORT:-8080}"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'

info()    { echo -e "${GREEN}[INFO]${NC} $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC} $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*"; exit 1; }

# ── Step 0: Check args ────────────────────────────────────
if [ -z "$BACKUP_FILE" ]; then
  echo ""
  echo "  Usage: ./migrate-import.sh <backup.wpress>"
  echo ""
  echo "  Example:"
  echo "    ./migrate-import.sh winstartek-backup.wpress"
  echo ""
  exit 1
fi

[ -f "$BACKUP_FILE" ] || error "File not found: $BACKUP_FILE"

# ── Step 1: Start containers ──────────────────────────────
info "Starting Docker containers..."
docker compose up -d
info "Waiting for WordPress to be ready..."
sleep 15

# ── Step 2: Install WP-CLI inside container ───────────────
info "Installing WP-CLI..."
docker exec "$WP_CONTAINER" bash -c '
  if ! command -v wp &>/dev/null; then
    curl -sO https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
    chmod +x wp-cli.phar
    mv wp-cli.phar /usr/local/bin/wp
  fi
'

# ── Step 3: Wait for WordPress install ────────────────────
info "Checking WordPress installation status..."
MAX=30; COUNT=0
until docker exec "$WP_CONTAINER" wp core is-installed --allow-root 2>/dev/null; do
  COUNT=$((COUNT+1))
  [ $COUNT -ge $MAX ] && error "WordPress not installed after ${MAX} checks. Please complete the setup wizard at http://localhost:${WP_PORT} first."
  info "Waiting for WordPress... ($COUNT/$MAX)"
  sleep 5
done
info "WordPress is installed."

# ── Step 4: Install All-in-One WP Migration ───────────────
info "Installing All-in-One WP Migration plugin..."
docker exec "$WP_CONTAINER" wp plugin install all-in-one-wp-migration --activate --allow-root

# Install the file extension add-on (for large files)
info "Installing file extension plugin..."
docker exec "$WP_CONTAINER" wp plugin install all-in-one-wp-migration-file-extension --activate --allow-root 2>/dev/null || true

# ── Step 5: Increase upload size limit ────────────────────
info "Setting upload size limit..."
docker exec "$WP_CONTAINER" bash -c "
  grep -qxF 'define(\"AI1WM_MAX_FILE_SIZE\", 536870912);' /var/www/html/wp-config.php || \
  sed -i \"/\/\* That's all, stop editing/i define('AI1WM_MAX_FILE_SIZE', 536870912); // 512MB\" /var/www/html/wp-config.php
"

# ── Step 6: Copy backup into container ────────────────────
info "Copying backup file into container..."
FILENAME=$(basename "$BACKUP_FILE")
docker cp "$BACKUP_FILE" "$WP_CONTAINER:/var/www/html/wp-content/ai1wm-backups/${FILENAME}"

# Set correct permissions
docker exec "$WP_CONTAINER" chown -R www-data:www-data /var/www/html/wp-content/ai1wm-backups/

# ── Step 7: Import via WP-CLI ─────────────────────────────
info "Starting import (this may take several minutes)..."
docker exec "$WP_CONTAINER" wp ai1wm restore "${FILENAME}" --allow-root

info ""
info "=============================================="
info " Import complete!"
info " WordPress : http://localhost:${WP_PORT}"
info " Admin     : http://localhost:${WP_PORT}/wp-admin"
info "=============================================="
info " Login with your original winstartek.com credentials."
info "=============================================="
