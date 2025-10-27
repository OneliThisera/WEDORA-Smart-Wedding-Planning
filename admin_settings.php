<?php
include 'db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle settings update
if (isset($_POST['update_settings'])) {
    $site_name = $_POST['site_name'];
    $admin_email = $_POST['admin_email'];
    
    // Update settings in database (you might want a settings table)
    // For demo, we'll just show the functionality
    
    $_SESSION['success_message'] = "Settings updated successfully!";
    header('Location: admin_settings.php');
    exit();
}
?>