<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Parcel System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <script src='https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js'></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <style>
        body { background-color: #f4f6f9; color: #333; }
        .card-box { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .card-box:hover { transform: translateY(-3px); }
        .main-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border: 1px solid #eee; }
        .bg-gradient-primary { background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%); color: white; }
        .bg-gradient-success { background: linear-gradient(135deg, #198754 0%, #0f5132 100%); color: white; }
        .bg-gradient-warning { background: linear-gradient(135deg, #ffc107 0%, #d39e00 100%); color: #333; }
        .search-box { border-radius: 20px; border: 1px solid #ddd; padding: 8px 15px; background: white; width: 300px; }
        .search-box input { border: none; outline: none; width: 100%; }
        .student-link { cursor: pointer; color: #0d6efd; text-decoration: none; font-weight: bold; transition: 0.2s; }
        .student-link:hover { color: #0a58ca; text-decoration: underline; }
        
        /* Clean Scanner UI */
        .scanner-viewport { position: relative; height: 300px; background: #000; border-radius: 10px; overflow: hidden; margin-bottom: 15px; }
        video { width: 100%; height: 100%; object-fit: cover; }
        .scan-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 4px solid rgba(0, 255, 0, 0.3); pointer-events: none; z-index: 10; }
        .scan-line { position: absolute; width: 100%; height: 2px; background: #0f0; top: 50%; box-shadow: 0 0 4px #0f0; opacity: 0.7; }
        
        @keyframes flash-green { 0% { background-color: #d1e7dd; } 100% { background-color: white; } }
        .flash-success { animation: flash-green 1s; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
        <a class="navbar-brand fw-bold" href="#"><i class="bi bi-shield-lock-fill"></i> Admin Dashboard</a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <form action="/logout" method="POST" class="d-inline m-0">
                @csrf
                <button class="btn btn-danger btn-sm rounded-pill px-3">Logout</button>
            </form>
        </div>
    </nav>

    <div class="container mt-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h3>Overview</h3>
            <div class="d-flex gap-2">
                <div class="d-flex align-items-center search-box shadow-sm">
                    <i class="bi bi-search me-2 text-muted"></i>
                    <input type="text" id="searchInput" placeholder="Search ID, Tracking, Shelf..." onkeyup="searchTable()">
                </div>
                
                <button class="btn btn-outline-dark rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#studentDBModal">
                    <i class="fa-solid fa-users"></i> Student DB
                </button>

                <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addParcelModal">
                    <i class="bi bi-plus-lg"></i> Add Parcel
                </button>

                <button class="btn btn-success rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#scanCollectionModal">
                    <i class="bi bi-qr-code-scan"></i> Scan to Collect
                </button>
            </div>
        </div>

        <div class="row mb-4 g-3">
            <div class="col-md-4"><div class="card card-box bg-gradient-primary p-4 h-100"><div class="d-flex justify-content-between align-items-center"><div><h6 class="opacity-75 mb-1">In Storage</h6><h2 class="fw-bold mb-0">{{ $totalInStorage ?? 0 }}</h2></div><i class="fa-solid fa-boxes-stacked fa-2x opacity-50"></i></div></div></div>
            <div class="col-md-4"><div class="card card-box bg-gradient-success p-4 h-100"><div class="d-flex justify-content-between align-items-center"><div><h6 class="opacity-75 mb-1">Empty Shelves</h6><h2 class="fw-bold mb-0">{{ $emptyShelves ?? 0 }}</h2></div><i class="fa-solid fa-clipboard-check fa-2x opacity-50"></i></div></div></div>
            <div class="col-md-4"><div class="card card-box bg-gradient-warning p-4 h-100"><div class="d-flex justify-content-between align-items-center"><div><h6 class="opacity-75 mb-1 text-dark">Unpaid Parcels</h6><h2 class="fw-bold mb-0 text-dark">{{ $totalUnpaid ?? 0 }}</h2></div><i class="fa-solid fa-triangle-exclamation fa-2x opacity-50 text-dark"></i></div></div></div>
        </div>

        @if($errors->any()) <div class="alert alert-danger rounded-3 border-0 shadow-sm">{{ $errors->first() }}</div> @endif
        @if(session('success')) <div class="alert alert-success rounded-3 border-0 shadow-sm"><i class="fa-solid fa-check-circle me-2"></i> {{ session('success') }}</div> @endif

        <div class="main-card">
            <h5 class="mb-3 fw-bold border-bottom pb-2">Recent Parcels</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="adminTable">
                    <thead class="table-light">
                        <tr>
                            <th>Unique ID</th>
                            <th>Student ID</th>
                            <th>Shelf Location</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($parcels->sortByDesc('created_at') as $parcel)
                        @php $student = $students->firstWhere('student_id', $parcel->student_id); @endphp
                        <tr>
                            <td class="fw-bold" style="letter-spacing: 1px;">
                                <a href="#" class="text-decoration-none text-dark" 
                                   data-bs-toggle="modal" 
                                   data-bs-target="#viewParcelModal"
                                   data-id="{{ $parcel->unique_id }}"
                                   data-tracking="{{ $parcel->tracking_number }}"
                                   data-time="{{ $parcel->created_at->format('d M Y, h:i A') }}"
                                   data-collected-time="{{ $parcel->is_collected ? $parcel->updated_at->format('d M Y, h:i A') : '' }}"
                                   data-student="{{ $student ? $student->name : 'Unknown' }}"
                                   data-shelf="{{ $parcel->shelf_label }}">
                                    {{ $parcel->unique_id }} 
                                    <i class="bi bi-info-circle-fill small text-primary opacity-50 ms-1" style="font-size: 10px;"></i>
                                </a>
                                <div class="text-muted small fw-normal" style="font-size: 10px;">{{ $parcel->tracking_number }}</div>
                            </td>
                            
                            <td>
                                @if($student)
                                    <a href="#" class="student-link" data-bs-toggle="modal" data-bs-target="#viewStudentModal"
                                       data-name="{{ $student->name }}" data-id="{{ $student->student_id }}"
                                       data-phone="{{ $student->phone }}" data-room="{{ $student->room_number }}">
                                       {{ $parcel->student_id }}
                                    </a>
                                @else
                                    <span class="text-danger">{{ $parcel->student_id }} (Not Found)</span>
                                @endif
                            </td>
                            
                            <td>
                                @if($parcel->is_collected)
                                    <span class="text-muted text-decoration-line-through">{{ $parcel->shelf_label }}</span>
                                @else
                                    <span class="badge bg-dark fs-6">{{ $parcel->shelf_label }}</span>
                                @endif
                            </td>
                            
                            <td>
                                @if($parcel->is_collected)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill">
                                        <i class="bi bi-check-circle-fill me-1"></i> Delivered
                                    </span>
                                @elseif($parcel->is_paid)
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-1 rounded-pill">
                                        <i class="bi bi-box-seam-fill me-1"></i> Ready for Pickup
                                    </span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning px-2 py-1 rounded-pill">
                                        <i class="bi bi-hourglass-split me-1"></i> Pending Payment
                                    </span>
                                @endif
                            </td>
                            
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editParcelModal" 
                                            data-id="{{ $parcel->id }}" 
                                            data-tracking="{{ $parcel->tracking_number }}" 
                                            data-shelf="{{ $parcel->shelf_label }}"
                                            data-status="{{ $parcel->is_collected ? 'collected' : ($parcel->is_paid ? 'ready' : 'unpaid') }}"
                                            title="Edit Parcel">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <form action="/admin/delete-parcel/{{ $parcel->id }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this parcel?');" class="m-0">
                                        @csrf 
                                        @method('DELETE') 
                                        <button class="btn btn-sm btn-outline-danger" title="Delete Parcel">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewParcelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 bg-light">
                    <h6 class="modal-title fw-bold"><i class="bi bi-box-seam"></i> Parcel Details</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <h1 class="display-3 fw-bold text-primary mb-0" id="detailId">...</h1>
                    <p class="text-muted small text-uppercase fw-bold mb-4">Unique ID</p>
                    
                    <div class="card bg-light border-0 p-3 text-start">
                        <div id="collectedTimeSection" class="mb-2 d-none">
                            <small class="text-muted d-block" style="font-size: 10px;">COLLECTED AT</small>
                            <span class="fw-bold text-success fs-5" id="detailCollectedTime">...</span>
                            <hr class="my-2">
                        </div>

                        <div class="mb-2">
                            <small class="text-muted d-block" style="font-size: 10px;">RECEIVED AT</small>
                            <span class="fw-bold text-dark fs-5" id="detailTime">...</span>
                        </div>
                        <hr class="my-2">
                        <div class="mb-2">
                            <small class="text-muted d-block" style="font-size: 10px;">STUDENT NAME</small>
                            <span class="fw-bold text-dark" id="detailStudent">...</span>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted d-block" style="font-size: 10px;">TRACKING NUMBER</small>
                            <span class="fw-bold text-dark text-break" id="detailTracking">...</span>
                        </div>
                        <div>
                            <small class="text-muted d-block" style="font-size: 10px;">SHELF LOCATION</small>
                            <span class="badge bg-dark fs-6" id="detailShelf">...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addParcelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-magic"></i> Add Parcel (Smart Scan)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-2"><span class="badge bg-dark" id="scanStatusBadge">📷 Camera Idle</span></div>
                    
                    <div class="scanner-viewport">
                        <video id="ocrVideo" autoplay playsinline class="d-none"></video>
                        <div id="reader-add-parcel" class="d-none" style="width: 100%; height: 100%;"></div>
                        <div class="scan-overlay"></div><div class="scan-line"></div>
                    </div>

                    <div id="ocrLoading" class="text-center text-primary mb-2 d-none"><div class="spinner-border spinner-border-sm" role="status"></div> <small class="fw-bold">Reading Text... Hold Steady!</small></div>
                    
                    <form action="/add-parcel" method="POST" id="addParcelForm">
                        @csrf
                        <input type="hidden" id="scanPhone" name="student_phone" required>
                        <div id="studentFoundDisplay" class="mb-3 p-3 rounded bg-success-subtle text-success border border-success d-none text-center"><h5 class="fw-bold mb-0"><i class="bi bi-check-circle-fill"></i> FOUND</h5><div class="fs-5 text-dark mt-1" id="dispName">...</div><small class="text-muted">Room: <span id="dispRoom">...</span></small></div>
                        <div id="studentErrorDisplay" class="mb-3 p-2 rounded bg-danger-subtle text-danger border border-danger d-none text-center"><i class="bi bi-exclamation-triangle-fill me-1"></i> Student not found!</div>
                        <div class="mb-4">
                            <label class="fw-bold small text-muted">Tracking Number</label>
                            <div class="input-group"><span class="input-group-text bg-light"><i class="bi bi-qr-code"></i></span><input type="text" id="scanTracking" name="tracking_number" class="form-control form-control-lg" required placeholder="Waiting for scan..." readonly></div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 shadow-sm"><i class="bi bi-save"></i> Save Parcel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editParcelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 bg-info text-dark"><h5 class="modal-title fw-bold"><i class="bi bi-pencil-square"></i> Edit Parcel</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body p-4">
                    <form id="editParcelForm" action="" method="POST">
                        @csrf @method('PUT')
                        <div class="mb-3"><label class="fw-bold small text-muted">Tracking Number</label><input type="text" name="tracking_number" id="editTracking" class="form-control" required></div>
                        <div class="mb-3"><label class="fw-bold small text-muted">Shelf Location</label><input type="text" name="shelf_label" id="editShelf" class="form-control" required></div>
                        
                        <div class="mb-3">
                            <label class="fw-bold small text-muted">Current Status</label>
                            <select name="status" id="editStatus" class="form-select bg-light fw-bold">
                                <option value="unpaid">🟡 Unpaid</option>
                                <option value="ready">🔵 Ready for Pickup (Paid)</option>
                                <option value="collected">🟢 Delivered / Collected</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-info w-100 rounded-pill fw-bold shadow-sm"><i class="bi bi-check-lg"></i> Update Parcel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="scanCollectionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 bg-success text-white"><h5 class="modal-title fw-bold"><i class="bi bi-qr-code-scan"></i> Scan to Collect</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body p-4 text-center">
                    <div class="text-center mb-2"><span class="badge bg-success bg-opacity-75">📷 Auto-Scanning...</span></div>
                    <div class="scanner-viewport">
                        <div id="reader-collection" style="width: 100%; height: 100%;"></div>
                        <div class="scan-overlay" style="border-color: rgba(255,255,255,0.5);"></div><div class="scan-line"></div>
                    </div>
                    <input type="hidden" id="collectionInput"><div id="scanError" class="text-danger mt-2 fw-bold d-none"><i class="bi bi-x-circle"></i> Parcel Not Found!</div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="collectionResultModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 bg-dark text-white"><h6 class="modal-title fw-bold">📦 Confirm Collection</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-center p-4"><h6 class="text-muted text-uppercase small fw-bold mb-1">Location</h6><h1 class="display-1 fw-bold text-primary mb-3" id="resShelf">...</h1><div class="card bg-light border-0 p-3 mb-4 text-start"><div class="row g-2"><div class="col-12"><small class="text-muted">STUDENT:</small> <strong class="text-dark" id="resName">...</strong></div><div class="col-12"><small class="text-muted">TRACKING:</small> <strong class="text-dark" id="resTracking">...</strong></div><div class="col-12"><small class="text-muted">PAYMENT:</small> <span id="resStatusBadge" class="badge bg-warning text-dark">...</span></div></div></div><form id="confirmCollectionForm" action="" method="POST">@csrf <button type="submit" class="btn btn-success w-100 btn-lg rounded-pill shadow fw-bold"><i class="bi bi-check-lg"></i> Parcel Delivered</button></form></div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="studentDBModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header border-0"><h5 class="modal-title fw-bold"><i class="fa-solid fa-database"></i> Manage Students</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="card bg-light border-0 p-3 mb-4"><h6 class="fw-bold mb-2">Add New Student</h6><form action="/admin/add-student" method="POST" class="row g-2">@csrf <div class="col-md-3"><input type="text" name="name" class="form-control form-control-sm" placeholder="Full Name" required></div><div class="col-md-2"><input type="text" name="student_id" class="form-control form-control-sm" placeholder="Matrix ID" required></div><div class="col-md-2"><input type="text" name="room_number" class="form-control form-control-sm" placeholder="Room No" required></div><div class="col-md-3"><input type="text" name="ic_number" class="form-control form-control-sm" placeholder="IC" required></div><div class="col-md-2"><input type="text" name="phone" class="form-control form-control-sm" placeholder="Phone" required></div><div class="col-12 mt-2 text-end"><button type="submit" class="btn btn-primary btn-sm px-4">Add Student</button></div></form></div><div class="table-responsive" style="max-height: 300px; overflow-y: auto;"><table class="table table-sm align-middle table-hover"><thead class="table-light sticky-top"><tr><th>Name</th><th>Matrix ID</th><th>Room</th><th>Phone</th><th>Action</th></tr></thead><tbody>@if(isset($students)) @foreach($students as $student) <tr><td>{{ $student->name }}</td><td class="fw-bold text-primary">{{ $student->student_id }}</td><td><span class="badge bg-secondary">{{ $student->room_number }}</span></td><td>{{ $student->phone }}</td><td><form action="/admin/delete-student/{{ $student->id }}" method="POST">@csrf <button class="btn btn-danger btn-sm px-2 py-0">Remove</button></form></td></tr> @endforeach @endif</tbody></table></div></div></div></div></div>
    <div class="modal fade" id="viewStudentModal" tabindex="-1"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content border-0 shadow"><div class="modal-header bg-primary text-white border-0"><h6 class="modal-title fw-bold">Student Details</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body text-center py-4"><div class="mb-3"><div class="avatar bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;"><i class="bi bi-person fs-2 text-primary"></i></div></div><h5 class="fw-bold mb-1" id="modalStudentName">...</h5><p class="text-muted small mb-3" id="modalStudentMatrix">...</p><div class="row g-2 text-start px-3"><div class="col-12 bg-light p-2 rounded"><small class="text-muted d-block" style="font-size: 10px;">ROOM</small><span class="fw-bold text-dark" id="modalStudentRoom">...</span></div><div class="col-12 bg-light p-2 rounded"><small class="text-muted d-block" style="font-size: 10px;">PHONE</small><span class="fw-bold text-dark" id="modalStudentPhone">...</span></div></div></div></div></div></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ============================================
        // 1. ADD PARCEL LOGIC (HEADLESS & SAFE TRANSITION)
        // ============================================
        const addParcelModal = document.getElementById('addParcelModal');
        const scanStatusBadge = document.getElementById('scanStatusBadge');
        const ocrVideo = document.getElementById('ocrVideo');
        const ocrLoading = document.getElementById('ocrLoading');
        const scanPhoneInput = document.getElementById('scanPhone');
        const scanTrackingInput = document.getElementById('scanTracking');
        const foundDisplay = document.getElementById('studentFoundDisplay');
        const errorDisplay = document.getElementById('studentErrorDisplay');

        let ocrInterval = null; 
        let barcodeScannerAdd = null; 
        let isStudentFound = false;

        addParcelModal.addEventListener('shown.bs.modal', function () {
            isStudentFound = false;
            scanPhoneInput.value = '';
            scanTrackingInput.value = '';
            foundDisplay.classList.add('d-none');
            errorDisplay.classList.add('d-none');
            startOCRMode();
        });

        addParcelModal.addEventListener('hidden.bs.modal', () => {
            stopOCRMode();
            if(barcodeScannerAdd) {
                barcodeScannerAdd.stop().then(() => barcodeScannerAdd.clear()).catch(e => console.log(e));
                barcodeScannerAdd = null;
            }
        });

        function startOCRMode() {
            scanStatusBadge.innerHTML = "👁️ POINT AT PHONE NUMBER";
            scanStatusBadge.className = "badge bg-info text-dark";
            ocrVideo.classList.remove('d-none'); 
            document.getElementById('reader-add-parcel').classList.add('d-none');
            
            navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
                .then(stream => {
                    ocrVideo.srcObject = stream;
                    ocrInterval = setInterval(readTextFromCamera, 1500);
                }).catch(err => console.error("Camera Error:", err));
        }

        function stopOCRMode() {
            clearInterval(ocrInterval);
            ocrVideo.classList.add('d-none');
            if(ocrVideo.srcObject) {
                let tracks = ocrVideo.srcObject.getTracks();
                tracks.forEach(track => track.stop());
                ocrVideo.srcObject = null;
            }
        }

        async function readTextFromCamera() {
            if(isStudentFound) return;
            const canvas = document.createElement('canvas');
            canvas.width = ocrVideo.videoWidth;
            canvas.height = ocrVideo.videoHeight;
            canvas.getContext('2d').drawImage(ocrVideo, 0, 0);
            ocrLoading.classList.remove('d-none'); 
            Tesseract.recognize(canvas, 'eng').then(({ data: { text } }) => {
                let cleanNumbers = text.replace(/[^0-9]/g, '');
                let match = cleanNumbers.match(/(01\d{8,9})/);
                if (match) checkStudent(match[0]);
                ocrLoading.classList.add('d-none');
            });
        }

        function checkStudent(phone) {
            fetch('/admin/check-student/' + phone).then(res => res.json()).then(data => {
                if(data.status === 'found') {
                    isStudentFound = true;
                    stopOCRMode(); 
                    scanPhoneInput.value = phone;
                    document.getElementById('dispName').innerText = data.name;
                    document.getElementById('dispRoom').innerText = data.room;
                    foundDisplay.classList.remove('d-none');
                    errorDisplay.classList.add('d-none');
                    setTimeout(startBarcodeMode, 500); // 500ms safety delay
                }
            });
        }

        function startBarcodeMode() {
            scanStatusBadge.innerHTML = "📦 SCAN TRACKING QR/BARCODE";
            scanStatusBadge.className = "badge bg-warning text-dark";
            document.getElementById('reader-add-parcel').classList.remove('d-none');

            if(!barcodeScannerAdd) {
                barcodeScannerAdd = new Html5Qrcode("reader-add-parcel");
            }

            barcodeScannerAdd.start(
                { facingMode: "environment" }, 
                { fps: 10, qrbox: { width: 250, height: 250 } }, 
                (decodedText) => {
                    scanTrackingInput.value = decodedText;
                    scanStatusBadge.innerHTML = "✅ READY TO SAVE";
                    scanStatusBadge.className = "badge bg-success";
                    scanTrackingInput.classList.add('flash-success');
                    barcodeScannerAdd.stop().then(() => barcodeScannerAdd.clear());
                },
                (errorMessage) => { /* ignore */ }
            ).catch(err => {
                console.log("QR Start Error", err);
                setTimeout(() => startBarcodeMode(), 1000);
            });
        }

        // ============================================
        // 2. COLLECTION SCANNER
        // ============================================
        const scanCollectionModal = document.getElementById('scanCollectionModal');
        const collectionInput = document.getElementById('collectionInput');
        const scanError = document.getElementById('scanError');
        const resultModal = new bootstrap.Modal(document.getElementById('collectionResultModal'));
        let collectionScanner = null;

        scanCollectionModal.addEventListener('shown.bs.modal', () => {
            collectionInput.value = '';
            scanError.classList.add('d-none');
            if (!collectionScanner) collectionScanner = new Html5Qrcode("reader-collection");
            collectionScanner.start(
                { facingMode: "environment" }, { fps: 10, qrbox: 250 },
                (decodedText) => {
                    collectionScanner.stop().then(() => collectionScanner.clear());
                    collectionInput.value = decodedText;
                    checkParcelDetails();
                }, () => {}
            ).catch(err => console.error("Coll Cam Error", err));
        });

        scanCollectionModal.addEventListener('hidden.bs.modal', () => {
            if (collectionScanner) collectionScanner.stop().then(() => collectionScanner.clear()).catch(e => console.log(e));
        });

        function checkParcelDetails() {
            let code = collectionInput.value.trim();
            if(!code) return;
            fetch('/admin/check-parcel-details/' + code).then(r => r.json()).then(data => {
                if (data.status === 'success') {
                    bootstrap.Modal.getInstance(scanCollectionModal).hide();
                    document.getElementById('resShelf').innerText = data.shelf;
                    document.getElementById('resName').innerText = data.student_name;
                    document.getElementById('resTracking').innerText = data.tracking_number;
                    const badge = document.getElementById('resStatusBadge');
                    if(data.is_paid) { badge.className = 'badge bg-success'; badge.innerText = 'PAID (Ready)'; } 
                    else { badge.className = 'badge bg-danger'; badge.innerText = 'NOT PAID!'; }
                    document.getElementById('confirmCollectionForm').action = '/collect-parcel/' + data.id;
                    resultModal.show();
                } else {
                    scanError.classList.remove('d-none');
                    collectionInput.value = '';
                }
            });
        }

        // ============================================
        // 3. EDIT & DELETE LOGIC
        // ============================================
        const editParcelModal = document.getElementById('editParcelModal');
        editParcelModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const tracking = button.getAttribute('data-tracking');
            const shelf = button.getAttribute('data-shelf');
            const status = button.getAttribute('data-status'); // Get current status

            document.getElementById('editTracking').value = tracking;
            document.getElementById('editShelf').value = shelf;
            document.getElementById('editStatus').value = status; // Set dropdown value
            document.getElementById('editParcelForm').action = '/admin/update-parcel/' + id;
        });

        const viewParcelModal = document.getElementById('viewParcelModal');
        viewParcelModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            document.getElementById('detailId').innerText = button.getAttribute('data-id');
            document.getElementById('detailTime').innerText = button.getAttribute('data-time');
            document.getElementById('detailStudent').innerText = button.getAttribute('data-student');
            document.getElementById('detailTracking').innerText = button.getAttribute('data-tracking');
            document.getElementById('detailShelf').innerText = button.getAttribute('data-shelf');
            
            const collectedTime = button.getAttribute('data-collected-time');
            const collectedSection = document.getElementById('collectedTimeSection');
            if(collectedTime) {
                document.getElementById('detailCollectedTime').innerText = collectedTime;
                collectedSection.classList.remove('d-none');
            } else {
                collectedSection.classList.add('d-none');
            }
        });

        const viewStudentModal = document.getElementById('viewStudentModal');
        viewStudentModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            document.getElementById('modalStudentName').textContent = button.getAttribute('data-name');
            document.getElementById('modalStudentMatrix').textContent = button.getAttribute('data-id');
            document.getElementById('modalStudentRoom').textContent = button.getAttribute('data-room');
            document.getElementById('modalStudentPhone').textContent = button.getAttribute('data-phone');
        });

        function searchTable() {
            let input = document.getElementById("searchInput").value.toUpperCase();
            let tr = document.getElementById("adminTable").getElementsByTagName("tr");
            for (let i = 1; i < tr.length; i++) {
                let cols = tr[i].getElementsByTagName("td");
                let found = false;
                if(cols.length > 2) {
                    if(cols[0].textContent.toUpperCase().indexOf(input) > -1 || 
                       cols[1].textContent.toUpperCase().indexOf(input) > -1 || 
                       cols[2].textContent.toUpperCase().indexOf(input) > -1) {
                        found = true;
                    }
                }
                tr[i].style.display = found ? "" : "none";
            }
        }
    </script>
</body>
</html>