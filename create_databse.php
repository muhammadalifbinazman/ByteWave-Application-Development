<?php
/* 
---------------------------------------
 File: create_database.php
 Author: ByteWave Team (GPSphere Project)
 Purpose: Automatically create database and tables
---------------------------------------
*/

$host = "localhost";
$user = "root";
$pass = "";
$port = 3307; // ⚠️ Change this if your MySQL uses another port

// 1️⃣ Connect to MySQL Server (no database selected yet)
$conn = new mysqli($host, $user, $pass, "", $port);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// 2️⃣ Create database
$dbname = "gpsphere_db";
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "✅ Database '$dbname' created or already exists.<br>";
} else {
    die("❌ Error creating database: " . $conn->error);
}

// 3️⃣ Select the database
$conn->select_db($dbname);

// 4️⃣ Create `users` table
$table = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','member','admin') DEFAULT 'student',
    status ENUM('pending','approved') DEFAULT 'pending',
    tac_code VARCHAR(10),
    tac_expiry DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($table) === TRUE) {
    echo "✅ Table 'users' created or already exists.<br>";
} else {
    die("❌ Error creating table: " . $conn->error);
}

// 5️⃣ Optional: Add an admin account
$adminEmail = "admin@gpsphere.com";
$adminPass = password_hash("Admin123!", PASSWORD_DEFAULT);

$checkAdmin = $conn->query("SELECT * FROM users WHERE email='$adminEmail'");
if ($checkAdmin->num_rows == 0) {
    $insertAdmin = "INSERT INTO users (name, email, password, role, status) 
                    VALUES ('System Admin', '$adminEmail', '$adminPass', 'admin', 'approved')";
    if ($conn->query($insertAdmin)) {
        echo "✅ Default admin account created (Email: $adminEmail | Password: Admin123!)<br>";
    } else {
        echo "⚠️ Admin insert failed: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ Admin account already exists.<br>";
}

// === Create Events Table ===
$events = "CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE,
    event_time TIME,
    location VARCHAR(150),
    director_needed INT DEFAULT 1,
    helper_needed INT DEFAULT 5,
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($events) === TRUE) {
    echo "✅ Table 'events' created or already exists.<br>";
} else {
    echo "❌ Error creating events table: " . $conn->error . "<br>";
}


echo "<hr><b>🎉 Database setup completed successfully!</b>";
$conn->close();
?>;

