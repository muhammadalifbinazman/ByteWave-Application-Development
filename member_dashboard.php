<?php
include('config.php');
session_start();

// --- Access Control: Members Only ---
if (!isset($_SESSION['email']) || $_SESSION['role'] != 'member') {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$name  = $_SESSION['name'] ?? 'Member';

// Get member record
$stmt = $conn->prepare("SELECT status FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$status = strtolower($user['status'] ?? 'approved');

// --- Show "first login" success message only once ---
$showWelcome = false;

if (!isset($_SESSION['member_first_login_shown'])) {
    // First time logging in this session
    $showWelcome = true;
    $_SESSION['member_first_login_shown'] = true; // Prevent it showing again
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>GPSphere | Member Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f7;
            padding: 40px;
        }
        h1 { color: #2c3e50; }
        .welcome {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            width: 60%;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            width: 60%;
            margin-top: 20px;
        }
        .logout {
            background: #34495e;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
        .logout:hover { background: #2c3e50; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #2980b9;
            color: white;
        }
        .info {
            color: #555;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <h1>Welcome, <?php echo $name; ?> 👋</h1>
    <p class="info">You are logged in as a <b>GPSphere Member</b> (<?php echo $email; ?>).</p>

    <?php if ($showWelcome): ?>
        <div class="welcome">
            ✅ Your membership has been approved.  
            You can now participate in club activities and view available events.
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>Upcoming Events (Feature for Sprint 2)</h3>
        <p class="info">This section will show available workshops, campaigns, and volunteer opportunities once Sprint 2 starts.</p>

        <table>
            <tr>
                <th>Event Name</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
            <tr>
                <td>Financial Management Workshop</td>
                <td>Coming Soon</td>
                <td><button disabled>Join (Coming Soon)</button></td>
            </tr>
        </table>
    </div>

    <p><a href="logout.php" class="logout">Logout</a></p>
</body>
</html>
