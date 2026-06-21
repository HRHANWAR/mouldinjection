# Insight Hub — WebSocket relay

Optional realtime layer for the messages console. The site works without it
(3-second polling is the fallback); when running, it adds instant message
delivery, typing indicators, read receipts and online presence.

## Quick deploy (Cloudways)

See **[DEPLOY.md](./DEPLOY.md)** for full SSH + nginx + WordPress steps.

```bash
cd wp-content/plugins/insight-hub-dashboard/server
chmod +x install-cloudways.sh && ./install-cloudways.sh
```

## Install & run (local)

```bash
cd wp-content/plugins/insight-hub-dashboard/server
npm install
npm start            # listens on ws://127.0.0.1:8080
```

Custom port/host:

```bash
PORT=8080 HOST=127.0.0.1 node server.js
```

Keep it alive with pm2:

```bash
pm2 start server.js --name insight-hub-ws
pm2 save
```

## Production (Cloudways / nginx)

Keep the server bound to 127.0.0.1 and proxy a public `wss://` path to it:

```nginx
location /ws/ {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_read_timeout 86400;
}
```

Then set the WordPress option so the browser knows where to connect:

```bash
wp option update ih_ws_url 'wss://your-domain.com/ws/'
```

(or via PHP: `update_option( 'ih_ws_url', 'wss://your-domain.com/ws/' );`)

If `ih_ws_url` is empty, the messages console only tries
`ws://localhost:8080` during local development and otherwise relies on
polling — no console errors on production.

## Protocol

Client → server: `register {user_id}`, `chat {receiver_id, text, id}`,
`typing|stop_typing {receiver_id}`, `read_receipt {user_id, message_ids[]}`

Server → client: `chat`, `typing`, `stop_typing`, `read_receipt`,
`status_update {user_id, is_online, last_seen}`
