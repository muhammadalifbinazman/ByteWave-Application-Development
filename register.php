<?php
include('config.php');
session_start();

if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    // --- Basic validation ---
    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $msg = "<div class='msg error'>All fields are required.</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "<div class='msg error'>Invalid email format.</div>";
    } elseif ($password !== $confirm) {
        $msg = "<div class='msg error'>Passwords do not match.</div>";
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        $msg = "<div class='msg error'>
                Password must be at least 8 characters and include uppercase, lowercase, number, and symbol.
                </div>";
    } else {
        // --- Check if email already exists ---
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $msg = "<div class='msg error'>Email already registered. Please login.</div>";
        } else {
            // --- Insert new user ---
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = "student";
            $status = "pending";

            $insert = $conn->prepare(
                "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)"
            );
            $insert->bind_param("sssss", $name, $email, $hash, $role, $status);

            if ($insert->execute()) {
                $msg = "<div class='msg success'>
                        Registration successful!<br>
                        Your account is pending approval by the admin.<br>
                        <a href='login.php'>Proceed to Login</a>
                        </div>";
            } else {
                $msg = "<div class='msg error'>Registration failed. Please try again.</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>GPSphere | Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f7;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: #fff;
            padding: 40px 60px;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
            text-align: center;
            width: 360px;
        }
        h2 { color: #2c3e50; margin-bottom: 25px; }
        input {
            display: block;
            width: 100%;
            margin-bottom: 15px;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }
        button {
            background: #2980b9;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover { background: #1c5980; }
        .msg {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
        }
        .error { background: #f8d7da; color: #721c24; }
        .success { background: #d4edda; color: #155724; }
        a { color: #2980b9; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Register for GPSphere</h2>
        <?php if (isset($msg)) echo $msg; ?>
        <form method="POST" action="">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Student Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm" placeholder="Confirm Password" required>
            <button type="submit" name="register">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>
