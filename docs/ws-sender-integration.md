# WebSocket Sender Integration

This project now uses WebSocket for scanner coordination instead of polling the API.

## What changed

- `resources/views/dashboard.blade.php`
  - uses a WebSocket connection to `ws://<dashboard-host>:8080`
  - sends dashboard commands to the sender
  - receives scanned values directly over websocket
- `ws-server.js`
  - relays commands between the dashboard and sender(s)
- `docker-compose.yml`
  - added `ws` service on port `8080`

## Sender changes

### 1. Connect to the websocket server

```js
const socket = new WebSocket('ws://localhost:8080');

socket.addEventListener('open', () => {
  socket.send(JSON.stringify({ type: 'identify', role: 'sender' }));
});
```

### 2. Handle incoming dashboard commands

```js
socket.addEventListener('message', event => {
  const data = JSON.parse(event.data);

  if (data.type === 'trigger-add') {
    // Start tracking number scan
    startTrackingScan();
  }

  if (data.type === 'trigger-phone-scan') {
    // Start phone number scan
    startPhoneScan();
  }
});
```

### 3. Send scan results back

When the tracking number is scanned:

```js
socket.send(JSON.stringify({
  type: 'tracking_number',
  tracking_number: 'TRACK12345'
}));
```

When the student phone number is scanned:

```js
socket.send(JSON.stringify({
  type: 'student_phone',
  student_phone: '0123456789'
}));
```

### 4. Optional: handle reconnect

If the socket closes, reconnect and re-identify:

```js
socket.addEventListener('close', () => {
  setTimeout(() => {
    connectWebSocket();
  }, 1000);
});
```

## Notes

- Dashboard commands are only sent when the `Add Parcel` button is pressed.
- The sender must be running and connected to the websocket server for scans to be delivered.
- The websocket server is exposed on port `8080` in Docker Compose.
