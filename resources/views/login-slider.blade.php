<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Portal | Smart Parcel System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #eef2f6 0%, #d9e2ec 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        /* Top Icon Styling */
        .icon-circle {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 20px;
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
            transition: transform 0.3s ease;
        }

        /* Modern Pill Toggle Button */
        .toggle-box {
            background: #f1f5f9;
            border-radius: 50px;
            padding: 5px;
            display: flex;
            position: relative;
            margin-bottom: 30px;
            cursor: pointer;
        }

        .toggle-btn {
            flex: 1;
            text-align: center;
            padding: 10px 0;
            font-weight: 600;
            font-size: 14px;
            color: #6c757d;
            z-index: 2;
            transition: color 0.3s ease;
        }

        .toggle-btn.active {
            color: #fff;
        }

        /* The sliding blue background inside the toggle */
        .toggle-slider {
            position: absolute;
            top: 5px;
            left: 5px;
            width: calc(50% - 5px);
            height: calc(100% - 10px);
            background: #0d6efd;
            border-radius: 50px;
            transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
            z-index: 1;
        }

        /* Move slider to the right when Admin is active */
        .admin-active .toggle-slider {
            transform: translateX(100%);
        }

        /* Form Inputs */
        .form-floating > label {
            color: #8898aa;
            font-size: 14px;
        }

        .form-control {
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            background-color: #f8fafc;
            padding: 1rem 0.75rem;
        }

        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
            background-color: #fff;
        }

        /* Submit Button */
        .btn-submit {
            background: #0d6efd;
            color: white;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            font-size: 15px;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            border: none;
        }

        .btn-submit:hover {
            background: #0b5ed7;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(13, 110, 253, 0.25);
        }

        /* Smooth fade for form switching */
        .form-section {
            display: none;
            animation: fadeIn 0.4s ease forwards;
        }

        .form-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .password-eye {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
        }
        
        .password-eye:hover { color: #333; }

        /* Responsive tweaks */
        @media (max-width: 400px) {
            .login-card { padding: 30px 20px; }
        }
    </style>
</head>
<body>

    <div class="login-card">
        
        <div class="icon-circle" id="headerIcon">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div class="text-center mb-4">
            <h4 class="fw-bold mb-1 text-dark" id="headerTitle">Student Portal</h4>
            <p class="text-muted small" style="font-size: 13px;">Login to access your parcel dashboard</p>
        </div>

        <div class="toggle-box" id="toggleBox" onclick="switchTab()">
            <div class="toggle-slider"></div>
            <div class="toggle-btn active" id="btnStudent">Student</div>
            <div class="toggle-btn" id="btnAdmin">Admin</div>
        </div>

        <div class="form-section active" id="formStudent">
            <form action="/student/login" method="POST">
                @csrf
                <div class="form-floating mb-3">
                    <input type="text" name="student_id" class="form-control" id="studentId" placeholder="S12345" value="{{ old('student_id') }}" required>
                    <label for="studentId"><i class="bi bi-person-badge me-2"></i>Student ID</label>
                </div>
                
                <div class="form-floating mb-4 position-relative">
                    <input type="password" name="password" class="form-control" id="studentPassword" placeholder="Password" required>
                    <label for="studentPassword"><i class="bi bi-lock me-2"></i>Password</label>
                    <i class="bi bi-eye-slash password-eye" onclick="toggleVisibility('studentPassword', this)"></i>
                </div>

                <button type="submit" class="btn btn-submit w-100">Login as Student <i class="bi bi-arrow-right-short ms-1"></i></button>
            </form>
        </div>

        <div class="form-section" id="formAdmin">
            <form action="/login" method="POST">
                @csrf
                <div class="form-floating mb-3">
                    <input type="email" name="email" class="form-control" id="adminEmail" placeholder="admin@example.com" value="{{ old('email') }}" required>
                    <label for="adminEmail"><i class="bi bi-envelope me-2"></i>Admin Email</label>
                </div>
                
                <div class="form-floating mb-4 position-relative">
                    <input type="password" name="password" class="form-control" id="adminPassword" placeholder="Password" required>
                    <label for="adminPassword"><i class="bi bi-lock me-2"></i>Password</label>
                    <i class="bi bi-eye-slash password-eye" onclick="toggleVisibility('adminPassword', this)"></i>
                </div>

                <button type="submit" class="btn btn-submit w-100" style="background: #212529;">Login as Admin <i class="bi bi-arrow-right-short ms-1"></i></button>
            </form>
        </div>

    </div>

    <script>
        const toggleBox = document.getElementById('toggleBox');
        const formStudent = document.getElementById('formStudent');
        const formAdmin = document.getElementById('formAdmin');
        const btnStudent = document.getElementById('btnStudent');
        const btnAdmin = document.getElementById('btnAdmin');
        const headerIcon = document.getElementById('headerIcon');
        const headerTitle = document.getElementById('headerTitle');

        let isStudent = true;

        // Smart Form Switching Logic
        function switchTab(forceAdmin = false) {
            if (forceAdmin || isStudent) {
                // Switch to Admin
                toggleBox.classList.add('admin-active');
                btnStudent.classList.remove('active');
                btnAdmin.classList.add('active');
                
                formStudent.classList.remove('active');
                formAdmin.classList.add('active');
                
                headerIcon.innerHTML = '<i class="bi bi-shield-lock-fill"></i>';
                headerIcon.style.background = 'linear-gradient(135deg, #212529 0%, #495057 100%)';
                headerIcon.style.boxShadow = '0 5px 15px rgba(33, 37, 41, 0.3)';
                headerTitle.innerText = "Admin Access";
                isStudent = false;
            } else {
                // Switch to Student
                toggleBox.classList.remove('admin-active');
                btnAdmin.classList.remove('active');
                btnStudent.classList.add('active');
                
                formAdmin.classList.remove('active');
                formStudent.classList.add('active');
                
                headerIcon.innerHTML = '<i class="bi bi-mortarboard-fill"></i>';
                headerIcon.style.background = 'linear-gradient(135deg, #0d6efd 0%, #0043a8 100%)';
                headerIcon.style.boxShadow = '0 5px 15px rgba(13, 110, 253, 0.3)';
                headerTitle.innerText = "Student Portal";
                isStudent = true;
            }
        }

        // Show/Hide Password Feature
        function toggleVisibility(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            }
        }

        // Auto-switch to Admin tab if the page reloaded due to an Admin login error
        @if(old('email'))
            switchTab(true);
        @endif

        // SweetAlert2 Error Intercept
        @if($errors->has('msg'))
            Swal.fire({
                icon: 'error',
                title: 'Access Denied',
                text: "{{ $errors->first('msg') }}",
                confirmButtonColor: isStudent ? '#0d6efd' : '#212529',
                customClass: { popup: 'rounded-4' }
            });
        @endif
    </script>
</body>
</html>