<?php
include 'db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle provider actions
if (isset($_POST['action'])) {
    $provider_id = $_POST['provider_id'];
    $action = $_POST['action'];
    
    if ($action == 'approve') {
        $query = "UPDATE providers SET status = 'approved' WHERE id = :id";
    } elseif ($action == 'reject') {
        $query = "UPDATE providers SET status = 'rejected' WHERE id = :id";
    } elseif ($action == 'delete') {
        $query = "DELETE FROM providers WHERE id = :id";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $provider_id);
    $stmt->execute();
    
    header('Location: admin_provider_approval.php');
    exit();
}

// Get all providers with user info
$query = "SELECT p.*, u.name as owner_name, u.email 
          FROM providers p 
          JOIN users u ON p.user_id = u.id 
          ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>