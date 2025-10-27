<?php
include 'db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle complaint actions
if (isset($_POST['action'])) {
    $complaint_id = $_POST['complaint_id'];
    $status = $_POST['status'];
    
    $query = "UPDATE complaints SET status = :status WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $complaint_id);
    $stmt->execute();
    
    header('Location: admin_complaints.php');
    exit();
}

// Get all complaints with user info
$query = "SELECT c.*, u.name as user_name 
          FROM complaints c 
          JOIN users u ON c.user_id = u.id 
          ORDER BY c.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>