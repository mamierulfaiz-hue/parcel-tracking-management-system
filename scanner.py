import cv2
import requests
import time
import numpy as np  # <--- This was missing!
from pyzbar.pyzbar import decode

# ==========================================
# CONFIGURATION
# ==========================================
# Make sure this matches your Laravel URL
SERVER_URL = "http://127.0.0.1:8000/api/scan-parcel"

cap = cv2.VideoCapture(0)
cap.set(3, 640) # Width
cap.set(4, 480) # Height

print("🎥 CAMERA STARTED")
print("👉 Point at a QR Code to scan...")

def send_to_server(tracking_number):
    try:
        # Send the data to Laravel
        payload = {'tracking_number': tracking_number}
        response = requests.post(SERVER_URL, data=payload)
        
        if response.status_code == 200:
            data = response.json()
            print(f"✅ SUCCESS: {data['message']} (Student: {data['student_id']})")
            return True
        else:
            print(f"❌ ERROR: Parcel not found in database or already collected.")
            return False
    except Exception as e:
        print(f"⚠️ NETWORK ERROR: Is Laravel running? {e}")
        return False

last_scan_time = 0

while True:
    success, img = cap.read()
    
    if not success:
        continue

    # Decode QR Codes
    for barcode in decode(img):
        myData = barcode.data.decode('utf-8')
        
        # Draw a box around the QR code
        pts = np.array([barcode.polygon], np.int32)
        pts = pts.reshape((-1, 1, 2))
        
        # Only scan once every 3 seconds (to avoid spamming)
        if time.time() - last_scan_time > 3:
            print(f"🔎 Scanned: {myData}")
            
            # Send to Database
            if send_to_server(myData):
                # Green Box for Success
                cv2.polylines(img, [pts], True, (0, 255, 0), 5) 
                cv2.putText(img, "COLLECTED", (barcode.rect[0], barcode.rect[1] - 10), 
                           cv2.FONT_HERSHEY_SIMPLEX, 0.9, (0, 255, 0), 2)
            else:
                # Red Box for Error
                cv2.polylines(img, [pts], True, (0, 0, 255), 5)
                cv2.putText(img, "ERROR", (barcode.rect[0], barcode.rect[1] - 10), 
                           cv2.FONT_HERSHEY_SIMPLEX, 0.9, (0, 0, 255), 2)

            last_scan_time = time.time()
        else:
            # Just draw the box (Blue) if waiting
            cv2.polylines(img, [pts], True, (255, 0, 0), 3)

    cv2.imshow("Result", img)
    
    # Press 'q' to quit
    if cv2.waitKey(1) & 0xFF == ord('q'):
        break

cap.release()
cv2.destroyAllWindows()