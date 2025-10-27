<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

try {
    // Get filter parameters
    $category = $_GET['category'] ?? '';
    $location = $_GET['location'] ?? '';
    $min_price = (int)($_GET['min_price'] ?? 0);
    $max_price = (int)($_GET['max_price'] ?? 0);
    $sort = $_GET['sort'] ?? 'rating';
    
    // Build query
    $query = "SELECT sp.*, sc.category_name, sc.icon_class,
                     COALESCE(AVG(r.rating), 0) as average_rating,
                     COUNT(r.review_id) as review_count
              FROM service_providers sp
              LEFT JOIN service_categories sc ON sp.category_id = sc.category_id
              LEFT JOIN reviews r ON sp.provider_id = r.provider_id
              WHERE sp.is_active = TRUE";
    
    $params = [];
    
    if (!empty($category)) {
        $query .= " AND sc.category_name = ?";
        $params[] = $category;
    }
    
    if (!empty($location)) {
        $query .= " AND sp.location LIKE ?";
        $params[] = "%$location%";
    }
    
    if ($min_price > 0) {
        $query .= " AND sp.price_range_min >= ?";
        $params[] = $min_price;
    }
    
    if ($max_price > 0) {
        $query .= " AND sp.price_range_max <= ?";
        $params[] = $max_price;
    }
    
    $query .= " GROUP BY sp.provider_id";
    
    // Add sorting
    switch ($sort) {
        case 'price_low':
            $query .= " ORDER BY sp.price_range_min ASC";
            break;
        case 'price_high':
            $query .= " ORDER BY sp.price_range_max DESC";
            break;
        case 'rating':
            $query .= " ORDER BY average_rating DESC, review_count DESC";
            break;
        case 'newest':
            $query .= " ORDER BY sp.created_at DESC";
            break;
        default:
            $query .= " ORDER BY average_rating DESC";
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $services = $stmt->fetchAll();
    
    // Format price ranges
    foreach ($services as &$service) {
        $service['price_range'] = 'LKR ' . number_format($service['price_range_min']) . ' - ' . number_format($service['price_range_max']);
        $service['average_rating'] = round($service['average_rating'], 1);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $services,
        'count' => count($services)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch services'
    ]);
}
?>