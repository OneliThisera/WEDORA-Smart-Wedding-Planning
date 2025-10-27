<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$provider_id = (int)($_POST['provider_id'] ?? $_GET['provider_id'] ?? 0);
$customer_id = $_SESSION['customer_id'];

try {
    switch ($action) {
        case 'add':
            $stmt = $pdo->prepare("INSERT IGNORE INTO favorites (customer_id, provider_id) VALUES (?, ?)");
            $stmt->execute([$customer_id, $provider_id]);
            echo json_encode(['success' => true, 'message' => 'Added to favorites']);
            break;
            
        case 'remove':
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE customer_id = ? AND provider_id = ?");
            $stmt->execute([$customer_id, $provider_id]);
            echo json_encode(['success' => true, 'message' => 'Removed from favorites']);
            break;
            
        case 'check':
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM favorites WHERE customer_id = ? AND provider_id = ?");
            $stmt->execute([$customer_id, $provider_id]);
            $result = $stmt->fetch();
            echo json_encode(['success' => true, 'is_favorite' => $result['count'] > 0]);
            break;
            
        case 'list':
            $stmt = $pdo->prepare("
                SELECT f.*, sp.business_name, sp.location, sc.category_name, sc.icon_class,
                       sp.price_range_min, sp.price_range_max,
                       COALESCE(AVG(r.rating), 0) as average_rating,
                       COUNT(r.review_id) as review_count
                FROM favorites f
                JOIN service_providers sp ON f.provider_id = sp.provider_id
                JOIN service_categories sc ON sp.category_id = sc.category_id
                LEFT JOIN reviews r ON sp.provider_id = r.provider_id
                WHERE f.customer_id = ?
                GROUP BY f.favorite_id
                ORDER BY f.created_at DESC
            ");
            $stmt->execute([$customer_id]);
            $favorites = $stmt->fetchAll();
            
            // Format data
            foreach ($favorites as &$favorite) {
                $favorite['price_range'] = 'LKR ' . number_format($favorite['price_range_min']) . ' - ' . number_format($favorite['price_range_max']);
                $favorite['average_rating'] = round($favorite['average_rating'], 1);
            }
            
            echo json_encode(['success' => true, 'data' => $favorites]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Operation failed']);
}
?>