<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header("Location: service-provider_login.html");
    exit();
}

// Include external database connection
require 'db.php';

// Get user data
$email = $_SESSION['user_email'];
$sql = "SELECT * FROM service_providers WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

// Assign provider ID and type
$provider_id = $user['id'];
$service_type = $user['service_type'];

//  Helper Functions
function getServiceIcon($service_type) {
    $icons = [
        'venue' => 'building',
        'catering' => 'utensils',
        'photography' => 'camera',
        'decor' => 'paint-brush',
        'music' => 'music',
        'dressing' => 'tshirt',
        'saloon' => 'spa',
        'other' => 'star'
    ];
    return $icons[strtolower($service_type)] ?? 'user-tie';
}

function getTotalBookings($conn, $provider_id) {
    $sql = "SELECT COUNT(*) as total FROM bookings WHERE provider_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['total'];
}

function getTotalRevenue($conn, $provider_id) {
    $sql = "SELECT SUM(amount) as total FROM payments 
            WHERE booking_id IN (SELECT id FROM bookings WHERE provider_id = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['total'] ?? 0;
}

function getAverageRating($conn, $provider_id) {
    $sql = "SELECT AVG(rating) as average FROM reviews WHERE provider_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['average'] ?? 0;
}

function getActiveServices($conn, $provider_id) {
    $sql = "SELECT COUNT(*) as total FROM services WHERE provider_id = ? AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['total'];
}

function getRecentBookings($conn, $provider_id) {
    $sql = "SELECT b.id, u.name as customer_name, s.title as service_title, 
                   b.booking_date, b.status, b.created_at
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN services s ON b.service_id = s.id
            WHERE b.provider_id = ?
            ORDER BY b.created_at DESC
            LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $html = '';
    while ($row = $result->fetch_assoc()) {
        $html .= '<div class="activity-item">
            <div class="activity-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="activity-content">
                <div class="activity-title">
                    New booking for ' . htmlspecialchars($row['service_title']) . ' by ' . htmlspecialchars($row['customer_name']) . '
                </div>
                <div class="activity-meta">
                    <span class="activity-time">' . date('M d, Y', strtotime($row['created_at'])) . '</span>
                    <span class="badge">' . ucfirst($row['status']) . '</span>
                </div>
            </div>
        </div>';
    }
    
    $stmt->close();
    return $html ?: '<p>No recent bookings found.</p>';
}

function getRecentReviews($conn, $provider_id) {
    $sql = "SELECT r.id, u.name as customer_name, r.rating, r.comment, r.created_at
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.provider_id = ?
            ORDER BY r.created_at DESC
            LIMIT 3";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $html = '';
    while ($row = $result->fetch_assoc()) {
        $stars = str_repeat('<i class="fas fa-star" style="color: gold;"></i>', $row['rating']);
        $stars .= str_repeat('<i class="far fa-star" style="color: gold;"></i>', 5 - $row['rating']);
        
        $html .= '<div class="activity-item">
            <div class="activity-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="activity-content">
                <div class="activity-title">
                    ' . $stars . ' by ' . htmlspecialchars($row['customer_name']) . '
                </div>
                <div class="activity-text">' . htmlspecialchars($row['comment']) . '</div>
                <div class="activity-time">' . date('M d, Y', strtotime($row['created_at'])) . '</div>
            </div>
        </div>';
    }
    
    $stmt->close();
    return $html ?: '<p>No recent reviews found.</p>';
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedora - Photography Dashboard</title>
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
                    <i class="fas fa-camera"></i>
                    <span>Photography Provider</span>
                </div>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="service-provider_dashboard.html" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="service-provider_profile.html" class="nav-link">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="service-provider_gallery.html" class="nav-link">
                        <i class="fas fa-image"></i>
                        <span>Gallery</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="service-provider_services.html" class="nav-link">
                        <i class="fas fa-concierge-bell"></i>
                        <span>Services</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="service-provider_bookings.html" class="nav-link">
                        <i class="fas fa-calendar-check"></i>
                        <span>Bookings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="service-provider_payments.html" class="nav-link">
                        <i class="fas fa-credit-card"></i>
                        <span>Payments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="service-provider_reviews.html" class="nav-link">
                        <i class="fas fa-star"></i>
                        <span>Reviews</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="service-provider_promotions.html" class="nav-link">
                        <i class="fas fa-bullhorn"></i>
                        <span>Promotions</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="service-provider_settings.html" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1>Photography Dashboard</h1>
                    <p>Welcome back, Sarah's Wedding Photography</p>
                </div>
               
               
                <div class="user-profile">
                    <div class="user-info">
                         <div class="user-avatar">JS</div>
                        <div class="user-name">John Smith</div>
                        <div class="user-role">Photography Provider</div>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Total Bookings</div>
                        <div class="card-icon bookings">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="card-value">47</div>
                    <div class="card-change positive">
                        <i class="fas fa-arrow-up"></i> 12% from last month
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Total Revenue</div>
                        <div class="card-icon revenue">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="card-value">$18,750.00</div>
                    <div class="card-change positive">
                        <i class="fas fa-arrow-up"></i> 8% from last month
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Average Rating</div>
                        <div class="card-icon rating">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="card-value">4.7/5.0</div>
                    <div class="card-change positive">
                        <i class="fas fa-arrow-up"></i> 0.3 from last month
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Active Services</div>
                        <div class="card-icon services">
                            <i class="fas fa-concierge-bell"></i>
                        </div>
                    </div>
                    <div class="card-value">5</div>
                    <div class="card-change negative">
                        <i class="fas fa-arrow-down"></i> 2 from last month
                    </div>
                </div>
            </div>
            
            <!-- Recent Bookings Section -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Recent Bookings</h2>
                    <a href="service-provider_bookings.html" class="view-all">View All</a>
                </div>
                
                <div class="recent-bookings">
                    <div class="booking-item">
                        <div class="booking-info">
                            <div class="booking-customer">Alex Thompson & Jessica Williams</div>
                            <div class="booking-service">Premium Wedding Photography Package</div>
                            <div class="booking-date">Oct 15, 2023 • 2:30 PM</div>
                        </div>
                        <div class="booking-status status-confirmed">Confirmed</div>
                    </div>
                    
                    <div class="booking-item">
                        <div class="booking-info">
                            <div class="booking-customer">Michael Rodriguez</div>
                            <div class="booking-service">Engagement Session</div>
                            <div class="booking-date">Oct 18, 2023 • 4:00 PM</div>
                        </div>
                        <div class="booking-status status-pending">Pending</div>
                    </div>
                    
                    <div class="booking-item">
                        <div class="booking-info">
                            <div class="booking-customer">Emily Chen</div>
                            <div class="booking-service">Bridal Portraits</div>
                            <div class="booking-date">Oct 20, 2023 • 10:00 AM</div>
                        </div>
                        <div class="booking-status status-confirmed">Confirmed</div>
                    </div>
                    
                    <div class="booking-item">
                        <div class="booking-info">
                            <div class="booking-customer">David Park</div>
                            <div class="booking-service">Premium Photo Album Design</div>
                            <div class="booking-date">Oct 22, 2023 • 3:15 PM</div>
                        </div>
                        <div class="booking-status status-pending">Pending</div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Reviews Section -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Recent Reviews</h2>
                    <a href="service-provider_reviews.html" class="view-all">View All</a>
                </div>
                
                <div class="recent-reviews">
                    <div class="review-item">
                        <div class="review-info">
                            <div class="review-customer">Alex Thompson</div>
                            <div class="review-service">Premium Wedding Photography</div>
                            <div class="review-date">Oct 16, 2023</div>
                        </div>
                        <div class="review-rating">
                            <span class="rating-stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="review-item">
                        <div class="review-info">
                            <div class="review-customer">Jessica Williams</div>
                            <div class="review-service">Engagement Session</div>
                            <div class="review-date">Oct 14, 2023</div>
                        </div>
                        <div class="review-rating">
                            <span class="rating-stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="review-item">
                        <div class="review-info">
                            <div class="review-customer">Michael Rodriguez</div>
                            <div class="review-service">Premium Wedding Photography</div>
                            <div class="review-date">Oct 12, 2023</div>
                        </div>
                        <div class="review-rating">
                            <span class="rating-stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="far fa-star"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="review-item">
                        <div class="review-info">
                            <div class="review-customer">Emily Chen</div>
                            <div class="review-service">Bridal Portraits</div>
                            <div class="review-date">Oct 10, 2023</div>
                        </div>
                        <div class="review-rating">
                            <span class="rating-stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>