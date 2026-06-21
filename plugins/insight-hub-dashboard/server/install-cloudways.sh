#!/usr/bin/env bash
# Insight Hub WebSocket — Cloudways / Linux install script
# Run via SSH from the server folder:
#   cd ~/applications/<app_id>/public_html/wp-content/plugins/insight-hub-dashboard/server
#   chmod +x install-cloudways.sh && ./install-cloudways.sh

set -euo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$DIR"

echo "==> Installing Node dependencies..."
if ! command -v npm >/dev/null 2>&1; then
  echo "ERROR: npm not found. Enable Node.js on Cloudways or install Node 18+."
  exit 1
fi

npm install --omit=dev

echo "==> Dependencies installed."

if command -v pm2 >/dev/null 2>&1; then
  echo "==> Starting with PM2..."
  pm2 start ecosystem.config.cjs
  pm2 save
  pm2 status insight-hub-ws
  echo "OK: WebSocket relay running. Set ih_ws_url in WordPress (see DEPLOY.md)."
else
  echo "PM2 not found. Start manually:"
  echo "  HOST=127.0.0.1 PORT=8080 node server.js"
  echo "Or install PM2: npm install -g pm2"
fi
