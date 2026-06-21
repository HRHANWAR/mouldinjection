/**
 * Insight Hub — realtime WebSocket relay
 *
 * Relays chat events between connected clients (admin console + users):
 *   client -> server : register, chat, typing, stop_typing, read_receipt
 *   server -> client : chat, typing, stop_typing, read_receipt, status_update
 *
 * Run:    node server.js            (defaults: 127.0.0.1:8080)
 * Env:    PORT=8080 HOST=127.0.0.1 node server.js
 *
 * In production keep it bound to 127.0.0.1 and proxy wss:// through nginx
 * (see README.md). The WordPress option `ih_ws_url` must point at the
 * public endpoint, e.g. wss://example.com/ws/
 */
const WebSocket = require('ws');

const PORT = parseInt(process.env.PORT || '8080', 10);
const HOST = process.env.HOST || '127.0.0.1';

const wss = new WebSocket.Server({ port: PORT, host: HOST });

/** user_id -> Set<WebSocket> (a user can have multiple tabs open) */
const clients = new Map();

function log(...args) {
  console.log(new Date().toISOString(), ...args);
}

function addClient(userId, ws) {
  if (!clients.has(userId)) clients.set(userId, new Set());
  clients.get(userId).add(ws);
}

function removeClient(ws) {
  if (!ws.userId || !clients.has(ws.userId)) return;
  const set = clients.get(ws.userId);
  set.delete(ws);
  if (set.size === 0) clients.delete(ws.userId);
}

function sendTo(userId, payload) {
  const set = clients.get(userId);
  if (!set) return;
  const data = JSON.stringify(payload);
  for (const ws of set) {
    if (ws.readyState === WebSocket.OPEN) ws.send(data);
  }
}

function broadcast(payload, exceptUserId) {
  const data = JSON.stringify(payload);
  for (const [uid, set] of clients) {
    if (uid === exceptUserId) continue;
    for (const ws of set) {
      if (ws.readyState === WebSocket.OPEN) ws.send(data);
    }
  }
}

/* heartbeat — terminate dead connections every 30s */
function heartbeat() { this.isAlive = true; }
const interval = setInterval(() => {
  wss.clients.forEach((ws) => {
    if (ws.isAlive === false) return ws.terminate();
    ws.isAlive = false;
    ws.ping();
  });
}, 30000);

wss.on('connection', (ws) => {
  ws.isAlive = true;
  ws.on('pong', heartbeat);

  ws.on('message', (raw) => {
    let msg;
    try { msg = JSON.parse(raw); } catch (e) { return; }
    if (!msg || typeof msg.type !== 'string') return;

    switch (msg.type) {
      case 'register': {
        const uid = parseInt(msg.user_id, 10);
        if (!uid) return;
        ws.userId = uid;
        addClient(uid, ws);
        log('registered user', uid, '(connections:', wss.clients.size + ')');
        broadcast({ type: 'status_update', user_id: uid, is_online: true, last_seen: '' }, uid);
        break;
      }

      case 'chat': {
        // relay a chat message to its receiver (persistence happens via AJAX/PHP)
        const to = parseInt(msg.receiver_id, 10);
        if (!to) return;
        sendTo(to, {
          type: 'chat',
          id: msg.id || 0,
          sender_id: ws.userId || msg.sender_id || 0,
          text: String(msg.text || ''),
        });
        break;
      }

      case 'typing':
      case 'stop_typing': {
        const to = parseInt(msg.receiver_id, 10);
        if (!to) return;
        sendTo(to, { type: msg.type, sender_id: ws.userId || msg.sender_id || 0 });
        break;
      }

      case 'read_receipt': {
        // admin marked messages from msg.user_id as read -> notify that user
        const to = parseInt(msg.user_id, 10);
        if (!to || !Array.isArray(msg.message_ids)) return;
        sendTo(to, { type: 'read_receipt', message_ids: msg.message_ids.map(Number) });
        break;
      }
    }
  });

  ws.on('close', () => {
    const uid = ws.userId;
    removeClient(ws);
    if (uid && !clients.has(uid)) {
      broadcast({
        type: 'status_update',
        user_id: uid,
        is_online: false,
        last_seen: new Date().toISOString(),
      });
      log('user offline', uid);
    }
  });

  ws.on('error', () => { /* close handler does the cleanup */ });
});

wss.on('close', () => clearInterval(interval));

log(`Insight Hub WS relay listening on ws://${HOST}:${PORT}`);
