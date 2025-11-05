<?php
include('config.php');
session_start();

// --- Access Control: Students Only ---
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$name = $_SESSION['name'] ?? 'Student';

// Get student record to check status
$stmt = $conn->prepare("SELECT status FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$status = strtolower($user['status'] ?? 'pending');
?>

<!DOCTYPE html>
<html>
<head>
    <title>GPSphere | Student Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f7;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            height: 100vh;
            margin: 0;
            padding-top: 50px;
        }
        .container {
            background: white;
            padding: 40px 60px;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
            text-align: center;
            width: 360px;
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .status {
            background: #fdf3cd;
            color: #7d6608;
            padding: 10px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .approved {
            background: #d4edda;
            color: #155724;
        }
        a.logout {
            display: inline-block;
            margin-top: 15px;
            background: #34495e;
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
        }
        a.logout:hover {
            background: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome, <?php echo $name; ?> 👋</h2>
        <p>Your registered email: <b><?php echo $email; ?></b></p>

        <?php if ($status == 'approved'): ?>
            <div class="status approved">
                🎉 Congratulations! Your membership has been approved. 
                <br>You can now access the <a href="member_dashboard.php">Member Dashboard</a>.
            </div>
        <?php else: ?>
            <div class="status">
                ⏳ Your registration is pending approval by the GPS Admin. 
                <br>Please check again later.
            </div>
        <?php endif; ?>

        <a href="logout.php" class="logout">Logout</a>
    </div>
</body>
</html>
