<?php
// admin_reports.php - UPDATED VERSION

require_once 'db_connection.php';

header('Content-Type: application/json');

// Get report data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $report_type = $_GET['type'] ?? 'overview';
    
    try {
        switch ($report_type) {
            case 'user_activity':
                $stmt = $pdo->query("
                    SELECT DATE(created_at) as date, COUNT(*) as signups
                    FROM users 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date
                ");
                $data = $stmt->fetchAll();
                break;
                
            case 'service_usage':
                $stmt = $pdo->query("
                    SELECT c.name as category, COUNT(*) as bookings
                    FROM appointments a
                    LEFT JOIN providers p ON a.provider_id = p.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY c.name
                    ORDER BY bookings DESC
                ");
                $data = $stmt->fetchAll();
                break;
                
            case 'revenue':
                $stmt = $pdo->query("
                    SELECT DATE(created_at) as date, SUM(amount) as revenue
                    FROM payments 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status = 'completed'
                    GROUP BY DATE(created_at)
                    ORDER BY date
                ");
                $data = $stmt->fetchAll();
                break;
                
            default: // overview
                $stmt = $pdo->query("
                    SELECT 
                        (SELECT COUNT(*) FROM users) as total_users,
                        (SELECT COUNT(*) FROM providers WHERE is_approved = 1) as total_providers,
                        (SELECT COUNT(*) FROM appointments WHERE DATE(created_at) = CURDATE()) as today_appointments,
                        (SELECT COUNT(*) FROM complaints WHERE status = 'pending') as pending_complaints
                ");
                $data = $stmt->fetch();
                break;
        }
        
        echo json_encode($data);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to generate report: ' . $e->getMessage()]);
    }
}
?>