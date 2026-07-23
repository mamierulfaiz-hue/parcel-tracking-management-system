import base64
import hashlib
import json
import os
import re
import socket
import struct
import sys
import threading
import time
import cv2
import pytesseract
from urllib.parse import urlparse
from pyzbar.pyzbar import decode

WS_URL = "ws://100.86.104.80:8080"
CAMERA_TIMEOUT = 60  # seconds to wait for a scan before resetting
RECONNECT_DELAY = 2


def find_camera():
    for i in range(5):
        cap = cv2.VideoCapture(i, cv2.CAP_V4L2)
        if cap.isOpened():
            return cap
        cap.release()
    return None


class WebSocketClient:
    def __init__(self, url, on_message):
        self.url = url
        self.on_message = on_message
        self.sock = None
        self.running = threading.Event()
        self._recv_thread = None

    def connect(self):
        parsed = urlparse(self.url)
        scheme = parsed.scheme
        if scheme not in ("ws", "wss"):
            raise ValueError(f"Unsupported websocket scheme: {scheme}")

        host = parsed.hostname
        port = parsed.port or (443 if scheme == "wss" else 80)
        path = parsed.path or "/"
        if parsed.query:
            path += f"?{parsed.query}"

        sock = socket.create_connection((host, port), timeout=5)
        if scheme == "wss":
            import ssl

            sock = ssl.wrap_socket(sock)

        sock.settimeout(None)

        key = base64.b64encode(os.urandom(16)).decode("ascii")
        request = (
            f"GET {path} HTTP/1.1\r\n"
            f"Host: {host}:{port}\r\n"
            "Upgrade: websocket\r\n"
            "Connection: Upgrade\r\n"
            f"Sec-WebSocket-Key: {key}\r\n"
            "Sec-WebSocket-Version: 13\r\n"
            "\r\n"
        )
        sock.sendall(request.encode("ascii"))

        response = self._recv_http_response(sock)
        lines = response.split("\r\n")
        if not lines or not lines[0].startswith("HTTP/1.1 101"):
            raise ConnectionError(f"WebSocket handshake failed: {lines[0] if lines else response}")

        accept_header = next((line.split(":", 1)[1].strip() for line in lines if line.lower().startswith("sec-websocket-accept:")), None)
        expected = base64.b64encode(hashlib.sha1((key + "258EAFA5-E914-47DA-95CA-C5AB0DC85B11").encode("ascii")).digest()).decode("ascii")
        if accept_header != expected:
            raise ConnectionError("WebSocket accept header mismatch")

        self.sock = sock
        self.running.set()
        self._recv_thread = threading.Thread(target=self._receive_loop, daemon=True)
        self._recv_thread.start()

    def _recv_http_response(self, sock):
        buffer = b""
        while b"\r\n\r\n" not in buffer:
            chunk = sock.recv(4096)
            if not chunk:
                break
            buffer += chunk
        return buffer.decode("ascii", errors="replace")

    def send_json(self, data):
        self.send_text(json.dumps(data))

    def send_text(self, text):
        if not self.sock:
            raise ConnectionError("WebSocket is not connected")

        payload = text.encode("utf-8")
        header = bytearray([0x81])
        length = len(payload)

        if length <= 125:
            header.append(0x80 | length)
        elif length <= 0xFFFF:
            header.append(0x80 | 126)
            header.extend(struct.pack(">H", length))
        else:
            header.append(0x80 | 127)
            header.extend(struct.pack(">Q", length))

        mask = os.urandom(4)
        header.extend(mask)
        masked_payload = bytes(b ^ mask[i % 4] for i, b in enumerate(payload))
        self.sock.sendall(header + masked_payload)

    def _receive_loop(self):
        try:
            while self.running.is_set():
                frame = self._read_frame()
                if frame is None:
                    break

                opcode, payload = frame
                if opcode == 0x1:
                    try:
                        self.on_message(payload.decode("utf-8", errors="replace"))
                    except Exception as exc:
                        print(f"Failed to handle message: {exc}")
                elif opcode == 0x8:
                    self.close()
                    break
                elif opcode == 0x9:
                    self._send_pong(payload)
                elif opcode == 0xA:
                    continue
        except Exception as exc:
            print(f"WebSocket receive error: {exc}")
        finally:
            self.running.clear()
            self.close()

    def _read_frame(self):
        header = self._recv_exact(2)
        if not header:
            return None

        first, second = header[0], header[1]
        opcode = first & 0x0F
        masked = bool(second & 0x80)
        length = second & 0x7F

        if length == 126:
            length = struct.unpack(">H", self._recv_exact(2))[0]
        elif length == 127:
            length = struct.unpack(">Q", self._recv_exact(8))[0]

        mask_key = self._recv_exact(4) if masked else None
        payload = self._recv_exact(length) if length else b""
        if masked and mask_key:
            payload = bytes(b ^ mask_key[i % 4] for i, b in enumerate(payload))

        return opcode, payload

    def _recv_exact(self, count):
        data = b""
        while len(data) < count:
            chunk = self.sock.recv(count - len(data))
            if not chunk:
                return None
            data += chunk
        return data

    def _send_pong(self, payload):
        if not self.sock:
            return
        header = bytearray([0x8A])
        length = len(payload)
        if length <= 125:
            header.append(0x80 | length)
        elif length <= 0xFFFF:
            header.append(0x80 | 126)
            header.extend(struct.pack(">H", length))
        else:
            header.append(0x80 | 127)
            header.extend(struct.pack(">Q", length))
        mask = os.urandom(4)
        header.extend(mask)
        masked_payload = bytes(b ^ mask[i % 4] for i, b in enumerate(payload))
        self.sock.sendall(header + masked_payload)

    def close(self):
        if self.sock:
            try:
                self.running.clear()
                self.sock.close()
            except Exception:
                pass
            finally:
                self.sock = None


class Scanner:
    def __init__(self):
        self.trigger_tracking = False
        self.trigger_phone = False
        self.scanned_tracking = None
        self.scanned_phone = None
        self.scan_started_at = None
        self.lock = threading.Lock()
        self.ws = None
        self.cap = None

    def set_websocket(self, ws):
        self.ws = ws

    def reset_scan_state(self):
        with self.lock:
            self.trigger_tracking = False
            self.trigger_phone = False
            self.scanned_tracking = None
            self.scanned_phone = None
            self.scan_started_at = None
        print("Scanner state reset")

    def on_message(self, raw_message):
        try:
            message = json.loads(raw_message)
        except json.JSONDecodeError:
            print(f"Invalid websocket message: {raw_message}")
            return

        message_type = message.get("type")
        if message_type == "identified":
            print(f"WebSocket identified as {message.get('role')}")
            return
        if message_type in ["trigger-add", "trigger-collect"]:
            self.reset_scan_state()
            with self.lock:
                self.trigger_tracking = True
                self.scan_started_at = time.monotonic()
            print(f"Received {message_type}: starting tracking scan")
        elif message_type == "trigger-phone-scan":
            with self.lock:
                self.trigger_phone = True
                if self.scan_started_at is None:
                    self.scan_started_at = time.monotonic()
            print("Received trigger-phone-scan: starting phone scan")
        elif message_type == "cancel-scan":
            self.reset_scan_state()
            print("Received cancel-scan: stopping current scan")
            return
        else:
            print(f"Unknown websocket command: {message_type}")

    def scan_loop(self):
        if not self.cap:
            self.cap = find_camera()
            if not self.cap:
                print("No camera found. Retrying...")
                return

        print(f"Scanner loop active; timeout is {CAMERA_TIMEOUT} seconds.")
        while self.ws and self.ws.running.is_set():
            if self.has_timed_out():
                print("Scan timed out. Resetting scan state.")
                self.reset_scan_state()
                continue

            if not self.should_scan():
                time.sleep(0.1)
                continue

            ret, frame = self.cap.read()
            if not ret:
                continue

            if self.should_scan_tracking():
                barcodes = decode(frame)
                if barcodes:
                    tracking_value = barcodes[0].data.decode("utf-8")
                    with self.lock:
                        self.scanned_tracking = tracking_value
                        self.trigger_tracking = False
                    print(f"Code Found: {tracking_value}")
                    self.ws.send_json({"type": "tracking_number", "tracking_number": tracking_value})
                    return

            if self.should_scan_phone():
                # Pre-processing for better OCR
                gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
                
                # Apply adaptive thresholding to make text pop
                thresh = cv2.adaptiveThreshold(gray, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 11, 2)
                
                # Optional: Invert if text is white on black (Tesseract likes black on white)
                # We'll just try standard first
                
                text = pytesseract.image_to_string(
                    thresh,
                    config='--oem 3 --psm 6' # PSM 6 is better for blocks of text
                )
                
                phone_value = None
                # Clean the text: remove everything except digits and plus
                cleaned_text = re.sub(r"[^0-9+]", "", text)
                
                # Search for Malaysian phone formats in the cleaned blob
                # 601... or 01...
                phone_match = re.search(r"(601\d{7,9}|01\d{8,9})", cleaned_text)
                
                if phone_match:
                    raw = phone_match.group(0)
                    phone_value = "6" + raw if raw.startswith("01") else raw
                    
                    with self.lock:
                        self.scanned_phone = phone_value
                        self.trigger_phone = False
                    print(f"Phone Found: {phone_value}")
                    self.ws.send_json({"type": "student_phone", "student_phone": phone_value})
                    return

            time.sleep(0.05)

        if self.should_scan():
            print("WebSocket disconnected while scanning. Resetting scan state.")
            with self.lock:
                self.trigger_tracking = False
                self.trigger_phone = False
                self.scanned_tracking = None
                self.scanned_phone = None
                self.scan_started_at = None

    def should_scan(self):
        with self.lock:
            return self.trigger_tracking or self.trigger_phone

    def should_scan_tracking(self):
        with self.lock:
            return self.trigger_tracking and self.scanned_tracking is None

    def should_scan_phone(self):
        with self.lock:
            return self.trigger_phone and self.scanned_tracking is not None and self.scanned_phone is None

    def has_timed_out(self):
        if CAMERA_TIMEOUT is None:
            return False

        with self.lock:
            if self.scan_started_at is None:
                return False
            return (time.monotonic() - self.scan_started_at) >= CAMERA_TIMEOUT

    def close(self):
        if self.cap:
            try:
                self.cap.release()
            except Exception:
                pass
            self.cap = None


def main():
    print("--- Raspberry Pi Dashcam Scanner (WebSocket Mode) ---")
    scanner = Scanner()

    while True:
        ws = WebSocketClient(WS_URL, scanner.on_message)
        scanner.set_websocket(ws)

        try:
            ws.connect()
            print(f"Connected to websocket {WS_URL}")
            ws.send_json({"type": "identify", "role": "sender"})

            while ws.running.is_set():
                scanner.scan_loop()
                time.sleep(0.1)
        except KeyboardInterrupt:
            print("Keyboard interrupt received, exiting.")
            break
        except Exception as exc:
            print(f"WebSocket error: {exc}")
            time.sleep(RECONNECT_DELAY)
        finally:
            ws.close()
            scanner.close()
            time.sleep(RECONNECT_DELAY)

    sys.exit(0)


if __name__ == "__main__":
    main()
