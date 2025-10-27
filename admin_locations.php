<?php
include 'db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle location actions
if (isset($_POST['add_location'])) {
    $name = $_POST['name'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    
    $query = "INSERT INTO locations (name, city, state) VALUES (:name, :city, :state)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':city', $city);
    $stmt->bindParam(':state', $state);
    $stmt->execute();
    
    header('Location: admin_location.php');
    exit();
}

if (isset($_POST['toggle_location'])) {
    $location_id = $_POST['location_id'];
    $current_status = $_POST['current_status'];
    $new_status = $current_status == 'active' ? 'inactive' : 'active';
    
    $query = "UPDATE locations SET status = :status WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $new_status);
    $stmt->bindParam(':id', $location_id);
    $stmt->execute();
    
    header('Location: admin_location.php');
    exit();
}

// Get all locations
$query = "SELECT * FROM locations ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>