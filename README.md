# ByteWave Website Development
Here is the Github for the development of GPSphere. 
GPSphere is a digital platform for the Gerakan Pengguna Siswa (Students’ Consumer Movement) at UTM Johor, built using HTML, CSS, JavaScript, PHP, and MySQL (via XAMPP).

# Important 
My MySQL port is 3307 due to a local conflict.
If your XAMPP MySQL uses the default 3306, update the port number in:
config.php
$port = 3307;

Also rename the folder in htdocs to name GPSphere

Change 3307 → 3306 (for normal  3306 port)


# Steps on how to start
1. Open XAMPP and start APACHE and MySQL
2. Browse http://localhost/GPSphere/ to get to the Index of/GPSphere
3. Also browse http://localhost/phpmyadmin for the database.
4. Run create_database.php. It will create
    gpsphere_db
    users
    events
    event_roles
    event_requests

    And a default Admin account
    Email: admin@gpsphere.com (not existed email. you can change by yourself at create_database.php part // 5️⃣ Optional: Add an admin account)
    Password: Admin123! (also can change)

5. Check the databse at the phpmyadmin. You should have a gpsphere_db
6. Follow the steps at for the app password for TAC 2FA email
7. After finish follow, you can start trying the website.

For now I have acheive:
Student registration with secure password hashing (bcrypt)
Login system with Two-Factor Authentication (TAC)
TAC Test Mode (TAC appears directly on the webpage for local testing)
TAC Gmail Mode (TAC sent via PHPMailer using Gmail App Password)

Role-based dashboards: Student, Member, Admin

Student can register to the website.
After being the member registration approve by the admin, they become member

Member can request to join the specific position of an events and need approval from the admin

Admin can approve or reject student applications for member and event crew
Admin can also create new events with specific crews needed.



# How to Generate a Gmail App Password for PHPMailer
App Password is needed by the phpmailer to get to work. Please follow it

Step 1 — Turn On 2-Step Verification
Go to 👉 https://myaccount.google.com/
On the left menu, click “Security.”
Scroll to “Signing in to Google.”

Click “2-Step Verification.”
Follow the prompts to turn it on (you’ll verify using your phone).
🔸 This step is required before you can create an App Password.

✅ Step 2 — Open “App Passwords”
After enabling 2-Step Verification, go back to:
👉 https://myaccount.google.com/apppasswords
Sign in again if prompted.
Under “Select app,” choose Mail.
Under “Select device,” choose Other (Custom name) and type GPSphere.
Click Generate.

✅ Step 3 — Copy the 16-Character App Password
A yellow box will appear with something like:
abcd efgh ijkl mnop
That’s your App Password (ignore the spaces).

✅ Step 4 — Use It in PHPMailer
In your login.php, replace:
$mail->Username = 'YOUR_GMAIL@gmail.com';
$mail->Password = 'YOUR_APP_PASSWORD';
with your actual Gmail and the App Password (without spaces).

Example:
$mail->Username = 'chengjieutm@gmail.com';
$mail->Password = 'abcdijklmnopqrst';

✅ Step 5 — Save and Test
Save login.php
Run XAMPP → start Apache + MySQL
Go to http://localhost/GPSphere/login.php
Login and check your Gmail inbox — you should receive the TAC email 🎉



# About 2FA TAC(Please watch the How to Generate a Gmail App Password for PHPMailer first)

🔐 Two-Factor Authentication (TAC System)
🧪 Local Test Mode (Default)
During development, GPSphere runs in Test Mode for TAC.
This means the generated 6-digit code is shown directly on the screen, instead of being sent via email.

Example output on login:
✅ Test Mode: Your TAC is 451344 (expires 2025-11-05 06:54:28)
This allows teammates to test login and TAC verification without needing Gmail setup.

How Test Mode Works
In login.php, you’ll find:
// ---- TEST MODE ----
// Comment out the real email sending during local testing
// $mail->send();
// $success = "A verification code (TAC) has been sent to your email.";
// $redirect = true;

// Instead, just show TAC on screen for local testing
$success = "✅ Test Mode: Your TAC is <b>$tac</b> (expires $expiry)";
$redirect = true;

To let it works with gmail:

Replace this part:
$mail->Username = 'YOUR_GMAIL@gmail.com';
$mail->Password = 'YOUR_APP_PASSWORD';

Example:
$mail->Username = 'chengjieutm@gmail.com';
$mail->Password = 'abcdijklmnopqrst';

Then change these lines:
// ---- TEST MODE ----
// Comment out the real email sending during local testing
// $mail->send();
// $success = "A verification code (TAC) has been sent to your email.";
// $redirect = true;

// Instead, just show TAC on screen for local testing
$success = "✅ Test Mode: Your TAC is <b>$tac</b> (expires $expiry)";
$redirect = true;
$mail->send();
$success = "A verification code (TAC) has been sent to your email.";
$redirect = true;

to this:
$mail->send();
$success = "A verification code (TAC) has been sent to your email.";
$redirect = true;


Step 4 — Restart & Test
REstart Apache & MySQL in XAMPP.
Visit 👉 http://localhost/GPSphere/login.php
Login → check your Gmail inbox for the TAC email 🎉
