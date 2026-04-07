<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment | Parcel System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        body { background-color: #f4f6f9; }
        
        .payment-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 30px 0;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.15);
        }

        .payment-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .bank-option {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        /* Highlight the selected bank */
        .btn-check:checked + .bank-option {
            border-color: #0d6efd;
            background-color: #f0f7ff;
            color: #0d6efd;
            font-weight: bold;
        }

        .summary-box {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
    </style>
</head>
<body>

    <div class="payment-header text-center">
        <h3 class="fw-bold mb-0"><i class="fa-solid fa-lock"></i> Secure Payment Gateway</h3>
        <p class="opacity-75 mb-0">Complete your payment to release your parcel</p>
    </div>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card payment-card">
                    <div class="card-body p-0">
                        <div class="row g-0">
                            
                            <div class="col-md-7 p-4 bg-white">
                                <h5 class="fw-bold mb-4">Choose Payment Method</h5>
                                
                                <form action="/student/process-payment/{{ $parcel->id }}" method="POST" id="paymentForm">
                                    @csrf

                                    <div class="d-grid gap-3">
                                        
                                        <input type="radio" class="btn-check" name="bank" id="maybank" checked>
                                        <label class="bank-option d-flex align-items-center justify-content-between" for="maybank">
                                            <span><i class="fa-solid fa-building-columns me-2"></i> Maybank2u</span>
                                            <i class="fa-regular fa-circle-check"></i>
                                        </label>

                                        <input type="radio" class="btn-check" name="bank" id="cimb">
                                        <label class="bank-option d-flex align-items-center justify-content-between" for="cimb">
                                            <span><i class="fa-solid fa-building-columns me-2"></i> CIMB Clicks</span>
                                            <i class="fa-regular fa-circle-check"></i>
                                        </label>

                                        <input type="radio" class="btn-check" name="bank" id="card">
                                        <label class="bank-option d-flex align-items-center justify-content-between" for="card">
                                            <span><i class="fa-regular fa-credit-card me-2"></i> Credit / Debit Card</span>
                                            <i class="fa-regular fa-circle-check"></i>
                                        </label>
                                    </div>

                                </form>
                            </div>

                            <div class="col-md-5 p-4" style="background-color: #f8faff; border-left: 1px solid #eee;">
                                <h5 class="fw-bold mb-4">Order Summary</h5>
                                
                                <div class="summary-box mb-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Item:</span>
                                        <span class="fw-bold">Parcel Retrieval</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Tracking No:</span>
                                        <span class="fw-bold text-break" style="font-size: 14px;">{{ $parcel->tracking_number }}</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold">Total Amount</span>
                                        <span class="h4 fw-bold text-primary mb-0">RM 1.00</span>
                                    </div>
                                </div>

                                <button type="submit" form="paymentForm" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm mb-3">
                                    Pay RM 1.00 Now <i class="fa-solid fa-lock ms-2"></i>
                                </button>

                                <a href="/student/dashboard" class="btn btn-outline-secondary w-100 rounded-pill border-0">
                                    Cancel & Return
                                </a>

                            </div>

                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4 text-muted">
                    <small><i class="fa-solid fa-shield-halved"></i> Secured by ParcelPay System. FYP Project Demo.</small>
                </div>

            </div>
        </div>
    </div>

</body>
</html>