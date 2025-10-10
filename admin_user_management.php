<?php
// user_management.php

require_once 'db_connection.php';

header('Content-Type: application/json');

// Get all users
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("
            SELECT id, name, email, role, status, created_at 
            FROM users 
            ORDER BY created_at DESC
        ");
        $users = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $users]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch users: ' . $e->getMessage()]);
    }
    exit;
}

// Update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    if (!isset($_POST['id']) || !isset($_POST['name']) || !isset($_POST['email']) || !isset($_POST['role']) || !isset($_POST['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET name = :name, email = :email, role = :role, status = :status
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':name' => $_POST['name'],
            ':email' => $_POST['email'],
            ':role' => $_POST['role'],
            ':status' => $_POST['status'],
            ':id' => $_POST['id']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update user: ' . $e->getMessage()]);
    }
    exit;
}

// Delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing user ID']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $_POST['id']]);
        
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete user: ' . $e->getMessage()]);
    }
    exit;
}

// If no valid action specified
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>