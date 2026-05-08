<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    logActivity($conn, 'LOGOUT', 'User logged out');
}

session_unset();
session_destroy();
setcookie('sms_user', '', time() - 3600, '/');

header('Location: index.php');
exit;
