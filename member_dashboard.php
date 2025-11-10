<?php
session_start();
include('config.php');

if (!isset($_SESSION['email']) || ($_SESSION['role'] != 'member' && ($_SESSION['role'] != 'student' || ($_SESSION['status'] ?? 'pending') != 'approved'))) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$name = $_SESSION['name'] ?? 'Member';
$msg = "";

// Get user info
$user = $conn->query("SELECT id FROM users WHERE email='$email'")->fetch_assoc();
$user_id = $user['id'];

// Get notifications
$notifications = $conn->query("SELECT * FROM notifications WHERE user_id=$user_id AND is_read=0 ORDER BY created_at DESC");

// Handle Join Requests
if (isset($_GET['join_role'])) {
    $role_id = intval($_GET['join_role']);
    $event_id = $conn->query("SELECT event_id FROM event_roles WHERE id=$role_id")->fetch_assoc()['event_id'];
    $check = $conn->query("SELECT * FROM event_requests WHERE user_id=$user_id AND event_id=$event_id");
    if ($check->num_rows > 0) {
        $msg = "<div class='msg error'>⚠️ You already joined or have a pending request for this event.</div>";
    } else {
        $conn->query("INSERT INTO event_requests (user_id,event_id,role_id,status) VALUES ($user_id,$event_id,$role_id,'pending')");
        $msg = "<div class='msg success'>✅ Request sent successfully!</div>";
    }
}

$events = $conn->query("SELECT * FROM events WHERE status='ongoing'");
$myApplications = $conn->query("
    SELECT er.*, e.event_name, e.event_date, e.event_time, er.status, r.role_name 
    FROM event_requests er 
    JOIN events e ON er.event_id = e.id 
    JOIN event_roles r ON er.role_id = r.id 
    WHERE er.user_id = $user_id 
    ORDER BY er.requested_at DESC
");

$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'events';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Portal | Gerakan Pengguna Siswa UTM</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: white;
            color: #2c3e50;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            background: white;
            padding: 20px 40px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #1a5490;
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
        
        .header-text h1 {
            font-size: 24px;
            font-weight: bold;
            color: #1a5490;
            margin-bottom: 2px;
        }
        
        .header-text .subtitle {
            font-size: 14px;
            color: #666;
        }
        
        .header-controls {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .btn-user {
            padding: 8px 16px;
            border-radius: 20px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            background: #e8f5e9;
            color: #27ae60;
            text-decoration: none;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-member {
            background: #27ae60;
            color: white;
        }
        
        .btn-icon {
            padding: 8px 16px;
            border-radius: 20px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-about {
            background: #2980b9;
            color: white;
        }
        
        .btn-about:hover {
            background: #1c5980;
        }
        
        .btn-logout {
            background: transparent;
            color: #2c3e50;
            border: 1px solid #ddd;
        }
        
        .btn-logout:hover {
            background: #f5f7fa;
        }
        
        .header-divider {
            height: 2px;
            background: #27ae60;
            width: 100%;
        }
        
        .welcome-section {
            padding: 30px 40px;
            background: white;
        }
        
        .welcome-title {
            font-size: 28px;
            font-weight: 600;
            color: #27ae60;
            margin-bottom: 10px;
        }
        
        .welcome-text {
            font-size: 14px;
            color: #666;
        }
        
        .nav-tabs-container {
            display: flex;
            justify-content: center;
            padding: 20px 40px;
            background: white;
        }
        
        .nav-tabs {
            display: flex;
            gap: 0;
            background: #f5f7fa;
            border-radius: 25px;
            padding: 4px;
            border: 1px solid #ddd;
        }
        
        .nav-tab {
            padding: 12px 30px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
            border-radius: 20px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .nav-tab:hover {
            color: #2c3e50;
        }
        
        .nav-tab.active {
            background: white;
            color: #2c3e50;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .main-content {
            flex: 1;
            padding: 40px;
            display: flex;
            justify-content: center;
        }
        
        .content-card {
            background: white;
            border-radius: 16px;
            padding: 60px 40px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .empty-icon {
            font-size: 80px;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .empty-title {
            font-size: 18px;
            color: #999;
            margin-bottom: 10px;
        }
        
        .empty-text {
            font-size: 14px;
            color: #bbb;
        }
        
        .event-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .event-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .event-info {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .event-description {
            font-size: 14px;
            color: #555;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .roles-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .roles-table th {
            background: #27ae60;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 14px;
            font-weight: 600;
        }
        
        .roles-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }
        
        .btn-join {
            background: #27ae60;
            color: white;
            padding: 6px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            display: inline-block;
        }
        
        .btn-join:hover {
            background: #229954;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-full {
            background: #f5f5f5;
            color: #999;
        }
        
        .application-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .application-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .application-info {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .footer {
            background: #1e8449;
            color: white;
            text-align: center;
            padding: 25px 20px;
            margin-top: auto;
        }
        
        .footer-text {
            font-size: 14px;
        }
        
        .msg {
            margin: 15px 0;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .tab-content {
            display: none;
            width: 100%;
            max-width: 900px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                gap: 15px;
            }
            
            .header-controls {
                flex-wrap: wrap;
            }
            
            .welcome-section, .main-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-top">
            <div class="logo-section">
                <div class="logo-circle">GERAKAN PENGGUNA SISWA UTM</div>
                <div class="header-text">
                    <h1>Gerakan Pengguna Siswa UTM</h1>
                    <div class="subtitle">Member Portal</div>
                </div>
            </div>
            <div class="header-controls">
                <a href="#" class="btn-user">
                    <span>👤</span>
                    <span><?= htmlspecialchars(strtolower($name)) ?></span>
                </a>
                <span class="badge badge-member">Member</span>
                <a href="about.php" class="btn-icon btn-about">
                    <span>ℹ️</span>
                    <span>About GPS</span>
                </a>
                <a href="logout.php" class="btn-icon btn-logout">
                    <span>→</span>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        <div class="header-divider"></div>
    </header>
    
    <div class="welcome-section">
        <h2 class="welcome-title">Welcome back, <?= htmlspecialchars($name) ?>!</h2>
        <p class="welcome-text">Explore upcoming events and join as crew to gain valuable experience and contribute to GPS UTM.</p>
    </div>
    
    <div class="nav-tabs-container">
        <div class="nav-tabs">
            <a href="?tab=events" class="nav-tab <?= $activeTab == 'events' ? 'active' : '' ?>">
                <span>📅</span>
                <span>Events</span>
            </a>
            <a href="?tab=applications" class="nav-tab <?= $activeTab == 'applications' ? 'active' : '' ?>">
                <span>📄</span>
                <span>My Applications</span>
            </a>
        </div>
    </div>
    
    <div class="main-content">
        <?= $msg ?>
        
        <!-- Events Tab -->
        <div class="tab-content <?= $activeTab == 'events' ? 'active' : '' ?>">
            <?php if ($events->num_rows > 0): ?>
                <?php while($e = $events->fetch_assoc()): ?>
                    <div class="event-card">
                        <h3 class="event-title"><?= htmlspecialchars($e['event_name']) ?></h3>
                        <div class="event-info">
                            <strong>Date:</strong> <?= $e['event_date'] ?> | 
                            <strong>Time:</strong> <?= $e['event_time'] ?> | 
                            <strong>Venue:</strong> <?= htmlspecialchars($e['location']) ?>
                        </div>
                        <div class="event-description">
                            <?= nl2br(htmlspecialchars($e['description'])) ?>
                        </div>
                        <table class="roles-table">
                            <thead>
                                <tr>
                                    <th>Position</th>
                                    <th>Slots</th>
                                    <th>Approved Members</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $roles = $conn->query("SELECT * FROM event_roles WHERE event_id={$e['id']}");
                                while($r = $roles->fetch_assoc()):
                                    $role_id = $r['id'];
                                    $role_name = $r['role_name'];
                                    $slots = $r['slots'];
                                    $approved = $conn->query("SELECT COUNT(*) AS c FROM event_requests WHERE role_id=$role_id AND status='approved'")->fetch_assoc()['c'];
                                    $members_q = $conn->query("SELECT u.name FROM event_requests er JOIN users u ON er.user_id=u.id WHERE er.role_id=$role_id AND er.status='approved'");
                                    $names = [];
                                    while($m = $members_q->fetch_assoc()) {
                                        $names[] = $m['name'] == $name ? "<strong style='color:#27ae60;'>" . htmlspecialchars($m['name']) . " (You)</strong>" : htmlspecialchars($m['name']);
                                    }
                                    $display = $names ? implode(', ', $names) : '<i style="color:#999;">None</i>';
                                    $st = $conn->query("SELECT er.status FROM event_requests er WHERE er.role_id=$role_id AND er.user_id=$user_id");
                                    $status = $st->num_rows > 0 ? $st->fetch_assoc()['status'] : '';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($role_name) ?></td>
                                    <td><?= $approved ?> / <?= $slots ?></td>
                                    <td><?= $display ?></td>
                                    <td>
                                        <?php
                                        if($status == 'approved') {
                                            echo "<span class='status-badge status-approved'>Approved</span>";
                                        } elseif($status == 'pending') {
                                            echo "<span class='status-badge status-pending'>Pending</span>";
                                        } elseif($status == 'rejected') {
                                            echo "<span class='status-badge status-rejected'>Rejected</span>";
                                        } elseif($approved >= $slots) {
                                            echo "<span class='status-badge status-full'>Full</span>";
                                        } else {
                                            echo "<a href='?join_role=$role_id&tab=events' class='btn-join'>Join</a>";
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="content-card">
                    <div class="empty-icon">📅</div>
                    <div class="empty-title">No upcoming events at the moment.</div>
                    <div class="empty-text">Check back later for new opportunities!</div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- My Applications Tab -->
        <div class="tab-content <?= $activeTab == 'applications' ? 'active' : '' ?>">
            <?php if ($myApplications->num_rows > 0): ?>
                <?php while($app = $myApplications->fetch_assoc()): ?>
                    <div class="application-card">
                        <h3 class="application-title"><?= htmlspecialchars($app['event_name']) ?></h3>
                        <div class="application-info">
                            <strong>Position:</strong> <?= htmlspecialchars($app['role_name']) ?>
                        </div>
                        <div class="application-info">
                            <strong>Date:</strong> <?= $app['event_date'] ?> | <strong>Time:</strong> <?= $app['event_time'] ?>
                        </div>
                        <div style="margin-top: 10px;">
                            <?php
                            $statusClass = 'status-' . $app['status'];
                            echo "<span class='status-badge $statusClass'>" . ucfirst($app['status']) . "</span>";
                            ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="content-card">
                    <div class="empty-icon">📄</div>
                    <div class="empty-title">No applications yet.</div>
                    <div class="empty-text">Join events to see your applications here!</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <footer class="footer">
        <p class="footer-text">© 2025 Gerakan Pengguna Siswa UTM. All rights reserved.</p>
    </footer>
</body>
</html>
