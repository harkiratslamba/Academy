<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'school_management');
define('APP_NAME', 'School Time Management');
define('APP_URL',  'http://localhost/school-management');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:20px;color:red;">
        <h3>Database Connection Failed</h3>
        <p>' . $conn->connect_error . '</p>
        <p>Make sure XAMPP MySQL is running and the database <b>' . DB_NAME . '</b> exists.</p>
    </div>');
}
$conn->set_charset('utf8mb4');
