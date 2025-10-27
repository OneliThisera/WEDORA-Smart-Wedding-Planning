<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['service_provider_id'])) {
    header("Location: service-provider_login.php");
    exit();
}

// Get service provider data
$provider_id = $_SESSION['service_provider_id'];
$sql = "SELECT * FROM service_providers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();
$provider = $result->fetch_assoc();
$stmt->close();

// Get reviews for this provider
$reviews_sql = "SELECT r.*, u.name as customer_name, u.profile_image as customer_image, 
               s.title as service_title
               FROM reviews r
               JOIN users u ON r.user_id = u.id
               JOIN services s ON r.service_id = s.id
               WHERE r.service_provider_id = ?
               ORDER BY r.created_at DESC";
$stmt = $conn->prepare($reviews_sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$reviews = $stmt->get_result();
$stmt->close();

// Calculate average rating
$avg_rating_sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                  FROM reviews WHERE service_provider_id = ?";
$stmt = $conn->prepare($avg_rating_sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$rating_result = $stmt->get_result();
$rating_data = $rating_result->fetch_assoc();
$stmt->close();

$average_rating = number_format($rating_data['avg_rating'] ?? 0, 1);
$total_reviews = $rating_data['total_reviews'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Wedora - Reviews</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
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
            --shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding-top: 30px;
            box-shadow: var(--shadow);
            position: fixed;
            height: 100%;
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
        }

        .logo i {
            margin-right: 10px;
        }

        .service-type {
            margin-top: 10px;
            font-size: 14px;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
        }

        .nav-item {
            margin-bottom: 10px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: background 0.3s;
        }

        .nav-link i {
            margin-right: 10px;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            border-left: 4px solid white;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 25px;
        }

        .page-title h1 {
            font-size: 24px;
            color: var(--dark-color);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            background-color: var(--light-color);
            padding: 10px 15px;
            border-radius: 8px;
        }

        .user-profile img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
        }

        .logout-btn {
            color: var(--danger-color);
            font-size: 18px;
            margin-left: 10px;
        }

        .logout-btn:hover {
            color: red;
        }

        /* Reviews Section */
        .reviews-container {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--shadow);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
        }

        .rating-summary {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }

        .average-rating {
            text-align: center;
        }

        .rating-value {
            font-size: 48px;
            font-weight: 700;
            color: var(--primary-color);
            line-height: 1;
        }

        .rating-stars {
            color: #FFD700;
            font-size: 24px;
            margin: 5px 0;
        }

        .rating-count {
            color: var(--text-light);
            font-size: 14px;
        }

        .rating-bars {
            flex: 1;
        }

        .rating-bar {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .rating-label {
            width: 80px;
            font-size: 14px;
        }

        .rating-progress {
            flex: 1;
            height: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin: 0 10px;
        }

        .rating-fill {
            height: 100%;
            background-color: #FFD700;
        }

        .rating-percent {
            width: 40px;
            font-size: 14px;
            text-align: right;
        }

        .review-list {
            margin-top: 20px;
        }

        .review-item {
            display: flex;
            padding: 20px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .reviewer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            background-color: var(--secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .review-content {
            flex: 1;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .reviewer-name {
            font-weight: 600;
        }

        .review-rating {
            color: #FFD700;
        }

        .review-service {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 10px;
        }

        .review-text {
            margin-bottom: 10px;
        }

        .review-date {
            font-size: 12px;
            color: var(--text-light);
        }

        .no-reviews {
            text-align: center;
            padding: 40px;
            color: var(--text-light);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }

            .main-content {
                margin-left: 70px;
            }

            .nav-link span {
                display: none;
            }

            .rating-summary {
                flex-direction: column;
                align-items: flex-start;
            }

            .rating-bars {
                width: 100%;
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
                    <i class="fas fa-<?php echo getServiceIcon($provider['service_type']); ?>"></i>
                    <span><?php echo ucfirst($provider['service_type']); ?> Provider</span>
                </div>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="service-provider_dashboard.html" class="nav-link">
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
                    <a href="service-provider_reviews.html" class="nav-link active">
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
                    <h1>Customer Reviews</h1>
                    <p>See what your customers are saying about your services</p>
                </div>
                <div class="user-profile">
                    <div class="user-info">
                        <div class="user-name"><?php echo $provider['owner_name']; ?></div>
                        <div class="user-role"><?php echo ucfirst($provider['service_type']); ?> Provider</div>
                    </div>
                    <img src="<?php echo $provider['profile_image'] ? 'uploads/' . $provider['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($provider['owner_name']) . '&background=6c5ce7&color=fff'; ?>" alt="Profile Image">
                    <a href="service-provider_login.html" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>

            <div class="reviews-container">
                <div class="section-header">
                    <h2 class="section-title">Customer Feedback</h2>
                </div>

                <div class="rating-summary">
                    <div class="average-rating">
                        <div class="rating-value"><?php echo $average_rating; ?></div>
                        <div class="rating-stars">
                            <?php
                            $full_stars = floor($average_rating);
                            $half_star = ($average_rating - $full_stars) >= 0.5;
                            
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $full_stars) {
                                    echo '<i class="fas fa-star"></i>';
                                } elseif ($half_star && $i == $full_stars + 1) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        <div class="rating-count"><?php echo $total_reviews; ?> reviews</div>
                    </div>

                    <div class="rating-bars">
    <?php for ($i = 5; $i >= 1; $i--): ?>
        <?php 
            // Example: calculate percentage for each star rating
            $count = isset($ratings[$i]) ? $ratings[$i] : 0; // number of reviews for this star
            $total = array_sum($ratings); // total reviews
            $percent = ($total > 0) ? ($count / $total) * 100 : 0;
        ?>
        <div class="rating-bar">
            <div class="rating-label"><?php echo $i; ?> star</div>
            <div class="rating-progress">
                <div class="rating-fill" style="width: <?php echo $percent; ?>%"></div>
            </div>
            <div class="rating-percent"><?php echo round($percent); ?>%</div>
        </div>
    <?php endfor; ?>
</div>


                <div class="review-list">
                    <?php if ($reviews->num_rows > 0): ?>
                        <?php while ($review = $reviews->fetch_assoc()): ?>
                            <div class="review-item">
                                <div class="reviewer-avatar">
                                    <?php if ($review['customer_image']): ?>
                                        <img src="uploads/<?php echo $review['customer_image']; ?>" alt="<?php echo htmlspecialchars($review['customer_name']); ?>">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($review['customer_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="review-content">
                                    <div class="review-header">
                                        <div class="reviewer-name"><?php echo htmlspecialchars($review['customer_name']); ?></div>
                                        <div class="review-rating">
                                            <?php
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $review['rating']) {
                                                    echo '<i class="fas fa-star"></i>';
                                                } else {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="review-service">Service: <?php echo htmlspecialchars($review['service_title']); ?></div>
                                    <div class="review-text"><?php echo htmlspecialchars($review['comment']); ?></div>
                                    <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-reviews">
                            <i class="fas fa-star" style="font-size: 48px; color: #e0e0e0; margin-bottom: 15px;"></i>
                            <h3>No Reviews Yet</h3>
                            <p>Your customers haven't left any reviews yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>