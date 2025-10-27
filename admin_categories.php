<?php
include 'db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle category actions
if (isset($_POST['add_category'])) {
    $name = $_POST['name'];
    $parent_id = $_POST['parent_id'] ?: NULL;
    
    $query = "INSERT INTO categories (name, parent_id) VALUES (:name, :parent_id)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':parent_id', $parent_id);
    $stmt->execute();
    
    header('Location: admin_categories.php');
    exit();
}

if (isset($_POST['delete_category'])) {
    $category_id = $_POST['category_id'];
    
    $query = "DELETE FROM categories WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $category_id);
    $stmt->execute();
    
    header('Location: admin_categories.php');
    exit();
}

// Get all categories
$query = "SELECT c1.*, c2.name as parent_name 
          FROM categories c1 
          LEFT JOIN categories c2 ON c1.parent_id = c2.id 
          ORDER BY c1.parent_id, c1.name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get main categories for dropdown
$query = "SELECT * FROM categories WHERE parent_id IS NULL";
$stmt = $db->prepare($query);
$stmt->execute();
$main_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>