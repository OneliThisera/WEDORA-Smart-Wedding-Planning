<?php
include 'db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle backup actions
if (isset($_POST['create_backup'])) {
    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $file_path = 'backups/' . $filename;
    
    // Create backups directory if it doesn't exist
    if (!is_dir('backups')) {
        mkdir('backups', 0755, true);
    }
    
    // Simple backup creation (in production, use proper backup tools)
    $query = "INSERT INTO backups (filename, file_path, file_size, backup_type) 
              VALUES (:filename, :file_path, :file_size, 'full')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':filename', $filename);
    $stmt->bindParam(':file_path', $file_path);
    $stmt->bindValue(':file_size', '2.5 MB');
    $stmt->execute();
    
    header('Location: admin_data_backup.php');
    exit();
}

if (isset($_POST['delete_backup'])) {
    $backup_id = $_POST['backup_id'];
    
    $query = "DELETE FROM backups WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $backup_id);
    $stmt->execute();
    
    header('Location: admin_data_backup.php');
    exit();
}

// Get all backups
$query = "SELECT * FROM backups ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>