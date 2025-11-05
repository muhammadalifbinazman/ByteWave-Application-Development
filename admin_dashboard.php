<?php
include('config.php');
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$name  = $_SESSION['name'] ?? 'Admin';
$msg   = "";

// --- MEMBER ACTIONS ---
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $conn->query("UPDATE users SET role='member', status='approved' WHERE id=$id");
    $msg = "<div class='msg success'>✅ Member approved successfully.</div>";
}
if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $conn->query("DELETE FROM users WHERE id=$id");
    $msg = "<div class='msg error'>❌ Registration rejected and deleted.</div>";
}
if (isset($_GET['remove'])) {
    $id = intval($_GET['remove']);
    $conn->query("DELETE FROM users WHERE id=$id");
    $msg = "<div class='msg error'>🗑 Member removed successfully.</div>";
}

// --- EVENT CREATION ---
if (isset($_POST['create_event'])) {
    $event_name = trim($_POST['event_name']);
    $description = trim($_POST['description']);
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $location   = trim($_POST['location']);
    $director_needed = intval($_POST['director_needed']);
    $helper_needed   = intval($_POST['helper_needed']);

    if (empty($event_name) || empty($event_date) || empty($event_time) || empty($location)) {
        $msg = "<div class='msg error'>⚠️ All required fields must be filled.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO events
            (event_name, description, event_date, event_time, location, director_needed, helper_needed, created_by)
            VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssiis", $event_name, $description, $event_date, $event_time,
                          $location, $director_needed, $helper_needed, $email);
        $msg = $stmt->execute()
            ? "<div class='msg success'>✅ Event created successfully.</div>"
            : "<div class='msg error'>❌ Failed to create event.</div>";
    }
}

// --- FETCH DATA ---
$pending  = $conn->query("SELECT * FROM users WHERE role='student' AND status='pending'");
$approved = $conn->query("SELECT * FROM users WHERE role='member' AND status='approved'");
$events   = $conn->query("SELECT * FROM events ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>GPSphere | Admin Dashboard</title>
<style>
body{font-family:Arial;background:#f4f6f7;margin:0;padding:30px;}
h1,h2{color:#2c3e50;}
nav{margin-bottom:20px;}
nav button{
    background:#2980b9;color:white;border:none;padding:10px 20px;
    border-radius:5px;cursor:pointer;margin-right:10px;
}
nav button.active{background:#1c5980;}
section{display:none;}
section.active{display:block;}
table{
    border-collapse:collapse;width:95%;background:#fff;margin-top:15px;
    border-radius:8px;overflow:hidden;box-shadow:0 4px 8px rgba(0,0,0,.1);
}
th,td{padding:10px 12px;border-bottom:1px solid #eee;}
th{background:#2980b9;color:#fff;}
.msg{margin:15px 0;padding:10px;border-radius:6px;width:60%;}
.success{background:#d4edda;color:#155724;}
.error{background:#f8d7da;color:#721c24;}
a.btn{text-decoration:none;padding:6px 10px;border-radius:5px;color:#fff;font-size:13px;}
.approve{background:#27ae60;}
.reject{background:#e74c3c;}
.remove{background:#c0392b;}
.logout{background:#34495e;color:white;padding:8px 15px;border-radius:5px;text-decoration:none;}
.logout:hover{background:#2c3e50;}
form{background:#fff;padding:20px;border-radius:10px;box-shadow:0 4px 8px rgba(0,0,0,.1);width:60%;margin-top:15px;}
input,textarea{width:100%;padding:8px;margin:8px 0;border:1px solid #ccc;border-radius:6px;}
button[type=submit]{background:#2980b9;color:white;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;}
button[type=submit]:hover{background:#1c5980;}
</style>
</head>
<body>

<h1>Admin Dashboard</h1>
<p>Welcome, <b><?php echo $name; ?></b> 👋 | <a href="logout.php" class="logout">Logout</a></p>
<?php echo $msg; ?>

<nav>
    <button id="btnMembers" class="active">👥 Members</button>
    <button id="btnEvents">📅 Events</button>
</nav>

<!-- MEMBERS SECTION -->
<section id="membersSection" class="active">
    <h2>Pending Approvals</h2>
    <table>
        <tr><th>Name</th><th>Email</th><th>Action</th></tr>
        <?php if ($pending->num_rows>0): while($r=$pending->fetch_assoc()): ?>
            <tr>
                <td><?= $r['name']??$r['NAME']; ?></td>
                <td><?= $r['email']??$r['EMAIL']; ?></td>
                <td>
                    <a href="?approve=<?= $r['id']; ?>" class="btn approve">✅ Approve</a>
                    <a href="?reject=<?= $r['id']; ?>" class="btn reject">❌ Reject</a>
                </td>
            </tr>
        <?php endwhile; else: ?><tr><td colspan="3">No pending applications.</td></tr><?php endif; ?>
    </table>

    <h2>Approved Members</h2>
    <table>
        <tr><th>Name</th><th>Email</th><th>Action</th></tr>
        <?php if ($approved->num_rows>0): while($r=$approved->fetch_assoc()): ?>
            <tr>
                <td><?= $r['name']??$r['NAME']; ?></td>
                <td><?= $r['email']??$r['EMAIL']; ?></td>
                <td><a href="?remove=<?= $r['id']; ?>" class="btn remove">🗑 Remove</a></td>
            </tr>
        <?php endwhile; else: ?><tr><td colspan="3">No members yet.</td></tr><?php endif; ?>
    </table>
</section>

<!-- EVENTS SECTION -->
<section id="eventsSection">
    <h2>Create New Event</h2>
    <form method="POST" action="">
        <input type="text" name="event_name" placeholder="Event Name" required>
        <textarea name="description" placeholder="Description"></textarea>
        <input type="date" name="event_date" required>
        <input type="time" name="event_time" required>
        <input type="text" name="location" placeholder="Location" required>
        <label>Director Needed:</label>
        <input type="number" name="director_needed" value="1" min="1">
        <label>Helpers Needed:</label>
        <input type="number" name="helper_needed" value="5" min="1">
        <button type="submit" name="create_event">Create Event</button>
    </form>

    <h2>Existing Events</h2>
    <table>
        <tr><th>Event Name</th><th>Date</th><th>Time</th><th>Location</th><th>Created By</th><th>Action</th></tr>
        <?php if ($events->num_rows>0): while($e=$events->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($e['event_name']); ?></td>
                <td><?= htmlspecialchars($e['event_date']); ?></td>
                <td><?= htmlspecialchars($e['event_time']); ?></td>
                <td><?= htmlspecialchars($e['location']); ?></td>
                <td><?= htmlspecialchars($e['created_by']); ?></td>
                <td><a href="delete_event.php?id=<?= $e['id']; ?>" class="btn reject">🗑 Delete</a></td>
            </tr>
        <?php endwhile; else: ?><tr><td colspan="6">No events created.</td></tr><?php endif; ?>
    </table>
</section>

<script>
// Toggle sections
const btnMembers=document.getElementById('btnMembers');
const btnEvents=document.getElementById('btnEvents');
const membersSection=document.getElementById('membersSection');
const eventsSection=document.getElementById('eventsSection');
btnMembers.onclick=()=>{
  btnMembers.classList.add('active');
  btnEvents.classList.remove('active');
  membersSection.classList.add('active');
  eventsSection.classList.remove('active');
};
btnEvents.onclick=()=>{
  btnEvents.classList.add('active');
  btnMembers.classList.remove('active');
  eventsSection.classList.add('active');
  membersSection.classList.remove('active');
};
</script>
</body>
</html>
