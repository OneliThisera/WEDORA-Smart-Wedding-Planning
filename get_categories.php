<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT sc.*, COUNT(sp.provider_id) as provider_count
        FROM service_categories sc
        LEFT JOIN service_providers sp ON sc.category_id = sp.category_id AND sp.is_active = TRUE
        GROUP BY sc.category_id
        ORDER BY provider_count DESC
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $categories]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to fetch categories']);
}
?>