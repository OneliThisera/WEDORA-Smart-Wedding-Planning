<?php
// admin_logout.php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear localStorage on client side by redirecting with a parameter
header('Location: admin_login.html?logout=success');
exit;
?>