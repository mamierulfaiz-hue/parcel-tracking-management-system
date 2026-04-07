<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Portal | Parcel System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            background-color: #eef2f6;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            height: 100vh;
        }

        .container {
            background-color: #fff;
            border-radius: 25px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            width: 900px;
            max-width: 100%;
            min-height: 550px;
        }

        .container p { font-size: 14px; line-height: 24px; letter-spacing: 0.3px; margin: 20px 0; }
        .container span { font-size: 13px; color: #666; margin-bottom: 15px; display: block;}
        
        .container button {
            background-color: #0d6efd;
            color: #fff;
            font-size: 13px;
            padding: 12px 45px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-top: 15px;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .container button:hover { background-color: #0b5ed7; }
        
        /* HIDDEN BUTTON (Inside Blue Box) */
        .container button.hidden { 
            background-color: transparent; 
            border-color: #fff; 
            color: #fff; /* Ensure text is white */
        }
        .container button.hidden:hover { background-color: rgba(255,255,255,0.1); }

        .container form {
            background-color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 50px;
            height: 100%;
            text-align: center;
        }

        .container input {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            margin: 10px 0;
            padding: 15px;
            font-size: 14px;
            border-radius: 8px;
            width: 100%;
            outline: none;
        }

        /* --- ANIMATION LOGIC --- */
        .form-container {
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.6s ease-in-out;
        }

        .sign-in { left: 0; width: 50%; z-index: 2; }
        .sign-up { left: 0; width: 50%; opacity: 0; z-index: 1; }

        .container.active .sign-in { transform: translateX(100%); }
        .container.active .sign-up { transform: translateX(100%); opacity: 1; z-index: 5; animation: move 0.6s; }

        @keyframes move {
            0%, 49.99% { opacity: 0; z-index: 1; }
            50%, 100% { opacity: 1; z-index: 5; }
        }

        .toggle-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: all 0.6s ease-in-out;
            border-radius: 100px 0 0 100px;
            z-index: 1000;
        }

        .container.active .toggle-container {
            transform: translateX(-100%);
            border-radius: 0 100px 100px 0;
        }

        .toggle {
            background: linear-gradient(to right, #4e73df, #224abe);
            color: #fff;
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: all 0.6s ease-in-out;
        }

        .container.active .toggle { transform: translateX(50%); }

        .toggle-panel {
            position: absolute;
            width: 50%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 30px;
            text-align: center;
            top: 0;
            transform: translateX(0);
            transition: all 0.6s ease-in-out;
        }

        /* --- FIX: FORCE TEXT WHITE IN BLUE BOX --- */
        .toggle-panel h1 { color: #fff !important; margin-bottom: 10px; }
        .toggle-panel p { color: #fff !important; }

        .toggle-left { transform: translateX(-200%); }
        .container.active .toggle-left { transform: translateX(0); }
        .toggle-right { right: 0; transform: translateX(0); }
        .container.active .toggle-right { transform: translateX(200%); }
        
        .error-msg { 
            color: #dc3545; 
            font-size: 13px; 
            margin-top: 5px; 
            background: #ffe6e6; 
            padding: 5px 10px; 
            border-radius: 5px; 
            display: block;
            width: 100%;
        }

        /* GLOBAL H1 (For the white part only) */
        h1 { margin-bottom: 15px; color: #333; }
        
        .social-icons { margin: 15px 0; }
        .social-icons a {
            border: 1px solid #ccc;
            border-radius: 50%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            margin: 0 5px;
            width: 45px;
            height: 45px;
            color: #333;
            text-decoration: none;
            font-size: 18px;
            transition: 0.3s;
        }
        .social-icons a:hover { background-color: #0d6efd; color: white; border-color: #0d6efd; }
    </style>
</head>
<body>

    <div class="container" id="container">
        
        <div class="form-container sign-up">
            <form action="/login" method="POST">
                @csrf
                <h1>Admin Access</h1>
                <div class="social-icons">
                    <a href="#" class="icon"><i class="fa-solid fa-user-shield"></i></a>
                </div>
                <span>Use your admin email and password</span>
                
                <input type="email" name="email" placeholder="Admin Email" required>
                <input type="password" name="password" placeholder="Password" required>
                
                @if($errors->has('msg'))
                    <div class="error-msg">
                        <i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first('msg') }}
                    </div>
                @endif

                <button type="submit">Login</button>
            </form>
        </div>

        <div class="form-container sign-in">
            <form action="/student/login" method="POST">
                @csrf
                <h1>Student Portal</h1>
                <div class="social-icons">
                    <a href="#" class="icon"><i class="fa-solid fa-graduation-cap"></i></a>
                </div>
                <span>Enter your Student ID to continue</span>
                
                <input type="text" name="student_id" placeholder="Student ID (e.g. S12345)" required>
                <input type="password" name="password" placeholder="Password" required>
                
                @if($errors->has('msg'))
                    <div class="error-msg">
                        <i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first('msg') }}
                    </div>
                @endif

                <button type="submit">Login</button>
            </form>
        </div>

        <div class="toggle-container">
            <div class="toggle">
                
                <div class="toggle-panel toggle-left">
                    <h1>Are you a Student?</h1>
                    <p>Access your parcel dashboard, check statuses, and pay fees.</p>
                    <button class="hidden" id="login">Go to Student Login</button>
                </div>

                <div class="toggle-panel toggle-right">
                    <h1>Are you an Admin?</h1>
                    <p>Switch here to manage the parcel system and track inventory.</p>
                    <button class="hidden" id="register">Go to Admin Login</button>
                </div>

            </div>
        </div>

    </div>

    <script>
        const container = document.getElementById('container');
        const registerBtn = document.getElementById('register');
        const loginBtn = document.getElementById('login');

        registerBtn.addEventListener('click', () => {
            container.classList.add("active");
        });

        loginBtn.addEventListener('click', () => {
            container.classList.remove("active");
        });
    </script>

</body>
</html>