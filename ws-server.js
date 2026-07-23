import { WebSocketServer } from 'ws';

const port = process.env.WS_PORT || 8080;
const wss = new WebSocketServer({ port });

console.log(`WebSocket server started on ws://0.0.0.0:${port}`);

const sendJson = (ws, payload) => {
  if (ws.readyState === ws.OPEN) {
    ws.send(JSON.stringify(payload));
  }
};

const broadcast = (payload, filterFn = () => true) => {
  wss.clients.forEach(client => {
    if (client.readyState === client.OPEN && filterFn(client)) {
      client.send(JSON.stringify(payload));
    }
  });
};

wss.on('connection', ws => {
  ws.role = 'dashboard';

  ws.on('message', raw => {
    let data;
    try {
      data = JSON.parse(raw.toString());
    } catch (error) {
      return sendJson(ws, { type: 'error', message: 'Invalid JSON' });
    }

    if (data.type === 'identify') {
      ws.role = data.role === 'sender' ? 'sender' : 'dashboard';
      return sendJson(ws, { type: 'identified', role: ws.role });
    }

    if (ws.role === 'dashboard') {
      if (data.type === 'trigger-add' || data.type === 'trigger-collect') {
        const outgoingType = data.type === 'trigger-collect' ? 'trigger-add' : data.type;
        broadcast({ type: outgoingType }, client => client.role === 'sender');
        return;
      }
      if (data.type === 'trigger-phone-scan') {
        broadcast({ type: 'trigger-phone-scan' }, client => client.role === 'sender');
        return;
      }
      if (data.type === 'cancel-scan') {
        broadcast({ type: 'cancel-scan' }, client => client.role === 'sender');
        return;
      }
    }

    if (ws.role === 'sender') {
      if (data.type === 'tracking_number' || data.type === 'student_phone') {
        broadcast(data, client => client.role === 'dashboard');
        return;
      }
    }

    sendJson(ws, { type: 'error', message: 'Unsupported message type' });
  });

  ws.on('close', () => {
    // nothing special yet
  });
});
