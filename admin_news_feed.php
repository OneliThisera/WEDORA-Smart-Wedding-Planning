<?php
include 'db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle news actions
if (isset($_POST['add_news'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $image_url = $_POST['image_url'];
    $status = $_POST['status'];
    $published_at = $status == 'published' ? date('Y-m-d H:i:s') : NULL;
    
    $query = "INSERT INTO news_feed (title, content, image_url, author_id, status, published_at) 
              VALUES (:title, :content, :image_url, :author_id, :status, :published_at)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':image_url', $image_url);
    $stmt->bindParam(':author_id', $_SESSION['admin_id']);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':published_at', $published_at);
    $stmt->execute();
    
    header('Location: admin_news_feed.php');
    exit();
}

if (isset($_POST['delete_news'])) {
    $news_id = $_POST['news_id'];
    
    $query = "DELETE FROM news_feed WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $news_id);
    $stmt->execute();
    
    header('Location: admin_news_feed.php');
    exit();
}

// Get all news
$query = "SELECT n.*, u.name as author_name 
          FROM news_feed n 
          JOIN users u ON n.author_id = u.id 
          ORDER BY n.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$news_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>