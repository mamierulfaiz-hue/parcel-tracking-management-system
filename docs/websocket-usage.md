# WebSocket Usage in This Project

This project uses a separate WebSocket service to connect the dashboard with the scanner sender device. The WebSocket connection is used for real-time scanning workflow events.

## Architecture

- `ws-server.js` runs a Node.js WebSocket server.
- The browser dashboard connects to this server as a `dashboard` client.
- The scanner sender (a separate hardware or sender client) connects as a `sender` client.
- Messages are forwarded between the dashboard and the sender using roles.

## WebSocket Server (`ws-server.js`)

- Runs on port `8080` by default.
- Uses the `ws` library to accept WebSocket connections.
- Each connection is assigned a `role`:
  - `dashboard` by default
  - `sender` when the client sends `{ type: 'identify', role: 'sender' }`
- Supports these message flows:
  - `dashboard` → `sender`
    - `{ type: 'trigger-add' }` to begin a new parcel scan
    - `{ type: 'trigger-phone-scan' }` to start phone number scanning
    - `{ type: 'cancel-scan' }` to cancel the current scan
  - `sender` → `dashboard`
    - `{ type: 'tracking_number', tracking_number: '...' }`
    - `{ type: 'student_phone', student_phone: '...' }`

## Dashboard Integration

In `resources/views/dashboard.blade.php` the browser opens a WebSocket connection:

- `new WebSocket(`ws://${window.location.hostname}:8080`)`
- After opening, the dashboard identifies itself:
  - `scannerSocket.send(JSON.stringify({ type: 'identify', role: 'dashboard' }));`

The dashboard listens for messages from the WebSocket server:

- `tracking_number` updates the tracking field and prompts the UI to scan the phone number.
- `student_phone` updates the phone field and completes the scan flow.

The dashboard can also send these commands to the server:

- `trigger-add` starts a new parcel tracking scan session.
- `trigger-phone-scan` tells the sender to scan the student phone.
- `cancel-scan` cancels an ongoing scan.

## Docker Integration

The WebSocket server is defined as the `ws` service in `docker-compose.yml`.

- Uses the `node:20-alpine` image.
- Mounts the project root into `/app`.
- Publishes port `8080` on the host.
- Runs:
  - `npm install ws`
  - `node /app/ws-server.js`

The `ws` service depends on the `app` service, ensuring the Laravel app starts first.

## How It Works Together

1. The dashboard page connects to `ws://localhost:8080`.
2. The scanner `sender` device connects to the same WebSocket server and identifies as `sender`.
3. The dashboard sends a `trigger-add` event to the server.
4. The server forwards the event to the `sender`.
5. The sender scans the tracking number and student phone, then sends those values back.
6. The dashboard receives the scanned values in real time and updates the form.

## Running Locally

Start the full stack with Docker Compose:

```bash
docker compose up --build
```

Then open the dashboard at:

- `http://localhost:6767`

The WebSocket server will be accessible at:

- `ws://localhost:8080`

## Summary

This project uses WebSockets for live scanner coordination rather than polling. The WebSocket server acts as a broker between the dashboard and the scanner sender client, allowing scan events to flow immediately between the two.
