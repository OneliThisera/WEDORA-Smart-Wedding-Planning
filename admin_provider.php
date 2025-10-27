<?php
// provider.php

require_once 'db_connection.php';

header('Content-Type: application/json');

// Get all providers
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = $_GET['type'] ?? 'all';
    
    try {
        $query = "
            SELECT p.id, p.company_name, u.name as owner_name, c.name as category_name, p.location, p.is_approved
            FROM providers p
            LEFT JOIN users u ON p.owner_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
        ";
        
        if ($type === 'pending') {
            $query .= " WHERE p.is_approved = 0";
        } elseif ($type === 'approved') {
            $query .= " WHERE p.is_approved = 1";
        }
        
        $query .= " ORDER BY p.created_at DESC";
        
        $stmt = $pdo->query($query);
        $providers = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $providers]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch providers: ' . $e->getMessage()]);
    }
    exit;
}

// Approve provider
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve') {
    if (!isset($_POST['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing provider ID']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE providers SET is_approved = 1 WHERE id = :id");
        $stmt->execute([':id' => $_POST['id']]);
        
        echo json_encode(['success' => true, 'message' => 'Provider approved successfully']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to approve provider: ' . $e->getMessage()]);
    }
    exit;
}

// Reject provider
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject') {
    if (!isset($_POST['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing provider ID']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM providers WHERE id = :id");
        $stmt->execute([':id' => $_POST['id']]);
        
        echo json_encode(['success' => true, 'message' => 'Provider rejected successfully']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to reject provider: ' . $e->getMessage()]);
    }
    exit;
}

// If no valid action specified
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>