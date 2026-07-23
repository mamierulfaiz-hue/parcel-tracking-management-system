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
        
        /* Responsive Search Box */
        .search-box { border-radius: 20px; border: 1px solid #ddd; padding: 8px 15px; background: white; width: 100%; max-width: 350px; }
        .search-box input { border: none; outline: none; width: 100%; }
        
        .student-link { cursor: pointer; color: #0d6efd; text-decoration: none; font-weight: bold; transition: 0.2s; }
        .student-link:hover { color: #0a58ca; text-decoration: underline; }
        
        @keyframes flash-green { 0% { background-color: #d1e7dd; } 100% { background-color: white; } }
        .flash-success { animation: flash-green 1s; }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .search-box { max-width: 100%; }
            .action-buttons { flex-direction: column; width: 100%; }
            .action-buttons button { width: 100%; justify-content: center; }
            .overview-header { flex-direction: column; align-items: flex-start !important; }
            .main-card { padding: 15px; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 shadow-sm">
        <a class="navbar-brand fw-bold" href="#"><i class="bi bi-shield-lock-fill"></i> Admin Dashboard</a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <form action="/logout" method="POST" class="d-inline m-0">
                @csrf
                <button class="btn btn-danger btn-sm rounded-pill px-4 shadow-sm">Logout</button>
            </form>
        </div>
    </nav>

    <div class="container mt-4 mt-md-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4 overview-header gap-3">
            <h3 class="mb-0 fw-bold">Overview</h3>
            <div class="d-flex gap-2 flex-wrap flex-md-nowrap w-100 justify-content-md-end action-buttons">
                
                <div class="d-flex align-items-center search-box shadow-sm flex-grow-1 flex-md-grow-0">
                    <i class="bi bi-search me-2 text-muted"></i>
                    <input type="text" id="searchInput" placeholder="Search ID, Tracking, Shelf..." onkeyup="searchTable()">
                </div>
                
                <button class="btn btn-outline-dark rounded-pill px-4 shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#studentDBModal">
                    <i class="fa-solid fa-users"></i> Student DB
                </button>

                <button class="btn btn-primary rounded-pill px-4 shadow-sm d-flex align-items-center gap-2" onclick="triggerPiForAdd()" data-bs-toggle="modal" data-bs-target="#addParcelModal">
                    <i class="bi bi-plus-lg"></i> Add Parcel
                </button>

                <button class="btn btn-success rounded-pill px-4 shadow-sm d-flex align-items-center gap-2" onclick="triggerPiCamera()" data-bs-toggle="modal" data-bs-target="#scanCollectionModal">
                    <i class="bi bi-qr-code-scan"></i> Scan to Collect
                </button>
            </div>
        </div>

        <div class="row mb-4 g-3">
            <div class="col-12 col-md-4"><div class="card card-box bg-gradient-primary p-4 h-100"><div class="d-flex justify-content-between align-items-center"><div><h6 class="opacity-75 mb-1">In Storage</h6><h2 class="fw-bold mb-0">{{ $totalInStorage ?? 0 }}</h2></div><i class="fa-solid fa-boxes-stacked fa-2x opacity-50"></i></div></div></div>
            <div class="col-12 col-md-4"><div class="card card-box bg-gradient-success p-4 h-100"><div class="d-flex justify-content-between align-items-center"><div><h6 class="opacity-75 mb-1">Delivered</h6><h2 class="fw-bold mb-0">{{ $totalDelivered ?? 0 }}</h2></div><i class="fa-solid fa-clipboard-check fa-2x opacity-50"></i></div></div></div>
            <div class="col-12 col-md-4"><div class="card card-box bg-gradient-warning p-4 h-100"><div class="d-flex justify-content-between align-items-center"><div><h6 class="opacity-75 mb-1 text-dark">Unpaid Parcels</h6><h2 class="fw-bold mb-0 text-dark">{{ $totalUnpaid ?? 0 }}</h2></div><i class="fa-solid fa-triangle-exclamation fa-2x opacity-50 text-dark"></i></div></div></div>
        </div>

        <div class="main-card mb-5">
            <h5 class="mb-3 fw-bold border-bottom pb-2">Recent Parcels</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="adminTable">
                    <thead class="table-light text-nowrap">
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
                            <td class="fw-bold text-nowrap">
                                <a href="#" class="text-decoration-none text-dark" data-bs-toggle="modal" data-bs-target="#viewParcelModal" 
                                    data-unique-id="{{ $parcel->unique_id }}" data-tracking="{{ $parcel->tracking_number }}" 
                                    data-student="{{ $student ? $student->name : 'Unknown' }}" data-shelf="{{ $parcel->shelf_label }}"
                                    data-time="{{ $parcel->created_at->format('d M Y, h:i A') }}">
                                    {{ $parcel->unique_id }} <i class="bi bi-info-circle small text-primary ms-1"></i>
                                </a>
                                <div class="text-muted small fw-normal" style="font-size: 10px;">{{ $parcel->tracking_number }}</div>
                            </td>
                            <td>{{ $parcel->student_id }}</td>
                            <td><span class="badge bg-dark fs-6">{{ $parcel->shelf_label }}</span></td>
                            <td class="text-nowrap">
                                @if($parcel->is_collected) <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill">Delivered</span>
                                @elseif($parcel->is_paid) <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-1 rounded-pill">Ready for Pickup</span>
                                @else <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning px-2 py-1 rounded-pill">Pending Payment</span> @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editParcelModal" data-id="{{ $parcel->id }}" data-tracking="{{ $parcel->tracking_number }}" data-shelf="{{ $parcel->shelf_label }}" data-status="{{ $parcel->is_collected ? 'collected' : ($parcel->is_paid ? 'ready' : 'unpaid') }}"><i class="bi bi-pencil-square"></i></button>
                                    
                                    <form action="/admin/delete-parcel/{{ $parcel->id }}" method="POST" onsubmit="return confirm('Delete this parcel?');" class="m-0">
                                        @csrf @method('DELETE') 
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
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

    <div class="modal fade" id="addParcelModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">Add Parcel</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <h4 id="piAddStatus" class="text-primary fw-bold">Waiting for Scan...</h4>
                    </div>
                    <form action="/admin/parcels" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="fw-bold small text-muted">Tracking Number</label>
                            <input type="text" id="scanTracking" name="tracking_number" class="form-control form-control-lg bg-light" required readonly>
                        </div>
                        <div class="mb-4">
                            <label class="fw-bold small text-muted">Student Phone Number</label>
                            <input type="text" id="scanPhone" name="student_phone" class="form-control" required placeholder="Will be filled by scanner" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill shadow-sm">Save Parcel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="scanCollectionModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-qr-code-scan me-2"></i>Scan to Collect</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="collectScannerUI" class="text-center mb-3">
                        <h4 id="collectStatus" class="text-success fw-bold">📷 Camera is ON...</h4>
                        <p class="text-muted small">Show Unique ID to Dashcam</p>
                        <input type="text" id="displayUniqueId" class="form-control form-control-lg text-center bg-light mb-3" placeholder="Waiting for scan..." readonly>
                    </div>
                    <div id="parcelDetails" class="mt-4 pt-3 border-top">
                        <h6 class="fw-bold mb-3 text-secondary">Student & Parcel Details</h6>
                        <table class="table table-bordered align-middle mb-4">
                            <tbody>
                                <tr>
                                    <th class="bg-light text-muted small w-50">Student Name</th>
                                    <td id="displayName" class="fw-bold">-</td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-muted small">Matrix ID</th>
                                    <td id="displayMatrix" class="fw-bold">-</td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-muted small">Shelf Location</th>
                                    <td id="displayShelf" class="fw-bold text-primary fs-5">-</td>
                                </tr>
                            </tbody>
                        </table>
                        <input type="hidden" id="foundParcelId">
                        <button class="btn btn-success btn-lg w-100 rounded-pill fw-bold shadow" onclick="confirmFinalDelivery()">
                            Parcel Delivered
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewParcelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light border-0">
                    <h6 class="modal-title fw-bold">Parcel Details</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <h1 class="display-4 fw-bold text-primary" id="vUniqueId">...</h1>
                    <div class="text-start mt-4">
                        <p class="mb-1"><small class="text-muted">Tracking:</small> <span id="vTracking" class="fw-bold"></span></p>
                        <p class="mb-1"><small class="text-muted">Student:</small> <span id="vStudent" class="fw-bold"></span></p>
                        <p class="mb-1"><small class="text-muted">Shelf:</small> <span id="vShelf" class="fw-bold"></span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editParcelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info border-0">
                    <h5 class="modal-title">Edit Parcel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="editParcelForm" method="POST">
                        @csrf 
                        @method('PUT')
                        <input type="text" name="tracking_number" id="editTracking" class="form-control mb-3" required>
                        <input type="text" name="shelf_label" id="editShelf" class="form-control mb-3" required>
                        <select name="status" id="editStatus" class="form-select mb-4">
                            <option value="unpaid">Unpaid</option>
                            <option value="ready">Ready</option>
                            <option value="collected">Delivered</option>
                        </select>
                        <button type="submit" class="btn btn-info w-100 rounded-pill fw-bold">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="studentDBModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Student Database</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="card bg-light border-0 p-3 mb-3">
                        <form action="/admin/add-student" method="POST" class="row g-2">
                            @csrf 
                            <div class="col-12 col-md-3"><input type="text" name="name" class="form-control form-control-sm" placeholder="Name" required></div>
                            <div class="col-6 col-md-2"><input type="text" name="student_id" class="form-control form-control-sm" placeholder="Matrix ID" required></div>
                            <div class="col-6 col-md-2"><input type="text" name="room_number" class="form-control form-control-sm" placeholder="Room No" required></div>
                            <div class="col-12 col-md-3"><input type="text" name="ic_number" class="form-control form-control-sm" placeholder="IC Number" required></div>
                            <div class="col-12 col-md-2"><input type="text" name="phone" class="form-control form-control-sm" placeholder="Phone" required></div>
                            <div class="col-12 mt-2 text-md-end"><button type="submit" class="btn btn-primary btn-sm w-100 w-md-auto">Add Student</button></div>
                        </form>
                    </div>
                    
                    <div class="table-responsive" style="max-height: 250px;">
                        <table class="table table-sm text-nowrap align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>ID</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $s)
                                <tr>
                                    <td>{{$s->name}}</td>
                                    <td>{{$s->student_id}}</td>
                                    <td>
                                        <form action="/admin/delete-student/{{ $s->id }}" method="POST" class="m-0" onsubmit="return confirm('Remove this student?');">
                                            @csrf 
                                            <button class="btn btn-outline-danger btn-sm px-2 py-0"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let pollInterval;

    // SweetAlert Intercepts
    @if(session('success'))
        Swal.fire({ icon: 'success', title: 'Saved!', text: "{{ session('success') }}", confirmButtonColor: '#198754' });
    @endif

    @if($errors->any())
        Swal.fire({ icon: 'error', title: 'Registration Denied', html: `{!! implode('<br>', $errors->all()) !!}`, confirmButtonColor: '#0d6efd' });
        // Force the modal to re-open if there is a validation error
        window.addEventListener('load', function() {
            var myModal = new bootstrap.Modal(document.getElementById('addParcelModal'));
            myModal.show();
            document.getElementById('piAddStatus').innerText = "❌ Duplicate Entry!";
            fetch('/api/scanner-reset');
        });
    @endif

    window.addEventListener('load', () => fetch('/api/scanner-reset'));

    // --- SCANNER LOGIC ---
    let addPolling = null;
    let phonePolling = null;
    let addModeReady = false;

    function startAddTrackingPoll() {
        if (addPolling) clearInterval(addPolling);
        addPolling = setInterval(() => {
            fetch('/api/check-latest-tracking?t=' + new Date().getTime())
            .then(res => res.json())
            .then(data => {
                if (data.tracking_number && !document.getElementById('scanTracking').value) {
                    document.getElementById('scanTracking').value = data.tracking_number;
                    document.getElementById('piAddStatus').innerText = "✅ Tracking scanned. Press ENTER to scan phone number.";
                    addModeReady = true;
                    if (phonePolling) {
                        clearInterval(phonePolling);
                        phonePolling = null;
                    }
                }
            });
        }, 1000);
    }

    function startAddPhonePoll() {
        if (phonePolling) clearInterval(phonePolling);
        phonePolling = setInterval(() => {
            fetch('/api/check-latest-tracking?t=' + new Date().getTime())
            .then(res => res.json())
            .then(data => {
                if (data.student_phone) {
                    clearInterval(phonePolling);
                    phonePolling = null;
                    fetch('/api/scanner-reset');
                    document.getElementById('scanPhone').value = data.student_phone;
                    document.getElementById('piAddStatus').innerText = "✅ ALL SCANNED!";
                }
            });
        }, 1000);
    }

    function handleAddModalKeydown(event) {
        if (event.key !== 'Enter') return;
        if (!addModeReady) return;
        if (!document.getElementById('scanTracking').value) return;
        if (document.getElementById('scanPhone').value) return;

        event.preventDefault();
        addModeReady = false;
        document.getElementById('piAddStatus').innerText = "📷 Scan phone number now...";

        fetch('/api/scanner-trigger-add', { method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'} });
        startAddPhonePoll();
        document.removeEventListener('keydown', handleAddModalKeydown);
    }

    function triggerPiForAdd() {
        document.getElementById('scanTracking').value = "";
        document.getElementById('scanPhone').value = "";
        document.getElementById('piAddStatus').innerText = "📷 Camera is ON for tracking scan...";
        addModeReady = false;

        fetch('/api/scanner-reset').then(() => {
            fetch('/api/scanner-trigger-add', { method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'} });
        });

        if (pollInterval) clearInterval(pollInterval);
        if (addPolling) clearInterval(addPolling);
        if (phonePolling) clearInterval(phonePolling);
        pollInterval = null;
        addPolling = null;
        phonePolling = null;

        startAddTrackingPoll();
        document.addEventListener('keydown', handleAddModalKeydown);
    }

    function triggerPiCamera() {
        document.getElementById('collectStatus').innerText = "📷 Camera is ON...";
        document.getElementById('displayUniqueId').value = "";
        document.getElementById('displayShelf').innerText = "-";
        document.getElementById('displayName').innerText = "-";
        document.getElementById('displayMatrix').innerText = "-";

        fetch('/api/scanner-reset').then(() => {
            fetch('/api/scanner-trigger', { method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'} });
        });

        if(pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(() => {
            fetch('/api/check-latest-scan').then(res => res.json()).then(data => {
                if (data.found) {
                    clearInterval(pollInterval);
                    fetch('/api/scanner-reset'); 
                    document.getElementById('displayUniqueId').value = data.unique_id; 
                    document.getElementById('displayShelf').innerText = data.location;
                    document.getElementById('displayName').innerText = data.student_name;
                    document.getElementById('displayMatrix').innerText = data.student_id;
                    document.getElementById('foundParcelId').value = data.id;
                    document.getElementById('collectStatus').innerText = "✅ PARCEL RECOGNIZED!";
                }
            });
        }, 1000);
    }

    function confirmFinalDelivery() {
        const id = document.getElementById('foundParcelId').value;
        if(!id) return Swal.fire('Error', 'No parcel scanned yet!', 'error');
        
        fetch('/api/confirm-collection/' + id, { method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'} })
        .then(res => res.json()).then(d => { 
            if(d.status === 'success') {
                Swal.fire('Delivered', 'Status Updated!', 'success').then(() => location.reload()); 
            }
        });
    }

    function searchTable() {
        let input = document.getElementById("searchInput").value.toUpperCase();
        let tr = document.getElementById("adminTable").getElementsByTagName("tr");
        for (let i = 1; i < tr.length; i++) {
            tr[i].style.display = tr[i].innerText.toUpperCase().indexOf(input) > -1 ? "" : "none";
        }
    }

    // --- MODAL EVENT LISTENERS ---
    const vModal = document.getElementById('viewParcelModal');
    if(vModal) vModal.addEventListener('show.bs.modal', e => {
        const b = e.relatedTarget;
        document.getElementById('vUniqueId').innerText = b.getAttribute('data-unique-id');
        document.getElementById('vTracking').innerText = b.getAttribute('data-tracking');
        document.getElementById('vStudent').innerText = b.getAttribute('data-student');
        document.getElementById('vShelf').innerText = b.getAttribute('data-shelf');
    });

    const eModal = document.getElementById('editParcelModal');
    if (eModal) {
        eModal.addEventListener('show.bs.modal', e => {
            const b = e.relatedTarget;
            document.getElementById('editParcelForm').action = '/api/update-parcel/' + b.getAttribute('data-id');
            document.getElementById('editTracking').value = b.getAttribute('data-tracking');
            document.getElementById('editShelf').value = b.getAttribute('data-shelf');
            document.getElementById('editStatus').value = b.getAttribute('data-status');
        });
    }

    // KILL SWITCH: Reset Pi if modal cleared/closed
    document.querySelectorAll('.modal').forEach(m => {
        m.addEventListener('hidden.bs.modal', () => { 
            if(pollInterval) clearInterval(pollInterval); 
            if(addPolling) clearInterval(addPolling);
            if(phonePolling) clearInterval(phonePolling);
            fetch('/api/scanner-reset'); 
            document.removeEventListener('keydown', handleAddModalKeydown);
            if(document.getElementById('scanTracking')) document.getElementById('scanTracking').value = "";
            if(document.getElementById('scanPhone')) document.getElementById('scanPhone').value = "";
            if(document.getElementById('piAddStatus')) document.getElementById('piAddStatus').innerText = "Waiting for Scan...";
        });
    });
</script>
</body>
</html>