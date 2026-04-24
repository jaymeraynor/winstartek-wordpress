#!/usr/bin/env bash
# Winstartek WordPress Mobile — Quick Setup Script
set -euo pipefail

# 1. Copy env file if it doesn't exist
if [ ! -f .env ]; then
  cp .env.example .env
  echo "[OK] .env created — please update passwords before production use."
fi

# 2. Start containers
docker compose up -d

echo ""
echo "=============================================="
echo " Winstartek WordPress Mobile — Ready!"
echo "=============================================="
echo " WordPress  : http://localhost:${WP_PORT:-8080}"
echo " phpMyAdmin : http://localhost:${PMA_PORT:-8081}"
echo "=============================================="
echo " First-time setup:"
echo "   1. Open http://localhost:${WP_PORT:-8080}"
echo "   2. Complete the WordPress install wizard"
echo "   3. Go to Appearance > Themes"
echo "   4. Activate 'Winstartek Mobile'"
echo "=============================================="
