<!DOCTYPE html>
<html>
<head>
    <title>Scanner Simulator</title>
    <style>
        body { font-family: sans-serif; padding: 50px; text-align: center; background-color: #333; color: white; }
        input { padding: 15px; font-size: 20px; width: 300px; text-align: center; }
        button { padding: 15px 30px; font-size: 20px; background-color: #28a745; color: white; border: none; cursor: pointer; }
        #result { margin-top: 20px; font-size: 24px; font-weight: bold; }
    </style>
</head>
<body>

    <h1>🤖 Raspberry Pi Simulator</h1>
    <p>Enter a Tracking Number to simulate a camera scan:</p>

    <input type="text" id="tracking_number" placeholder="Scan QR Code...">
    <button onclick="scanParcel()">SCAN</button>

    <div id="result">Waiting for scan...</div>

    <script>
        async function scanParcel() {
            let tracking = document.getElementById('tracking_number').value;
            let resultBox = document.getElementById('result');

            resultBox.innerHTML = "Scanning...";
            resultBox.style.color = "yellow";

            try {
                // Send data to your new API
                let response = await fetch('/api/scan-parcel', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ tracking_number: tracking })
                });

                let data = await response.json();

                if (data.status === 'success') {
                    resultBox.innerHTML = "✅ " + data.message;
                    resultBox.style.color = "#0f0"; // Green
                } else {
                    resultBox.innerHTML = "❌ " + data.message;
                    resultBox.style.color = "#f00"; // Red
                }

            } catch (error) {
                resultBox.innerHTML = "❌ Error connecting to server";
            }
        }
    </script>

</body>
</html>