<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout | Smart Parcel</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            background-color: #f8fafc; 
            font-family: 'Inter', sans-serif; 
            color: #334155;
            -webkit-font-smoothing: antialiased;
        }

        /* --- Clean Navbar --- */
        .navbar-custom {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 15px 0;
        }
        .brand-logo { font-weight: 700; font-size: 1.25rem; color: #0f172a; letter-spacing: -0.5px; }

        /* --- Payment Container --- */
        .checkout-container {
            max-width: 1000px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 40px -10px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        /* --- Clickable Payment Cards --- */
        .payment-method {
            display: block;
            position: relative;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #ffffff;
        }
        .payment-method:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
        }
        
        /* Hide default radio buttons */
        .btn-check { position: absolute; opacity: 0; z-index: -1; }

        /* Active State Styling */
        .btn-check:checked + .payment-method {
            border-color: #0d6efd;
            background-color: #eff6ff;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.1);
        }
        
        .check-icon {
            display: none;
            color: #0d6efd;
            font-size: 1.2rem;
        }
        .btn-check:checked + .payment-method .check-icon { display: block; }
        .btn-check:checked + .payment-method .uncheck-icon { display: none; }
        
        .uncheck-icon {
            color: #cbd5e1;
            font-size: 1.2rem;
        }

        /* Payment Icons/Logos Colors */
        .icon-tng { color: #005BBB; }     /* Touch 'n Go Blue */
        .icon-maybank { color: #FFcc00; } /* Maybank Yellow */
        .icon-cimb { color: #7A0026; }    /* CIMB Red */
        .icon-card { color: #475569; }    /* Generic Gray */

        /* --- Summary Section --- */
        .summary-panel {
            background-color: #f8fafc;
            border-left: 1px solid #e2e8f0;
            padding: 40px;
            height: 100%;
        }
        .summary-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        /* Submit Button */
        .btn-pay {
            background: #0f172a;
            color: white;
            padding: 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            border: none;
            transition: 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            width: 100%;
        }
        .btn-pay:hover {
            background: #1e293b;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.15);
        }

        /* Mobile Adjustments */
        @media (max-width: 991px) {
            .summary-panel { border-left: none; border-bottom: 1px solid #e2e8f0; padding: 25px; }
            .checkout-container { margin: 20px 15px; }
            .payment-panel { padding: 25px !important; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-custom sticky-top">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="navbar-brand brand-logo d-flex align-items-center gap-2" href="/student/dashboard">
                <i class="fa-solid fa-box text-primary"></i> SmartParcel
            </a>
            <a href="/student/dashboard" class="btn btn-light border btn-sm rounded-pill px-3 fw-medium text-secondary shadow-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Cancel
            </a>
        </div>
    </nav>

    <div class="container px-0 px-md-3">
        <div class="checkout-container">
            <div class="row g-0 flex-column-reverse flex-lg-row">
                
                <div class="col-lg-7 payment-panel" style="padding: 40px;">
                    <div class="mb-4 pb-2 border-bottom">
                        <h4 class="fw-bold text-dark mb-1">Select Payment Method</h4>
                        <p class="text-muted small mb-0">All transactions are secure and encrypted.</p>
                    </div>
                    
                    <form action="/student/process-payment/{{ $parcel->id }}" method="POST" id="paymentForm">
                        @csrf

                        <div class="d-grid gap-3">
                            
                            <input type="radio" class="btn-check" name="bank" id="tng" value="tng" checked>
                            <label class="payment-method d-flex align-items-center justify-content-between" for="tng">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="fs-3 icon-tng"><i class="fa-solid fa-wallet"></i></div>
                                    <div>
                                        <div class="fw-bold text-dark">Touch 'n Go eWallet</div>
                                        <div class="text-muted" style="font-size: 12px;">Pay seamlessly using your TNG app</div>
                                    </div>
                                </div>
                                <div>
                                    <i class="fa-regular fa-circle uncheck-icon"></i>
                                    <i class="fa-solid fa-circle-check check-icon"></i>
                                </div>
                            </label>

                            <input type="radio" class="btn-check" name="bank" id="maybank" value="maybank">
                            <label class="payment-method d-flex align-items-center justify-content-between" for="maybank">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="fs-3 icon-maybank"><i class="fa-solid fa-building-columns"></i></div>
                                    <div>
                                        <div class="fw-bold text-dark">Maybank2u</div>
                                        <div class="text-muted" style="font-size: 12px;">Direct bank transfer via FPX</div>
                                    </div>
                                </div>
                                <div>
                                    <i class="fa-regular fa-circle uncheck-icon"></i>
                                    <i class="fa-solid fa-circle-check check-icon"></i>
                                </div>
                            </label>

                            <input type="radio" class="btn-check" name="bank" id="cimb" value="cimb">
                            <label class="payment-method d-flex align-items-center justify-content-between" for="cimb">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="fs-3 icon-cimb"><i class="fa-solid fa-building-columns"></i></div>
                                    <div>
                                        <div class="fw-bold text-dark">CIMB Clicks</div>
                                        <div class="text-muted" style="font-size: 12px;">Direct bank transfer via FPX</div>
                                    </div>
                                </div>
                                <div>
                                    <i class="fa-regular fa-circle uncheck-icon"></i>
                                    <i class="fa-solid fa-circle-check check-icon"></i>
                                </div>
                            </label>

                            <input type="radio" class="btn-check" name="bank" id="card" value="card">
                            <label class="payment-method d-flex align-items-center justify-content-between" for="card">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="fs-3 icon-card"><i class="fa-regular fa-credit-card"></i></div>
                                    <div>
                                        <div class="fw-bold text-dark">Credit / Debit Card</div>
                                        <div class="text-muted" style="font-size: 12px;">Visa, Mastercard, or UnionPay</div>
                                    </div>
                                </div>
                                <div>
                                    <i class="fa-regular fa-circle uncheck-icon"></i>
                                    <i class="fa-solid fa-circle-check check-icon"></i>
                                </div>
                            </label>

                        </div>
                    </form>
                </div>

                <div class="col-lg-5 summary-panel">
                    <h5 class="fw-bold text-dark mb-4">Order Summary</h5>
                    
                    <div class="summary-box">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted fw-medium">Item Description</span>
                            <span class="fw-bold text-dark text-end">Parcel Retrieval Fee</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted fw-medium">Tracking Number</span>
                            <span class="fw-bold text-primary text-end text-break" style="font-size: 14px;">{{ $parcel->tracking_number }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted fw-medium">Date Received</span>
                            <span class="fw-bold text-dark text-end" style="font-size: 13px;">{{ $parcel->created_at->format('d M Y, h:i A') }}</span>
                        </div>
                        <hr class="my-3 text-muted">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark fs-5">Total to Pay</span>
                            <span class="h3 fw-bold text-primary mb-0">RM 1.00</span>
                        </div>
                    </div>

                    <button type="submit" form="paymentForm" class="btn-pay shadow-sm">
                        <i class="fa-solid fa-lock text-white-50"></i> Pay RM 1.00
                    </button>
                    
                    <div class="text-center mt-4 text-muted" style="font-size: 12px;">
                        <i class="fa-solid fa-shield-halved me-1 text-success"></i> Secured by ParcelPay System.
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>