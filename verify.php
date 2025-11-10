<?php
include('config.php');
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$message = '';

if (isset($_POST['verify'])) {
    $tac = trim($_POST['tac']);

    if (empty($tac)) {
        $error = "Please enter your TAC code.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            $storedTac = $user['tac_code'] ?? $user['TAC_CODE'] ?? null;
            $tacExpiry = $user['tac_expiry'] ?? $user['TAC_EXPIRY'] ?? null;
            $role = strtolower($user['role'] ?? $user['ROLE'] ?? 'student');
            $status = strtolower($user['status'] ?? $user['STATUS'] ?? 'pending');

            // Check TAC and expiry
            if ($storedTac && $storedTac == $tac && strtotime($tacExpiry) > time()) {

                // Clear TAC for security
                $clear = $conn->prepare("UPDATE users SET tac_code=NULL, tac_expiry=NULL WHERE email=?");
                $clear->bind_param("s", $email);
                $clear->execute();

                $_SESSION['role'] = $role;
                $_SESSION['name'] = $user['name'] ?? $user['NAME'] ?? 'User';

                // Redirect based on role
                if ($role == 'admin') {
                    header("Location: admin_dashboard.php");
                } elseif ($role == 'member' && $status == 'approved') {
                    header("Location: member_dashboard.php");
                } else {
                    header("Location: student_dashboard.php");
                }
                exit();

            } else {
                $error = "Invalid or expired TAC. Please try again.";
            }
        } else {
            $error = "User not found. Please log in again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify TAC | Gerakan Pengguna Siswa UTM</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: #2c3e50;
        }
        
        .header {
            background: white;
            padding: 20px 40px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header-top {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .back-link {
            color: #2980b9;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: #1c5980;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2980b9 0%, #1c5980 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            padding: 5px;
            line-height: 1.2;
        }
        
        .header-text {
            display: flex;
            flex-direction: column;
        }
        
        .main-title {
            font-size: 24px;
            font-weight: bold;
            color: #1a5490;
            margin-bottom: 2px;
        }
        
        .subtitle {
            font-size: 16px;
            color: #666;
        }
        
        .header-divider {
            height: 2px;
            background: #2980b9;
            width: 100%;
        }
        
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .form-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .form-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #e3f2fd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin: 0 auto 20px;
            color: #2980b9;
        }
        
        .form-title {
            text-align: center;
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .form-subtitle {
            text-align: center;
            font-size: 14px;
            color: #666;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #2c3e50;
            text-align: center;
        }
        
        .tac-input {
            width: 100%;
            padding: 16px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 24px;
            text-align: center;
            letter-spacing: 8px;
            font-weight: 600;
            transition: border-color 0.3s;
            max-width: 280px;
            margin: 0 auto;
            display: block;
        }
        
        .tac-input:focus {
            outline: none;
            border-color: #2980b9;
        }
        
        .tac-input::placeholder {
            letter-spacing: 4px;
            font-size: 18px;
            color: #999;
        }
        
        .submit-btn {
            width: 100%;
            padding: 14px;
            background: #2980b9;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .submit-btn:hover {
            background: #1c5980;
        }
        
        .msg {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            text-align: center;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-to-login a {
            color: #2980b9;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .back-to-login a:hover {
            color: #1c5980;
            text-decoration: underline;
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 25px 20px;
        }
        
        .footer-text {
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
            }
            
            .form-card {
                padding: 30px 20px;
            }
            
            .tac-input {
                font-size: 20px;
                letter-spacing: 6px;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-top">
            <a href="login.php" class="back-link">← Back</a>
            <div class="logo-section">
                <div class="logo-circle">GERAKAN PENGGUNA SISWA UTM</div>
                <div class="header-text">
                    <div class="main-title">Gerakan Pengguna Siswa UTM</div>
                    <div class="subtitle">Student Portal</div>
                </div>
            </div>
        </div>
        <div class="header-divider"></div>
    </header>
    
    <div class="main-content">
        <div class="form-card">
            <div class="form-icon">🔐</div>
            <h2 class="form-title">Verify TAC Code</h2>
            <p class="form-subtitle">Enter the 6-digit code sent to your email</p>
            
            <?php if (isset($error)): ?>
                <div class="msg error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">TAC Code</label>
                    <input type="text" name="tac" id="tac" class="tac-input" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autocomplete="off">
                </div>
                
                <button type="submit" name="verify" class="submit-btn">Verify</button>
            </form>
            
            <div class="back-to-login">
                <a href="login.php">← Back to Login</a>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <p class="footer-text">© 2025 Gerakan Pengguna Siswa UTM. All rights reserved.</p>
    </footer>
    
    <script>
        // Auto-focus and format TAC input
        document.getElementById('tac').focus();
        
        // Only allow numbers
        document.getElementById('tac').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // Auto-submit when 6 digits are entered
        document.getElementById('tac').addEventListener('input', function(e) {
            if (this.value.length === 6) {
                // Optional: auto-submit after a short delay
                // setTimeout(() => {
                //     this.form.submit();
                // }, 500);
            }
        });
    </script>
</body>
</html>
