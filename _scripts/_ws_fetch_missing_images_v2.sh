#!/bin/bash
# Try to download 10 missing images from production winstartek.com
# into the local uploads/ folder
set -e
UPLOADS="/c/Users/alroy/Local Sites/winstartekcom/app/public/wp-content/uploads"
HOST="https://winstartek.com"

# list of (relative_path)
PATHS=(
  "/wp-content/uploads/2022/12/辦公室照明標準規則.jpg"
  "/wp-content/uploads/2022/12/辦公室照明燈具怎麼選.jpg"
  "/wp-content/uploads/2022/12/色溫與工作集中力.jpg"
  "/wp-content/uploads/2022/12/平板燈優點有哪些？.jpg"
  "/wp-content/uploads/2022/12/工業照明設計三大重點，你也能輕鬆上手工業用照明燈設計.jpg"
  "/wp-content/uploads/2025/04/60cm平板燈-會議室場景.jpg"
  "/wp-content/uploads/2025/04/60cm平板燈-會議室場景-明亮.jpg"
  "/wp-content/uploads/2024/03/2023-Radiant Star型錄cover.jpg"
  "/wp-content/uploads/2024/03/banner2-scaled.jpg"
  "/wp-content/uploads/2024/02/banner2.jpg"
)

ok=0; fail=0
for p in "${PATHS[@]}"; do
  local_path="$UPLOADS${p#/wp-content/uploads}"
  url="$HOST$p"
  # mkdir parent
  mkdir -p "$(dirname "$local_path")"
  # url-encode the spaces and chinese for curl
  encoded_url=$(printf '%s' "$url" | python -c "import sys, urllib.parse; print(urllib.parse.quote(sys.stdin.read(), safe=':/'))" 2>/dev/null || echo "$url")
  # try fetch
  http_code=$(curl -sSL -o "$local_path.tmp" -w "%{http_code}" "$encoded_url" 2>&1 || echo "000")
  size=$(stat -c%s "$local_path.tmp" 2>/dev/null || echo 0)
  if [ "$http_code" = "200" ] && [ "$size" -gt 1000 ]; then
    mv "$local_path.tmp" "$local_path"
    echo "OK    [$size bytes]  $p"
    ok=$((ok+1))
  else
    rm -f "$local_path.tmp"
    echo "FAIL  [http=$http_code size=$size]  $p"
    fail=$((fail+1))
  fi
done

echo ""
echo "Summary: ok=$ok fail=$fail"
