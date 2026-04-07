<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard | Parcel System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        /* Modern Header */
        .dashboard-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0099ff 100%);
            color: white;
            padding: 40px 0 80px 0;
            border-radius: 0 0 40px 40px;
            box-shadow: 0 10px 30px rgba(13, 110, 253, 0.2);
            position: relative;
        }

        .stat-pill {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 15px 30px;
            min-width: 160px;
        }

        .cards-container {
            margin-top: -50px; 
            padding-bottom: 50px;
        }

        .parcel-card {
            border: none;
            border-radius: 20px;
            background: white;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .parcel-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .card-top-strip { height: 6px; width: 100%; }
        .bg-unpaid { background-color: #ffc107; }
        .bg-ready { background-color: #0d6efd; }
        .bg-collected { background-color: #198754; }

        .btn-pay {
            background: linear-gradient(45deg, #ffc107, #ffdb4d);
            border: none;
            color: #000;
            font-weight: 700;
            padding: 12px;
            border-radius: 12px;
            width: 100%;
            transition: 0.2s;
        }
        .btn-pay:hover { transform: scale(1.02); }

        .qr-container {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 15px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .qr-container:hover { background-color: #e9ecef; }
    </style>
</head>
<body>

    <div class="dashboard-header text-center">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0"><i class="fa-solid fa-box-open me-2"></i> My Parcels</h3>
                
                <div class="d-flex gap-2">
                    
                    @if(!$student->chat_id)
                        <a href="https://t.me/PFI2_Parcel_bot?start={{ $student->student_id }}" 
                           target="_blank" 
                           class="btn btn-light btn-sm rounded-pill px-3 fw-bold text-primary shadow-sm d-flex align-items-center gap-2">
                            <i class="fa-regular fa-bell"></i> Get Notifications
                        </a>
                    @else
                        <button class="btn btn-success btn-sm rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center gap-2" disabled>
                            <i class="fa-solid fa-bell"></i> Notifications On
                        </button>
                    @endif

                    <form action="/student/logout" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-outline-light btn-sm rounded-pill px-4 fw-bold">Logout</button>
                    </form>
                </div>
            </div>

            <h5 class="opacity-75 fw-normal mb-1">Welcome back,</h5>
            <h1 class="fw-bold mb-4">{{ $student->name }}</h1>

            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <div class="stat-pill">
                    <div class="h2 fw-bold mb-0">{{ $pendingCount }}</div>
                    <small class="opacity-75">Items Pending</small>
                </div>
                <div class="stat-pill">
                    <div class="h2 fw-bold mb-0">RM {{ number_format($totalPayment, 2) }}</div>
                    <small class="opacity-75">Total Unpaid</small>
                </div>
            </div>
        </div>
    </div>

    <div class="container cards-container">
        
        @if(session('success'))
            <div class="alert alert-success rounded-4 shadow-sm border-0 d-flex align-items-center mb-4 p-3 bg-white">
                <i class="fa-solid fa-circle-check fs-4 me-3 text-success"></i>
                <div class="fw-bold text-dark">{{ session('success') }}</div>
            </div>
        @endif

        @if($parcels->isEmpty())
            <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                <div class="mb-3 text-muted opacity-25"><i class="fa-solid fa-box-open fa-5x"></i></div>
                <h4 class="fw-bold text-muted">No Parcels Found</h4>
                <p class="text-muted">You are all caught up! No parcels waiting for you.</p>
            </div>
        @else
            <div class="row g-4 justify-content-center">
                
                {{-- SORTING: Active first, Completed last --}}
                @php
                    $activeParcels = $parcels->where('is_collected', false)->sortByDesc('created_at');
                    $completedParcels = $parcels->where('is_collected', true)->sortByDesc('updated_at');
                    $sortedParcels = $activeParcels->merge($completedParcels);
                @endphp

                @foreach($sortedParcels as $parcel)
                <div class="col-md-6 col-lg-4">
                    <div class="parcel-card position-relative">
                        
                        @if($parcel->is_collected)
                            <div class="card-top-strip bg-collected"></div>
                        @elseif($parcel->is_paid)
                            <div class="card-top-strip bg-ready"></div>
                        @else
                            <div class="card-top-strip bg-unpaid"></div>
                        @endif

                        <div class="p-4 d-flex flex-column h-100">
                            
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <small class="text-muted text-uppercase fw-bold" style="font-size: 11px;">Tracking Number</small>
                                    <h5 class="fw-bold mb-0 text-primary">{{ $parcel->tracking_number }}</h5>
                                    <small class="text-muted" style="font-size: 12px;">
                                        <i class="fa-regular fa-clock me-1"></i> {{ $parcel->created_at->format('d M, h:i A') }}
                                    </small>
                                </div>
                                
                                @if($parcel->is_collected)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill">
                                        <i class="fa-solid fa-check-circle me-1"></i> Collected
                                    </span>
                                @elseif($parcel->is_paid)
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-1 rounded-pill">
                                        <i class="fa-solid fa-box-open me-1"></i> Ready for Pickup
                                    </span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning px-2 py-1 rounded-pill">
                                        <i class="fa-solid fa-hourglass-half me-1"></i> Unpaid
                                    </span>
                                @endif
                            </div>

                            <div class="mt-auto">
                                @if($parcel->is_collected)
                                    <div class="p-3 bg-light rounded-3 text-center text-muted">
                                        <i class="fa-solid fa-check-circle mb-1 fs-4 text-success"></i><br>
                                        <small class="fw-bold">Collected on {{ $parcel->updated_at->format('d M, h:i A') }}</small>
                                    </div>
                                @elseif($parcel->is_paid)
                                    <div class="qr-container" data-bs-toggle="modal" data-bs-target="#viewQrModal" data-id="{{ $parcel->unique_id }}">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ $parcel->unique_id }}" 
                                             alt="QR Code" class="img-fluid mb-2" style="max-width: 140px;">
                                        <h4 class="fw-bold text-dark mb-0" style="letter-spacing: 2px;">{{ $parcel->unique_id }}</h4>
                                        <small class="text-primary d-block mt-1" style="font-size: 11px; font-weight: bold;">
                                            <i class="fa-solid fa-magnifying-glass-plus"></i> Click to Enlarge
                                        </small>
                                    </div>
                                @else
                                    <div class="text-center mb-3">
                                        <h3 class="fw-bold mb-0">RM 1.00</h3>
                                        <small class="text-muted">Service Fee</small>
                                    </div>
                                    <a href="/student/pay/{{ $parcel->id }}" class="btn btn-pay shadow-sm">
                                        Pay Now <i class="fa-solid fa-chevron-right ms-1"></i>
                                    </a>
                                @endif
                            </div>

                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif

    </div>

    <div class="modal fade" id="viewQrModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 bg-primary text-white">
                    <h6 class="modal-title fw-bold">Scan at Counter</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-5 bg-white">
                    <h2 class="display-4 fw-bold text-dark mb-4" id="modalQrText" style="letter-spacing: 3px;">...</h2>
                    <img id="modalQrImage" src="" alt="Large QR" class="img-fluid border p-2 rounded shadow-sm" style="width: 300px; height: 300px;">
                    <p class="text-muted mt-3 mb-0">Show this to the admin to collect your parcel.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const viewQrModal = document.getElementById('viewQrModal');
        viewQrModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            const uniqueId = button.getAttribute('data-id');
            document.getElementById('modalQrText').innerText = uniqueId;
            document.getElementById('modalQrImage').src = `https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=${uniqueId}`;
        });
    </script>
</body>
</html>