<?php
include 'db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get dashboard statistics
$stats = [];

// Total users
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total providers
$query = "SELECT COUNT(*) as total FROM providers WHERE status = 'approved'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_providers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pending providers
$query = "SELECT COUNT(*) as total FROM providers WHERE status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['pending_providers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pending complaints
$query = "SELECT COUNT(*) as total FROM complaints WHERE status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['pending_complaints'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent activity
$query = "SELECT c.*, u.name as user_name FROM complaints c 
          JOIN users u ON c.user_id = u.id 
          ORDER BY c.created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>