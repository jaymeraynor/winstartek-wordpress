#!/usr/bin/env bash
# ============================================================
# Winstartek — Migration Export Script
# Creates a .wpress backup ready to restore to production
# ============================================================
set -euo pipefail

WP_CONTAINER="winstartek_wordpress"
EXPORT_DIR="./exports"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
EXPORT_NAME="winstartek-seo-optimized-${TIMESTAMP}"

GREEN='\033[0;32m'; NC='\033[0m'
info() { echo -e "${GREEN}[INFO]${NC} $*"; }

mkdir -p "$EXPORT_DIR"

info "Exporting optimized WordPress site..."

# Create backup via WP-CLI
docker exec "$WP_CONTAINER" wp ai1wm backup --exclude-spam --allow-root

# Copy the latest backup out of the container
LATEST=$(docker exec "$WP_CONTAINER" bash -c \
  "ls -t /var/www/html/wp-content/ai1wm-backups/*.wpress 2>/dev/null | head -1")

[ -z "$LATEST" ] && { echo "No backup found. Run migration import first."; exit 1; }

OUTFILE="${EXPORT_DIR}/${EXPORT_NAME}.wpress"
docker cp "${WP_CONTAINER}:${LATEST}" "$OUTFILE"

info "=============================================="
info " Export saved to: $OUTFILE"
info "=============================================="
info " To restore to winstartek.com:"
info "  1. Go to wp-admin > All-in-One WP Migration > Restore"
info "  2. Upload: ${EXPORT_NAME}.wpress"
info "  3. Confirm restore"
info "=============================================="
