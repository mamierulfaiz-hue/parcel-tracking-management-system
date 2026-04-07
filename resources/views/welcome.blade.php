<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | Parcel Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border: none;
            border-radius: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        .login-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        .icon-box {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 20px;
        }
    </style>
</head>
<body>

    <div class="container text-center">
        
        <div class="mb-5">
            <h1 class="fw-bold text-dark display-5"><i class="bi bi-box-seam-fill text-primary"></i> Parcel System</h1>
            <p class="text-muted fs-5">Kolej Kediaman Parcel Management</p>
        </div>

        <div class="row justify-content-center g-4">
            
            <div class="col-md-5 col-lg-4">
                <a href="/student/login" class="card p-5 login-card shadow-sm text-center">
                    <div class="icon-box bg-primary text-white">
                        <i class="bi bi-person-workspace fs-1"></i>
                    </div>
                    <h3 class="fw-bold">Student Portal</h3>
                    <p class="text-muted mb-0">Check parcels, generate QR codes, and make payments.</p>
                </a>
            </div>

            <div class="col-md-5 col-lg-4">
                <a href="/login" class="card p-5 login-card shadow-sm text-center">
                    <div class="icon-box bg-dark text-white">
                        <i class="bi bi-shield-lock-fill fs-1"></i>
                    </div>
                    <h3 class="fw-bold">Admin Login</h3>
                    <p class="text-muted mb-0">Manage parcels, track collections, and scan codes.</p>
                </a>
            </div>

        </div>

        <div class="mt