<?php
// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['provider_id'])) {
    header("Location: service-provider_login.php");
    exit();
}

// Include database connection (assumes PDO, no session_start())
require '../db_connection.php';

// Get user data from session
$provider_id = $_SESSION['provider_id'];
$email = $_SESSION['email'] ?? '';
$company_name = $_SESSION['company_name'] ?? 'Unknown Provider';
$service_type = $_SESSION['service_type'] ?? 'other';
$profile_image = $_SESSION['profile_image'] ?? 'default_profile.jpg';

function getServiceIcon($service_type) {
    $icons = [
        'venue' => 'building',
        'catering' => 'utensils',
        'photography' => 'camera',
        'decor' => 'paint-brush',
        'music' => 'music',
        'salon' => 'spa',
        'other' => 'star'
    ];
    return $icons[strtolower($service_type)] ?? 'user-tie';
}

function getTotalBookings($pdo, $provider_id) {
    try {
        $sql = "SELECT COUNT(*) as total FROM bookings WHERE provider_id = :provider_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['provider_id' => $provider_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error in getTotalBookings: " . $e->getMessage());
        return 0;
    }
}

function getTotalRevenue($pdo, $provider_id) {
    try {
        $sql = "SELECT SUM(amount) as total FROM payments 
                WHERE booking_id IN (SELECT id FROM bookings WHERE provider_id = :provider_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['provider_id' => $provider_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error in getTotalRevenue: " . $e->getMessage());
        return 0;
    }
}

function getAverageRating($pdo, $provider_id) {
    try {
        $sql = "SELECT AVG(rating) as average FROM reviews WHERE provider_id = :provider_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['provider_id' => $provider_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return round($row['average'] ?? 0, 1);
    } catch (PDOException $e) {
        error_log("Error in getAverageRating: " . $e->getMessage());
        return 0;
    }
}

function getActiveServices($pdo, $provider_id) {
    try {
        $sql = "SELECT COUNT(*) as total FROM services WHERE provider_id = :provider_id AND status = 'active'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['provider_id' => $provider_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error in getActiveServices: " . $e->getMessage());
        return 0;
    }
}

function getRecentBookings($pdo, $provider_id) {
    try {
        $sql = "SELECT b.id, u.name as customer_name, s.title as service_title, 
                       b.booking_date, b.status, b.created_at
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                JOIN services s ON b.service_id = s.id
                WHERE b.provider_id = :provider_id
                ORDER BY b.created_at DESC
                LIMIT 5";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['provider_id' => $provider_id]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $html = '';
        foreach ($result as $row) {
            $html .= '<div class="booking-item">
                <div class="booking-info">
                    <div class="booking-customer">' . htmlspecialchars($row['customer_name']) . '</div>
                    <div class="booking-service">' . htmlspecialchars($row['service_title']) . '</div>
                    <div class="booking-date">' . date('M d, Y • h:i A', strtotime($row['booking_date'])) . '</div>
                </div>
                <div class="booking-status status-' . strtolower($row['status']) . '">' . ucfirst($row['status']) . '</div>
            </div>';
        }
        
        return $html ?: '<p>No recent bookings found.</p>';
    } catch (PDOException $e) {
        error_log("Error in getRecentBookings: " . $e->getMessage());
        return '<p>Unable to load recent bookings.</p>';
    }
}

function getRecentReviews($pdo, $provider_id) {
    try {
        $sql = "SELECT r.id, u.name as customer_name, r.rating, r.comment, r.created_at
                FROM reviews r
                JOIN users u ON r.user_id = u.id
                WHERE r.provider_id = :provider_id
                ORDER BY r.created_at DESC
                LIMIT 3";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['provider_id' => $provider_id]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $html = '';
        foreach ($result as $row) {
            $stars = str_repeat('<i class="fas fa-star" style="color: gold;"></i>', $row['rating']);
            $stars .= str_repeat('<i class="far fa-star" style="color: gold;"></i>', 5 - $row['rating']);
            
            $html .= '<div class="review-item">
                <div class="review-info">
                    <div class="review-customer">' . htmlspecialchars($row['customer_name']) . '</div>
                    <div class="review-service">' . htmlspecialchars($row['comment']) . '</div>
                    <div class="review-date">' . date('M d, Y', strtotime($row['created_at'])) . '</div>
                </div>
                <div class="review-rating">' . $stars . '</div>
            </div>';
        }
        
        return $html ?: '<p>No recent reviews found.</p>';
    } catch (PDOException $e) {
        error_log("Error in getRecentReviews: " . $e->getMessage());
        return '<p>Unable to load recent reviews.</p>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedora - Salon Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #6c5ce7;
            --secondary-color: #a29bfe;
            --accent-color: #f645a3;
            --dark-color: #2d3436;
            --light-color: #f5f6fa;
            --success-color: #00b894;
            --warning-color: #fdcb6e;
            --danger-color: #d63031;
            --text-color: #333;
            --text-light: #666;
            --border-color: #ddd;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--text-color);
            line-height: 1.6;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 0;
            box-shadow: var(--shadow);
            position: fixed;
            height: 100%;
            z-index: 100;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .logo {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }

        .logo i {
            margin-right: 10px;
            color: var(--accent-color);
        }

        .service-type {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
            font-size: 18px;
            color: #1b0c66;
        }

        .service-type i {
            color: #d4a5a5;
            font-size: 20px;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 15px;
        }

        .nav-link:hover, .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 3px solid var(--accent-color);
        }

        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }

        .page-title h1 {
            font-size: 24px;
            color: var(--dark-color);
        }

        .page-title p {
            font-size: 14px;
            color: var(--text-light);
        }

        .user-profile {
            display: flex;
            align-items: center;
        }

        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            border: 2px solid var(--secondary-color);
        }

        .user-info {
            margin-right: 15px;
            text-align: right;
        }

        .user-name {
            font-weight: 500;
            font-size: 14px;
        }

        .user-role {
            font-size: 12px;
            color: var(--text-light);
        }

        .logout-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 20px;
        }

        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--shadow);
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 500;
            color: var(--text-light);
        }

        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .card-icon.bookings {
            background-color: rgba(0, 184, 148, 0.1);
            color: var(--success-color);
        }

        .card-icon.revenue {
            background-color: rgba(253, 203, 110, 0.1);
            color: var(--warning-color);
        }

        .card-icon.rating {
            background-color: rgba(108, 92, 231, 0.1);
            color: var(--primary-color);
        }

        .card-icon.services {
            background-color: rgba(214, 48, 49, 0.1);
            color: var(--danger-color);
        }

        .card-value {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .card-change {
            font-size: 12px;
            display: flex;
            align-items: center;
        }

        .card-change.positive {
            color: var(--success-color);
        }

        .card-change.negative {
            color: var(--danger-color);
        }

        /* Recent Activity Section */
        .section {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
        }

        .view-all {
            color: var(--primary-color);
            font-size: 14px;
            text-decoration: none;
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(108, 92, 231, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary-color);
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .activity-time {
            font-size: 12px;
            color: var(--text-light);
        }

        /* Booking and Review Styles */
        .booking-item, .review-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .booking-item:last-child, .review-item:last-child {
            border-bottom: none;
        }

        .booking-info, .review-info {
            flex: 1;
        }

        .booking-customer, .review-customer {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .booking-service, .review-service {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .booking-date, .review-date {
            font-size: 12px;
            color: var(--text-light);
        }

        .booking-status, .review-rating {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-confirmed {
            background-color: rgba(0, 184, 148, 0.1);
            color: var(--success-color);
        }

        .status-pending {
            background-color: rgba(253, 203, 110, 0.1);
            color: var(--warning-color);
        }

        .rating-stars {
            color: #FFD700;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }

            .sidebar-header, .nav-link span {
                display: none;
            }

            .nav-link {
                justify-content: center;
                padding: 15px 0;
            }

            .nav-link i {
                margin-right: 0;
                font-size: 18px;
            }

            .main-content {
                margin-left: 70px;
            }

            .user-info {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-heart"></i>
                    <span>Wedora</span>
                </div>
                <div class="service-type">
                    <i class="fas fa-<?php echo htmlspecialchars(getServiceIcon($service_type)); ?>"></i>
                    <span>Salon Provider</span>
                </div>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="../salon_dashboard.php" class="nav-link active"><i class="fas fa-tachometer-alt"></i><span>Salon Dashboard</span></a></li>
                <li class="nav-item"><a href="../service-provider_profile.php" class="nav-link"><i class="fas fa-user"></i><span>Profile</span></a></li>
                <li class="nav-item"><a href="../service-provider_gallery.php" class="nav-link"><i class="fas fa-image"></i><span>Gallery</span></a></li>
                <li class="nav-item"><a href="../service-provider_services.php" class="nav-link"><i class="fas fa-concierge-bell"></i><span>Services</span></a></li>
                <li class="nav-item"><a href="../service-provider_bookings.php" class="nav-link"><i class="fas fa-calendar-check"></i><span>Bookings</span></a></li>
                <li class="nav-item"><a href="../service-provider_payments.php" class="nav-link"><i class="fas fa-credit-card"></i><span>Payments</span></a></li>
                <li class="nav-item"><a href="../service-provider_reviews.php" class="nav-link"><i class="fas fa-star"></i><span>Reviews</span></a></li>
                <li class="nav-item"><a href="../service-provider_promotions.php" class="nav-link"><i class="fas fa-bullhorn"></i><span>Promotions</span></a></li>
                <li class="nav-item"><a href="../service-provider_settings.php" class="nav-link"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1>Salon Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($company_name); ?></p>
                </div>
                <div class="user-profile">
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($company_name); ?></div>
                        <div class="user-role">Salon Provider</div>
                    </div>
                    <form action="logout.php" method="POST">
                        <button type="submit" class="logout-btn"><i class="fas fa-sign-out-alt"></i></button>
                    </form>
                </div>
            </div>

            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Total Bookings</div>
                        <div class="card-icon bookings"><i class="fas fa-calendar-check"></i></div>
                    </div>
                    <div class="card-value"><?php echo getTotalBookings($pdo, $provider_id); ?></div>
                    <div class="card-change positive"><i class="fas fa-arrow-up"></i> Dynamic data needed</div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Total Revenue</div>
                        <div class="card-icon revenue"><i class="fas fa-dollar-sign"></i></div>
                    </div>
                    <div class="card-value">$<?php echo number_format(getTotalRevenue($pdo, $provider_id), 2); ?></div>
                    <div class="card-change positive"><i class="fas fa-arrow-up"></i> Dynamic data needed</div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Average Rating</div>
                        <div class="card-icon rating"><i class="fas fa-star"></i></div>
                    </div>
                    <div class="card-value"><?php echo getAverageRating($pdo, $provider_id); ?>/5.0</div>
                    <div class="card-change positive"><i class="fas fa-arrow-up"></i> Dynamic data needed</div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Active Services</div>
                        <div class="card-icon services"><i class="fas fa-concierge-bell"></i></div>
                    </div>
                    <div class="card-value"><?php echo getActiveServices($pdo, $provider_id); ?></div>
                    <div class="card-change negative"><i class="fas fa-arrow-down"></i> Dynamic data needed</div>
                </div>
            </div>

            <!-- Recent Bookings Section -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Recent Bookings</h2>
                    <a href="service-provider_bookings.php" class="view-all">View All</a>
                </div>
                <div class="recent-bookings">
                    <?php echo getRecentBookings($pdo, $provider_id); ?>
                </div>
            </div>

            <!-- Recent Reviews Section -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Recent Reviews</h2>
                    <a href="service-provider_reviews.php" class="view-all">View All</a>
                </div>
                <div class="recent-reviews">
                    <?php echo getRecentReviews($pdo, $provider_id); ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>