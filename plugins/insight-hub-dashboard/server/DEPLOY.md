# Deploy WebSocket relay on Cloudways

The WebSocket server is **optional**. WordPress and the public site work without it (messages use polling). It does **not** fix PHP 502 errors by itself — those are fixed by the optimized plugin PHP files.

## 1. Upload files (do NOT upload `node_modules`)

Upload the entire `server/` folder except `node_modules`:

```
wp-content/plugins/insight-hub-dashboard/server/
  server.js
  package.json
  package-lock.json
  ecosystem.config.cjs
  install-cloudways.sh
  nginx-ws.conf
  README.md
```

## 2. SSH into Cloudways

```bash
cd ~/applications/*/public_html/wp-content/plugins/insight-hub-dashboard/server
chmod +x install-cloudways.sh
./install-cloudways.sh
```

This runs `npm install` and starts PM2 if available.

Manual start (no PM2):

```bash
HOST=127.0.0.1 PORT=8080 node server.js
```

## 3. Nginx proxy (required for browser `wss://`)

Ask Cloudways support to add `nginx-ws.conf` to your application vhost, **or** paste the `location /ws/` block from `nginx-ws.conf`.

## 4. WordPress setting

In **Insight Hub → Settings**, set WebSocket URL to:

```
wss://wordpress-1613719-6351471.cloudwaysapps.com/ws/
```

Or via WP-CLI:

```bash
wp option update ih_ws_url 'wss://wordpress-1613719-6351471.cloudwaysapps.com/ws/'
```

Leave **blank** if the Node process is not running — the site still works.

## 5. Fix 502 (PHP) — upload these too

```
wp-content/plugins/insight-hub-dashboard/insight-hub-dashboard.php
wp-content/plugins/insight-hub-dashboard/pages/dashboard.php
wp-content/plugins/insight-hub-dashboard/pages/layout.php
wp-content/plugins/insight-hub-dashboard/pages/requests.php
wp-content/plugins/insight-hub-dashboard/pages/messages.php
wp-content/themes/Injection-Moulding/functions.php
```

Then: **Restart PHP-FPM** and **purge Varnish + Breeze cache**.

## Verify

```bash
pm2 logs insight-hub-ws --lines 20
curl -I https://your-domain.com/
```

Messages admin page should show instant delivery when WS is connected; otherwise polling every 3s.
