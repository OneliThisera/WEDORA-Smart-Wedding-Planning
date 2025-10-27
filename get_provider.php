<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Provider ID required']);
    exit;
}

$provider_id = (int)$_GET['id'];

try {
    // Get provider details
    $stmt = $pdo->prepare("
        SELECT sp.*, sc.category_name, sc.icon_class,
               COALESCE(AVG(r.rating), 0) as average_rating,
               COUNT(r.review_id) as review_count
        FROM service_providers sp
        LEFT JOIN service_categories sc ON sp.category_id = sc.category_id
        LEFT JOIN reviews r ON sp.provider_id = r.provider_id
        WHERE sp.provider_id = ? AND sp.is_active = TRUE
        GROUP BY sp.provider_id
    ");
    $stmt->execute([$provider_id]);
    $provider = $stmt->fetch();
    
    if (!$provider) {
        echo json_encode(['success' => false, 'error' => 'Provider not found']);
        exit;
    }
    
    // Get service packages
    $stmt = $pdo->prepare("
        SELECT * FROM service_packages 
        WHERE provider_id = ? AND is_active = TRUE 
        ORDER BY price ASC
    ");
    $stmt->execute([$provider_id]);
    $packages = $stmt->fetchAll();
    
    // Get recent reviews
    $stmt = $pdo->prepare("
        SELECT r.*, c.first_name, c.last_name, 
               DATE_FORMAT(r.created_at, '%M %d, %Y') as formatted_date
        FROM reviews r
        JOIN customers c ON r.customer_id = c.customer_id
        WHERE r.provider_id = ?
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$provider_id]);
    $reviews = $stmt->fetchAll();
    
    // Check if current user has favorited this provider
    $is_favorite = false;
    if (isset($_SESSION['customer_id'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM favorites WHERE customer_id = ? AND provider_id = ?");
        $stmt->execute([$_SESSION['customer_id'], $provider_id]);
        $result = $stmt->fetch();
        $is_favorite = $result['count'] > 0;
    }
    
    // Format data
    $provider['price_range'] = 'LKR ' . number_format($provider['price_range_min']) . ' - ' . number_format($provider['price_range_max']);
    $provider['average_rating'] = round($provider['average_rating'], 1);
    $provider['is_favorite'] = $is_favorite;
    
    echo json_encode([
        'success' => true,
        'provider' => $provider,
        'packages' => $packages,
        'reviews' => $reviews
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch provider details'
    ]);
}
?>