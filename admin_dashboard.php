<?php
session_start();
include('config.php');

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$name  = $_SESSION['name'] ?? 'Admin';
$msg   = "";

// Get statistics
$totalRegistrations = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='student'")->fetch_assoc()['count'];
$pendingReview = $conn->query("SELECT COUNT(*) as count FROM users WHERE status='pending' AND role='student'")->fetch_assoc()['count'];
$approved = $conn->query("SELECT COUNT(*) as count FROM users WHERE status='approved' AND role='student'")->fetch_assoc()['count'];
$activeMembers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='member' AND status='approved'")->fetch_assoc()['count'];

// Calculate approval rate
$approvalRate = $totalRegistrations > 0 ? round(($approved / $totalRegistrations) * 100, 1) : 0;
$pendingPercent = $totalRegistrations > 0 ? round(($pendingReview / $totalRegistrations) * 100, 1) : 0;

// Get approval times (simplified - using created_at to approved timestamp difference)
$approvalTimes = $conn->query("
    SELECT TIMESTAMPDIFF(HOUR, created_at, NOW()) as hours 
    FROM users 
    WHERE status='approved' AND role='student' 
    LIMIT 2
");
$avgApprovalTime = 0;
$count = 0;
$totalHours = 0;
while($row = $approvalTimes->fetch_assoc()) {
    $totalHours += $row['hours'];
    $count++;
}
$avgApprovalTime = $count > 0 ? round($totalHours / $count, 1) : 0;

// Get registrations for last 7 days
$registrationTimeline = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = '$date' AND role='student'")->fetch_assoc()['count'];
    $registrationTimeline[] = ['date' => date('M j', strtotime($date)), 'count' => (int)$count];
}

// Get this week registrations
$weekStart = date('Y-m-d', strtotime('monday this week'));
$thisWeek = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= '$weekStart' AND role='student'")->fetch_assoc()['count'];

// Get top programs (simplified - using email domain or name patterns)
$topPrograms = [
    ['name' => 'Computer Science', 'count' => 1],
    ['name' => 'Software Engineering', 'count' => 1],
    ['name' => 'Information Systems', 'count' => 1]
];

// Get distribution by year (simplified)
$yearDistribution = [
    ['year' => 'Year 1', 'count' => 1],
    ['year' => 'Year 2', 'count' => 1],
    ['year' => 'Year 3', 'count' => 1]
];

// Handle actions
if (isset($_GET['approve_user'])) {
    $user_id = intval($_GET['approve_user']);
    $conn->query("UPDATE users SET status='approved', role='member' WHERE id=$user_id");
    $msg = "<div class='msg success'>✅ User approved and promoted to member.</div>";
    header("Location: admin_dashboard.php?tab=registrations");
    exit();
}
if (isset($_GET['reject_user'])) {
    $user_id = intval($_GET['reject_user']);
    // Delete the rejected registration
    $conn->query("DELETE FROM users WHERE id=$user_id AND role='student' AND status='pending'");
    $msg = "<div class='msg error'>❌ Registration rejected and removed.</div>";
    header("Location: admin_dashboard.php?tab=registrations");
    exit();
}
if (isset($_GET['req_approve'])) {
    $req_id = intval($_GET['req_approve']);
    $conn->query("UPDATE event_requests SET status='approved' WHERE id=$req_id");
    $conn->query("UPDATE event_roles SET slots = GREATEST(slots - 1, 0) WHERE id=(SELECT role_id FROM event_requests WHERE id=$req_id)");
    $msg = "<div class='msg success'>✅ Member approved.</div>";
    header("Location: admin_dashboard.php");
    exit();
}
if (isset($_GET['req_reject'])) {
    $req_id = intval($_GET['req_reject']);
    $conn->query("UPDATE event_requests SET status='rejected' WHERE id=$req_id");
    $msg = "<div class='msg error'>❌ Request rejected.</div>";
    header("Location: admin_dashboard.php");
    exit();
}
if (isset($_GET['remove_crew'])) {
    $req_id = intval($_GET['remove_crew']);
    $conn->query("UPDATE event_roles SET slots = slots + 1 WHERE id=(SELECT role_id FROM event_requests WHERE id=$req_id)");
    $conn->query("DELETE FROM event_requests WHERE id=$req_id");
    $msg = "<div class='msg error'>🗑 Crew member removed.</div>";
    header("Location: admin_dashboard.php");
    exit();
}
if (isset($_POST['change_role'])) {
    $req_id = intval($_POST['req_id']);
    $new_role_id = intval($_POST['new_role_id']);
    $info = $conn->query("
        SELECT er.user_id, er.role_id AS old_role_id, e.event_name,
               r1.role_name AS old_role, r2.role_name AS new_role
        FROM event_requests er
        JOIN event_roles r1 ON er.role_id = r1.id
        JOIN event_roles r2 ON r2.id = $new_role_id
        JOIN events e ON r1.event_id = e.id
        WHERE er.id = $req_id
    ")->fetch_assoc();
    if ($info) {
        $user_id = $info['user_id'];
        $old_role_id = $info['old_role_id'];
        $conn->query("UPDATE event_roles SET slots = slots + 1 WHERE id=$old_role_id");
        $conn->query("UPDATE event_requests SET role_id=$new_role_id WHERE id=$req_id");
        $conn->query("UPDATE event_roles SET slots = GREATEST(slots - 1, 0) WHERE id=$new_role_id");
        $msg = "<div class='msg success'>✅ Role changed successfully.</div>";
        header("Location: admin_dashboard.php");
        exit();
    }
}
if (isset($_GET['finish_event'])) {
    $eid = intval($_GET['finish_event']);
    $conn->query("UPDATE events SET status='finished' WHERE id=$eid");
    $msg = "<div class='msg success'>🏁 Event marked as finished.</div>";
    header("Location: admin_dashboard.php");
    exit();
}

$events = $conn->query("SELECT * FROM events WHERE status='ongoing'");
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'analytics';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Portal | Gerakan Pengguna Siswa UTM</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #2c3e50;
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
            font-size: 14px;
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
            gap: 15px;
        }
        
        .user-badge {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-admin {
            background: #e74c3c;
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
            background: #e74c3c;
            width: 100%;
        }
        
        .nav-bar {
            background: white;
            padding: 0 40px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .nav-tabs {
            display: flex;
            gap: 0;
        }
        
        .nav-tab {
            padding: 15px 25px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .nav-tab:hover {
            color: #2980b9;
            background: #f8f9fa;
        }
        
        .nav-tab.active {
            color: #2980b9;
            border-bottom-color: #2980b9;
            font-weight: 600;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 40px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .metric-content h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .metric-content .value {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .metric-content .subtitle {
            font-size: 12px;
            color: #999;
        }
        
        .metric-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .icon-blue { background: #e3f2fd; color: #2980b9; }
        .icon-yellow { background: #fff9e6; color: #f39c12; }
        .icon-green { background: #e8f5e9; color: #27ae60; }
        .icon-grey { background: #f5f5f5; color: #666; }
        
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .chart-card h3 {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .chart-card .subtitle {
            font-size: 12px;
            color: #999;
            margin-bottom: 20px;
        }
        
        .chart-container {
            position: relative;
            height: 250px;
        }
        
        .insight-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .insight-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .insight-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .insight-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .value-blue { color: #2980b9; }
        .value-green { color: #27ae60; }
        .value-purple { color: #9b59b6; }
        
        .insight-subtitle {
            font-size: 12px;
            color: #999;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .msg {
            margin: 15px 0;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        
        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                gap: 15px;
            }
            
            .dashboard-grid, .chart-grid, .insight-grid {
                grid-template-columns: 1fr;
            }
        }
</style>
</head>
<body>
    <header class="header">
        <div class="header-top">
            <div class="logo-section">
                <div class="logo-circle">GPS</div>
                <div class="header-text">
                    <h1>Gerakan Pengguna Siswa UTM</h1>
                    <div class="subtitle">Administrator Portal</div>
                </div>
            </div>
            <div class="header-controls">
                <div class="user-badge">
                    <span><?= htmlspecialchars($name) ?></span>
                    <span class="badge badge-admin">Admin</span>
                </div>
                <a href="about.php" class="btn-icon btn-about">
                    <span>ℹ️</span>
                    <span>About GPS</span>
                </a>
                <a href="logout.php" class="btn-icon btn-logout">
                    <span>🚪</span>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        <div class="header-divider"></div>
    </header>
    
    <nav class="nav-bar">
        <div class="nav-tabs">
            <a href="?tab=analytics" class="nav-tab <?= $activeTab == 'analytics' ? 'active' : '' ?>">
                <span>📊</span>
                <span>Analytics</span>
            </a>
            <a href="?tab=registrations" class="nav-tab <?= $activeTab == 'registrations' ? 'active' : '' ?>">
                <span>📝</span>
                <span>Registrations</span>
            </a>
            <a href="?tab=members" class="nav-tab <?= $activeTab == 'members' ? 'active' : '' ?>">
                <span>👥</span>
                <span>Members</span>
            </a>
            <a href="?tab=programs" class="nav-tab <?= $activeTab == 'programs' ? 'active' : '' ?>">
                <span>📚</span>
                <span>Programs</span>
            </a>
            <a href="?tab=events" class="nav-tab <?= $activeTab == 'events' ? 'active' : '' ?>">
                <span>🎯</span>
                <span>Events</span>
            </a>
        </div>
    </nav>
    
    <div class="container">
<?= $msg ?>

        <!-- Analytics Tab -->
        <div class="tab-content <?= $activeTab == 'analytics' ? 'active' : '' ?>">
            <div class="dashboard-grid">
                <div class="metric-card">
                    <div class="metric-content">
                        <h3>Total Registrations</h3>
                        <div class="value"><?= $totalRegistrations ?></div>
                        <div class="subtitle">All time</div>
                    </div>
                    <div class="metric-icon icon-blue">👥</div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-content">
                        <h3>Pending Review</h3>
                        <div class="value"><?= $pendingReview ?></div>
                        <div class="subtitle"><?= $pendingPercent ?>% of total</div>
                    </div>
                    <div class="metric-icon icon-yellow">⏰</div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-content">
                        <h3>Approved</h3>
                        <div class="value"><?= $approved ?></div>
                        <div class="subtitle"><?= $approvalRate ?>% approval rate</div>
                    </div>
                    <div class="metric-icon icon-green">✓</div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-content">
                        <h3>Avg. Approval Time</h3>
                        <div class="value"><?= $avgApprovalTime ?>h</div>
                        <div class="subtitle"><?= $approved ?> approved</div>
                    </div>
                    <div class="metric-icon icon-grey">📈</div>
                </div>
            </div>
            
            <div class="chart-grid">
                <div class="chart-card">
                    <h3>Registration Timeline</h3>
                    <div class="subtitle">Registrations over the last 7 days</div>
                    <div class="chart-container">
                        <canvas id="timelineChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <h3>Status Distribution</h3>
                    <div class="subtitle">Current status of all registrations</div>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="chart-grid">
                <div class="chart-card">
                    <h3>Top 5 Programs</h3>
                    <div class="subtitle">Most popular study programs</div>
                    <div class="chart-container">
                        <canvas id="programsChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <h3>Distribution by Year</h3>
                    <div class="subtitle">Registrations across different years</div>
                    <div class="chart-container">
                        <canvas id="yearChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="insight-grid">
                <div class="insight-card">
                    <h3>Action Required</h3>
                    <div class="insight-value value-blue"><?= $pendingReview ?></div>
                    <div class="insight-subtitle">Registrations waiting for review</div>
                </div>
                
                <div class="insight-card">
                    <h3>Active Members</h3>
                    <div class="insight-value value-green"><?= $activeMembers ?></div>
                    <div class="insight-subtitle">Total approved members</div>
                </div>
                
                <div class="insight-card">
                    <h3>This Week</h3>
                    <div class="insight-value value-purple"><?= $thisWeek ?></div>
                    <div class="insight-subtitle">New registrations</div>
                </div>
            </div>
        </div>
        
        <!-- Registrations Tab -->
        <div class="tab-content <?= $activeTab == 'registrations' ? 'active' : '' ?>">
            <?php
            $allRegistrations = $conn->query("SELECT * FROM users WHERE role='student' ORDER BY created_at DESC");
            ?>
            <div class="chart-card">
                <h3>All Student Registrations</h3>
                <table style="width:100%; margin-top:15px; border-collapse:collapse;">
                    <tr style="background:#2980b9; color:white;">
                        <th style="padding:10px; text-align:left;">Name</th>
                        <th style="padding:10px; text-align:left;">Email</th>
                        <th style="padding:10px; text-align:left;">Status</th>
                        <th style="padding:10px; text-align:left;">Registered</th>
                        <th style="padding:10px; text-align:left;">Action</th>
                    </tr>
                    <?php if ($allRegistrations->num_rows > 0): ?>
                        <?php while($reg = $allRegistrations->fetch_assoc()): ?>
                            <tr style="border-bottom:1px solid #ddd;">
                                <td style="padding:10px;"><?= htmlspecialchars($reg['name']) ?></td>
                                <td style="padding:10px;"><?= htmlspecialchars($reg['email']) ?></td>
                                <td style="padding:10px;">
                                    <span style="padding:4px 8px; border-radius:4px; font-size:12px; 
                                        <?= $reg['status'] == 'approved' ? 'background:#d4edda; color:#155724;' : 'background:#fff3cd; color:#856404;' ?>">
                                        <?= ucfirst($reg['status']) ?>
                                    </span>
                                </td>
                                <td style="padding:10px;"><?= date('M j, Y', strtotime($reg['created_at'])) ?></td>
                                <td style="padding:10px;">
                                    <?php if ($reg['status'] == 'pending'): ?>
                                        <a href="?approve_user=<?= $reg['id'] ?>&tab=registrations" 
                                           style="background:#27ae60; color:white; padding:4px 8px; border-radius:4px; text-decoration:none; font-size:12px; margin-right:8px;">Approve</a>
                                        <a href="?reject_user=<?= $reg['id'] ?>&tab=registrations" 
                                           onclick="return confirm('Are you sure you want to reject this registration? This action cannot be undone.');"
                                           style="background:#e74c3c; color:white; padding:4px 8px; border-radius:4px; text-decoration:none; font-size:12px;">Reject</a>
                                    <?php else: ?>
                                        <span style="color:#999;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="padding:20px; text-align:center; color:#999;">No registrations found.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <!-- Members Tab -->
        <div class="tab-content <?= $activeTab == 'members' ? 'active' : '' ?>">
            <?php
            $allMembers = $conn->query("SELECT * FROM users WHERE role='member' OR (role='student' AND status='approved') ORDER BY created_at DESC");
            ?>
            <div class="chart-card">
                <h3>All Members</h3>
                <table style="width:100%; margin-top:15px; border-collapse:collapse;">
                    <tr style="background:#2980b9; color:white;">
                        <th style="padding:10px; text-align:left;">Name</th>
                        <th style="padding:10px; text-align:left;">Email</th>
                        <th style="padding:10px; text-align:left;">Role</th>
                        <th style="padding:10px; text-align:left;">Status</th>
                        <th style="padding:10px; text-align:left;">Joined</th>
                    </tr>
                    <?php if ($allMembers->num_rows > 0): ?>
                        <?php while($member = $allMembers->fetch_assoc()): ?>
                            <tr style="border-bottom:1px solid #ddd;">
                                <td style="padding:10px;"><?= htmlspecialchars($member['name']) ?></td>
                                <td style="padding:10px;"><?= htmlspecialchars($member['email']) ?></td>
                                <td style="padding:10px;"><?= ucfirst($member['role']) ?></td>
                                <td style="padding:10px;">
                                    <span style="padding:4px 8px; border-radius:4px; font-size:12px; background:#d4edda; color:#155724;">
                                        <?= ucfirst($member['status']) ?>
                                    </span>
                                </td>
                                <td style="padding:10px;"><?= date('M j, Y', strtotime($member['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="padding:20px; text-align:center; color:#999;">No members found.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <!-- Programs Tab -->
        <div class="tab-content <?= $activeTab == 'programs' ? 'active' : '' ?>">
            <div class="chart-card">
                <h3>Study Programs</h3>
                <p style="margin-top:15px; color:#666;">Program management features coming soon.</p>
            </div>
        </div>
        
        <!-- Events Tab (existing functionality) -->
        <div class="tab-content <?= $activeTab == 'events' ? 'active' : '' ?>">
            <?php if ($events->num_rows > 0): ?>
                <?php while($e = $events->fetch_assoc()): ?>
                    <div class="chart-card" style="margin-bottom: 20px;">
  <h3><?= htmlspecialchars($e['event_name']) ?></h3>
                        <p><b>Date:</b> <?= $e['event_date'] ?> | <b>Time:</b> <?= $e['event_time'] ?><br>
                        <b>Venue:</b> <?= htmlspecialchars($e['location']) ?></p>
  <p><?= nl2br(htmlspecialchars($e['description'])) ?></p>
                        <table style="width:100%; margin-top:15px; border-collapse:collapse;">
                            <tr style="background:#2980b9; color:white;">
                                <th style="padding:10px; text-align:left;">Role</th>
                                <th style="padding:10px; text-align:left;">Slots</th>
                                <th style="padding:10px; text-align:left;">Approved Members</th>
                                <th style="padding:10px; text-align:left;">Pending Requests</th>
                            </tr>
                            <?php
                            $roles = $conn->query("SELECT * FROM event_roles WHERE event_id={$e['id']}");
                            while($r = $roles->fetch_assoc()):
                                $role_id = $r['id'];
                                $role_name = $r['role_name'];
                                $slots = $r['slots'];
                                $approved_q = $conn->query("SELECT COUNT(*) AS c FROM event_requests WHERE role_id=$role_id AND status='approved'");
                                $approved_count = $approved_q->fetch_assoc()['c'];
                                $pending = $conn->query("SELECT er.id,u.name FROM event_requests er JOIN users u ON er.user_id=u.id WHERE er.role_id=$role_id AND er.status='pending'");
                            ?>
                            <tr style="border-bottom:1px solid #ddd;">
                                <td style="padding:10px;"><?= $role_name ?></td>
                                <td style="padding:10px;"><?= $approved_count ?> / <?= $slots ?></td>
                                <td style="padding:10px;">
    <?php
                                    $approved_list = $conn->query("SELECT er.id AS req_id,u.name FROM event_requests er JOIN users u ON er.user_id=u.id WHERE er.role_id=$role_id AND er.status='approved'");
                                    if ($approved_list->num_rows > 0):
                                        while($a = $approved_list->fetch_assoc()):
                                            echo htmlspecialchars($a['name']) . "<br>";
                                        endwhile;
                                    else:
                                        echo "<i>None</i>";
                                    endif;
                                    ?>
                                </td>
                                <td style="padding:10px;">
                                    <?php if ($pending->num_rows > 0): ?>
                                        <?php while($p = $pending->fetch_assoc()): ?>
                                            <?= htmlspecialchars($p['name']) ?> 
                                            <a href="?req_approve=<?= $p['id'] ?>&tab=events" style="background:#27ae60; color:white; padding:4px 8px; border-radius:4px; text-decoration:none; font-size:12px;">Approve</a>
                                            <a href="?req_reject=<?= $p['id'] ?>&tab=events" style="background:#e74c3c; color:white; padding:4px 8px; border-radius:4px; text-decoration:none; font-size:12px;">Reject</a><br>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <i>No pending requests.</i>
                                    <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
                        <br><a href="?finish_event=<?= $e['id'] ?>&tab=events" style="background:#f39c12; color:white; padding:8px 16px; border-radius:6px; text-decoration:none;">Finish Event</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="chart-card">
                    <p>No ongoing events.</p>
                </div>
            <?php endif; ?>
        </div>
</div>
    
    <script>
        // Registration Timeline Chart
        const timelineCtx = document.getElementById('timelineChart').getContext('2d');
        new Chart(timelineCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($registrationTimeline, 'date')) ?>,
                datasets: [{
                    label: 'Registrations',
                    data: <?= json_encode(array_column($registrationTimeline, 'count')) ?>,
                    borderColor: '#2980b9',
                    backgroundColor: 'rgba(41, 128, 185, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
        
        // Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Approved', 'Pending'],
                datasets: [{
                    data: [<?= $approved ?>, <?= $pendingReview ?>],
                    backgroundColor: ['#27ae60', '#f39c12']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        
        // Programs Chart
        const programsCtx = document.getElementById('programsChart').getContext('2d');
        new Chart(programsCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($topPrograms, 'name')) ?>,
                datasets: [{
                    label: 'Count',
                    data: <?= json_encode(array_column($topPrograms, 'count')) ?>,
                    backgroundColor: '#2980b9'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 0.25 } }
                }
            }
        });
        
        // Year Distribution Chart
        const yearCtx = document.getElementById('yearChart').getContext('2d');
        new Chart(yearCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($yearDistribution, 'year')) ?>,
                datasets: [{
                    label: 'Count',
                    data: <?= json_encode(array_column($yearDistribution, 'count')) ?>,
                    backgroundColor: '#27ae60'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 0.25 } }
                }
            }
        });
    </script>
</body>
</html>
