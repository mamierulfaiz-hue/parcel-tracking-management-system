<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard | Smart Parcel</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            background-color: #f4f6f9; 
            font-family: 'Inter', sans-serif; 
            color: #334155;
            -webkit-font-smoothing: antialiased;
        }

        /* --- Clean Navbar --- */
        .navbar-custom {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            padding: 15px 0;
        }
        
        .brand-logo {
            font-weight: 700;
            font-size: 1.25rem;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        /* --- Colorful Stat Cards --- */
        .stat-card {
            border: none;
            border-radius: 16px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.1);
        }
        
        /* Vibrant Gradients */
        .bg-gradient-primary { 
            background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%); 
            color: white; 
        }
        .bg-gradient-warning { 
            background: linear-gradient(135deg, #ffc107 0%, #d39e00 100%); 
            color: #212529; 
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
        }

        /* --- Filter Tabs --- */
        .filter-tabs {
            display: flex;
            gap: 10px;
            background: #ffffff;
            padding: 6px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            width: fit-content;
            overflow-x: auto;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }
        .filter-btn {
            background: transparent;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            color: #64748b;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .filter-btn.active {
            background: #0d6efd; /* Bright blue for active tab */
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
        }

        /* --- Minimalist Parcel Cards with Colorful Borders --- */
        .parcel-card {
            background: #ffffff;
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }
        .parcel-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.08);
        }
        
        /* Colored Status Stripes at the Top of Cards */
        .border-collected { border-top: 5px solid #10b981; }
        .border-ready { border-top: 5px solid #3b82f6; }
        .border-unpaid { border-top: 5px solid #f59e0b; }
        
        .card-header-clean {
            padding: 20px 20px 15px 20px;
            border-bottom: 1px dashed #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .card-body-clean {
            padding: 20px;
            flex-grow: 1;
        }

        .card-footer-clean {
            padding: 15px 20px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            border-radius: 0 0 16px 16px;
        }

        /* --- Badges & Vibrant Buttons --- */
        .status-badge {
            font-size: 12px;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .badge-collected { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .badge-ready { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .badge-unpaid { background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; }

        .btn-action {
            width: 100%;
            padding: 12px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            border: none;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: 0.5px;
        }
        
        /* Vibrant Pay Button */
        .btn-pay { 
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); 
            color: #000; 
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }
        .btn-pay:hover { 
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); 
            transform: translateY(-2px);
        }
        
        /* Vibrant QR Button */
        .btn-qr { 
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); 
            color: white; 
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
        }
        .btn-qr:hover { 
            background: linear-gradient(135deg, #0b5ed7 0%, #084298 100%); 
            color: white; 
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stat-card { padding: 20px; gap: 15px; }
            .filter-tabs { width: 100%; justify-content: flex-start; }
            .filter-btn { flex: 1; text-align: center; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand brand-logo d-flex align-items-center gap-2" href="#">
                <i class="fa-solid fa-box-open text-primary fs-3"></i> 
                <span class="text-dark">UniMAP <span class="text-primary">Parcel</span></span>
            </a>
            
            <div class="d-flex align-items-center gap-2 ml-auto">
                @if(!$student->chat_id)
                    <a href="https://t.me/PFI2_Parcel_bot?start={{ $student->student_id }}" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold bg-primary bg-opacity-10 d-none d-sm-block border-0">
                        <i class="fa-regular fa-bell me-1"></i> Enable Alerts
                    </a>
                    <a href="https://t.me/PFI2_Parcel_bot?start={{ $student->student_id }}" target="_blank" class="btn btn-outline-primary btn-sm rounded-circle d-flex align-items-center justify-content-center d-sm-none bg-primary bg-opacity-10 border-0" style="width: 35px; height: 35px;">
                        <i class="fa-regular fa-bell"></i>
                    </a>
                @else
                    <form action="/student/toggle-telegram" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 fw-bold shadow-sm d-none d-sm-inline-flex align-items-center">
                            <i class="fa-solid fa-bell-slash me-1"></i> Disable Alerts
                        </button>
                        <button type="submit" class="btn btn-danger btn-sm rounded-circle shadow-sm d-flex align-items-center justify-content-center d-sm-none" style="width: 35px; height: 35px;">
                            <i class="fa-solid fa-bell-slash"></i>
                        </button>
                    </form>
                    <span class="badge bg-success text-white ms-2 d-none d-sm-inline-flex">Alerts On</span>
                @endif

                <form action="/student/logout" method="POST" class="m-0">
                    @csrf
                    <button class="btn btn-danger btn-sm rounded-pill px-4 fw-bold shadow-sm">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container py-4 py-lg-5">
        
        <div class="row mb-4 align-items-center">
            <div class="col-md-6 mb-3 mb-md-0">
                <p class="text-muted mb-1 fw-bold text-uppercase" style="font-size: 12px; letter-spacing: 1px;">Welcome back</p>
                <h2 class="fw-bold text-dark mb-0" style="letter-spacing: -1px;">{{ $student->name }}</h2>
            </div>
        </div>

        <div class="row g-3 mb-5">
            <div class="col-6 col-md-4">
                <div class="stat-card bg-gradient-primary">
                    <div class="stat-icon d-none d-sm-flex text-white"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <div>
                        <h2 class="fw-bold mb-0 text-white">{{ $pendingCount }}</h2>
                        <small class="fw-medium text-uppercase text-white opacity-75" style="font-size: 11px; letter-spacing: 0.5px;">Pending Items</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stat-card bg-gradient-warning">
                    <div class="stat-icon d-none d-sm-flex" style="color: #212529;"><i class="fa-solid fa-wallet"></i></div>
                    <div>
                        <h2 class="fw-bold mb-0 text-dark"><span class="fs-6 opacity-75">RM</span> {{ number_format($totalPayment, 2) }}</h2>
                        <small class="fw-bold text-uppercase text-dark opacity-75" style="font-size: 11px; letter-spacing: 0.5px;">Amount Due</small>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success rounded-3 shadow-sm border border-success-subtle d-flex align-items-center mb-4 p-3 bg-white">
                <i class="fa-solid fa-circle-check fs-4 me-3 text-success"></i>
                <div class="fw-bold text-dark">{{ session('success') }}</div>
            </div>
        @endif

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <h4 class="fw-bold mb-0 text-dark">Your Inventory</h4>
            
            <div class="filter-tabs">
                <button class="filter-btn active" onclick="filterParcels('all', this)">All Parcels</button>
                <button class="filter-btn" onclick="filterParcels('action', this)">Action Needed</button>
                <button class="filter-btn" onclick="filterParcels('completed', this)">Completed</button>
            </div>
        </div>

        @if($parcels->isEmpty())
            <div class="text-center py-5 bg-white border rounded-4 shadow-sm mt-3">
                <div class="mb-3 text-muted" style="opacity: 0.2;"><i class="fa-solid fa-box-open fa-4x"></i></div>
                <h5 class="fw-bold text-dark mb-1">No Parcels Yet</h5>
                <p class="text-muted small">You don't have any items in the system right now.</p>
            </div>
        @else
            <div class="row g-4 justify-content-start" id="parcelContainer">
                
                @php
                    $activeParcels = $parcels->where('is_collected', false)->sortByDesc('created_at');
                    $completedParcels = $parcels->where('is_collected', true)->sortByDesc('updated_at');
                    $sortedParcels = $activeParcels->merge($completedParcels);
                @endphp

                @foreach($sortedParcels as $parcel)
                
                @php
                    // Determine filter category for Javascript
                    $filterClass = $parcel->is_collected ? 'filter-completed' : 'filter-action';
                    // Determine Border Color based on status
                    $borderClass = $parcel->is_collected ? 'border-collected' : ($parcel->is_paid ? 'border-ready' : 'border-unpaid');
                @endphp

                <div class="col-12 col-md-6 col-lg-4 parcel-item {{ $filterClass }}">
                    <div class="parcel-card {{ $borderClass }}">
                        
                        <div class="card-header-clean">
                            <div style="max-width: 65%;">
                                <small class="text-muted fw-bold d-block mb-1" style="font-size: 10px; letter-spacing: 1px;">TRACKING NO.</small>
                                <h6 class="fw-bold mb-0 text-dark text-truncate" title="{{ $parcel->tracking_number }}">{{ $parcel->tracking_number }}</h6>
                            </div>
                            
                            <div>
                                @if($parcel->is_collected)
                                    <span class="status-badge badge-collected"><i class="fa-solid fa-check"></i> Collected</span>
                                @elseif($parcel->is_paid)
                                    <span class="status-badge badge-ready"><i class="fa-solid fa-box"></i> Ready</span>
                                @else
                                    <span class="status-badge badge-unpaid"><i class="fa-regular fa-clock"></i> Unpaid</span>
                                @endif
                            </div>
                        </div>

                        <div class="card-body-clean">
                            <div class="d-flex align-items-center gap-2 text-muted mb-1" style="font-size: 13px;">
                                <i class="fa-regular fa-calendar text-secondary"></i> 
                                <span>Arrived: <span class="fw-bold text-dark">{{ $parcel->created_at->format('d M Y, h:i A') }}</span></span>
                            </div>
                            <div class="d-flex align-items-center gap-2 text-muted mt-2" style="font-size: 13px;">
                                <i class="fa-solid fa-credit-card text-primary"></i> 
                                <span>Paid: <span class="fw-bold text-dark">{{ optional($parcel->paid_at)->format('d M Y, h:i A') ?? 'Pending' }}</span></span>
                            </div>
                            <div class="d-flex align-items-center gap-2 text-muted mt-2" style="font-size: 13px;">
                                <i class="fa-solid fa-truck-fast text-success"></i> 
                                <span>Delivered: <span class="fw-bold text-dark">{{ optional($parcel->collected_at)->format('d M Y, h:i A') ?? 'Not Delivered' }}</span></span>
                            </div>
                        </div>

                        <div class="card-footer-clean">
                            @if($parcel->is_collected)
                                <div class="text-center py-2 text-success fw-bold" style="font-size: 14px;">
                                    <i class="fa-solid fa-shield-check me-1"></i> Transaction Complete
                                </div>
                            
                            @elseif($parcel->is_paid)
                                <button class="btn-action btn-qr" data-bs-toggle="modal" data-bs-target="#viewQrModal" data-id="{{ $parcel->unique_id }}">
                                    <i class="fa-solid fa-qrcode fs-5"></i> Show Access QR
                                </button>
                            
                            @else
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted fw-medium" style="font-size: 13px;">Processing Fee</span>
                                    <span class="fw-bold text-primary fs-5">RM 1.00</span>
                                </div>
                                <a href="/student/pay/{{ $parcel->id }}" class="text-decoration-none">
                                    <button class="btn-action btn-pay">
                                        Pay Now <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </button>
                                </a>
                            @endif
                        </div>

                    </div>
                </div>
                @endforeach
            </div>
        @endif

    </div>

    <div class="modal fade" id="viewQrModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-0 bg-primary bg-gradient p-3">
                    <h6 class="modal-title fw-bold m-0 text-white"><i class="fa-solid fa-qrcode me-2"></i> Scan at Dashboard</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <h3 class="fw-bold text-dark mb-4" id="modalQrText" style="letter-spacing: 2px;">...</h3>
                    <div class="bg-white p-2 rounded-4 d-inline-block border shadow-sm">
                        <img id="modalQrImage" src="" alt="Access QR" class="img-fluid" style="width: 220px; height: 220px;">
                    </div>
                    <p class="text-muted mt-4 mb-0" style="font-size: 13px;">Hold this QR code up to the dashcam to automatically collect your parcel.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // 1. Client-Side Tab Filtering Logic
        function filterParcels(category, buttonElement) {
            // Update active button styling
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            buttonElement.classList.add('active');

            // Show/Hide items based on class
            const items = document.querySelectorAll('.parcel-item');
            items.forEach(item => {
                if (category === 'all') {
                    item.style.display = 'block';
                } else if (category === 'action' && item.classList.contains('filter-action')) {
                    item.style.display = 'block';
                } else if (category === 'completed' && item.classList.contains('filter-completed')) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // 2. QR Modal Inject Logic
        const viewQrModal = document.getElementById('viewQrModal');
        if (viewQrModal) {
            viewQrModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                const uniqueId = button.getAttribute('data-id');
                document.getElementById('modalQrText').innerText = uniqueId;
                document.getElementById('modalQrImage').src = `https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=${uniqueId}`;
            });
        }
    </script>
</body>
</html>