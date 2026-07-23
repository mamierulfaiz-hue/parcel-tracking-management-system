# Python Usage in This Project

This project includes a Python scanner client used to capture parcel tracking barcodes and student phone numbers from a camera source. The Python code runs separately from the Laravel PHP app and communicates in real time through WebSockets.

## Where the Python code lives

- `py_script/parcel_scanner.py`

## Purpose

The Python script acts as a scanner sender client that:

- connects to the WebSocket server at `ws://<host>:8080`
- identifies itself as a `sender`
- waits for the dashboard to request a scan
- captures either a barcode or printed phone number from the camera
- sends scanned values back to the dashboard in JSON messages

## How it works

### WebSocket client

The script includes a minimal WebSocket client implementation:

- performs handshake using raw sockets
- sends and receives WebSocket frames
- supports text messages, ping/pong, and close frames

The client sends these messages to the server:

- `{ type: "identify", role: "sender" }`
- `{ type: "tracking_number", tracking_number: "..." }`
- `{ type: "student_phone", student_phone: "..." }`

It also responds to dashboard commands:

- `trigger-add` → start the barcode scan flow
- `trigger-phone-scan` → start the phone OCR scan flow
- `cancel-scan` → stop the active scan session

### Scanner workflow

The script uses OpenCV and pyzbar (barcode scanner) to read the parcel tracking code from the camera.

Once the tracking code is scanned successfully, it sends:

- `tracking_number`

Then the web dashboard will request phone scanning. The script uses Tesseract OCR to read text from the camera frame, extracts a Malaysian phone number, and sends:

- `student_phone`

### Scan state handling

The `Scanner` class manages state so the script knows when to scan tracking vs phone data.

- `trigger_tracking` and `trigger_phone` are controlled by incoming WebSocket commands
- `scanned_tracking` and `scanned_phone` store the scanned result
- scanning stops automatically after a timeout

## Dependencies

The script requires Python packages such as:

- `opencv-python`
- `pytesseract`
- `pyzbar`

It also uses the system camera and Tesseract OCR.

## How it fits into the project

This Python code is intended to run on a scanner device (such as a Raspberry Pi with a camera). It connects to the same WebSocket server used by the Laravel dashboard and provides live scan inputs.

The Laravel dashboard opens `ws://<host>:8080` and sends `trigger-add` / `trigger-phone-scan` commands. The Python sender receives those commands and sends scanned values back to the dashboard.

## Running the scanner script

To run the scanner manually:

```bash
python py_script/parcel_scanner.py
```

Make sure the scanner device can reach the WebSocket server and has the required camera and OCR dependencies installed.

## Notes

- The Python code is separate from the PHP application logic.
- It does not directly modify the database; it only sends scanned data to the dashboard.
- The dashboard then uses that scanned data to create parcels in Laravel.
