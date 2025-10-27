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

// Handle form submission for new promotion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_promotion'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $discount = $_POST['discount'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $service_id = $_POST['service_id'];
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['promo_image']) && $_FILES['promo_image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/promotions/";
        $imageFileType = strtolower(pathinfo($_FILES["promo_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $imageFileType;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["promo_image"]["tmp_name"], $target_file)) {
            $image_path = $new_filename;
        }
    }
    
    // Insert new promotion
    $insert_sql = "INSERT INTO promotions (provider_id, service_id, title, description, 
                  discount, start_date, end_date, image_path, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("iissdsss", $provider_id, $service_id, $title, $description, 
                      $discount, $start_date, $end_date, $image_path);
    
    if ($stmt->execute()) {
        $success_message = "Promotion submitted for approval!";
    } else {
        $error_message = "Error creating promotion: " . $conn->error;
    }
    $stmt->close();
}

// Get promotions for this provider
$promotions_sql = "SELECT p.*, s.title as service_title
                  FROM promotions p
                  JOIN services s ON p.service_id = s.id
                  WHERE p.provider_id = ?
                  ORDER BY p.created_at DESC";
$stmt = $conn->prepare($promotions_sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$promotions = $stmt->get_result();
$stmt->close();

// Get active services for dropdown
$services_sql = "SELECT id, title FROM services 
                WHERE provider_id = ? AND status = 'active'";
$stmt = $conn->prepare($services_sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$services = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Wedora - Promotions</title>
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

        /* Promotions Section */
        .promotions-container {
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

        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #5649c0;
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: var(--dark-color);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: rgba(0, 184, 148, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .alert-danger {
            background-color: rgba(214, 48, 49, 0.1);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }

        /* Add Promotion Form */
        .add-promotion-form {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        /* Promotions Grid */
        .promotions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .promotion-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.3s;
        }

        .promotion-card:hover {
            transform: translateY(-5px);
        }

        .promotion-image {
            height: 180px;
            background-color: #f0f0f0;
            position: relative;
            overflow: hidden;
        }

        .promotion-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .promotion-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-pending {
            background-color: rgba(253, 203, 110, 0.2);
            color: #b7950b;
        }

        .badge-active {
            background-color: rgba(0, 184, 148, 0.2);
            color: var(--success-color);
        }

        .badge-expired {
            background-color: rgba(214, 48, 49, 0.2);
            color: var(--danger-color);
        }

        .promotion-content {
            padding: 15px;
        }

        .promotion-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .promotion-service {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 10px;
        }

        .promotion-description {
            font-size: 14px;
            margin-bottom: 15px;
            color: var(--text-color);
        }

        .promotion-dates {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-light);
            margin-bottom: 15px;
        }

        .promotion-discount {
            font-size: 24px;
            font-weight: 700;
            color: var(--accent-color);
            text-align: center;
            margin: 10px 0;
        }

        .promotion-actions {
            display: flex;
            gap: 10px;
        }

        .no-promotions {
            text-align: center;
            padding: 40px;
            color: var(--text-light);
            grid-column: 1 / -1;
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

            .form-row {
                flex-direction: column;
                gap: 0;
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
                    <a href="service-provider_reviews.html" class="nav-link">
                        <i class="fas fa-star"></i>
                        <span>Reviews</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="service-provider_promotions.html" class="nav-link active">
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
                    <h1>Promotions</h1>
                    <p>Create and manage special offers for your services</p>
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



            <div class="promotions-container">
                <div class="section-header">
                    <h2 class="section-title">Create New Promotion</h2>
                </div>

                <div class="add-promotion-form">
                    <form action="service_provider_promotions.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">Promotion Title</label>
                            <input type="text" id="title" name="title" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" required></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="service_id">Service</label>
                                <select id="service_id" name="service_id" class="form-control" required>
                                    <option value="">Select Service</option>
                                    <?php while ($service = $services->fetch_assoc()): ?>
                                        <option value="<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['title']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="discount">Discount (%)</label>
                                <input type="number" id="discount" name="discount" class="form-control" min="1" max="100" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="end_date">End Date</label>
                                <input type="date" id="end_date" name="end_date" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="promo_image">Promotion Image</label>
                            <input type="file" id="promo_image" name="promo_image" class="form-control" accept="image/*">
                            <small>Recommended size: 800x600 pixels, max 2MB</small>
                        </div>

                        <button type="submit" name="add_promotion" class="btn btn-primary">Submit Promotion</button>
                    </form>
                </div>

                <div class="section-header">
                    <h2 class="section-title">Your Promotions</h2>
                </div>

                <div class="promotions-grid">
                    <?php if ($promotions->num_rows > 0): ?>
                        <?php while ($promotion = $promotions->fetch_assoc()): 
                            $current_date = date('Y-m-d');
                            $status = '';
                            $badge_class = '';
                            
                            if ($promotion['status'] == 'approved') {
                                if ($current_date < $promotion['start_date']) {
                                    $status = 'pending';
                                    $badge_class = 'badge-pending';
                                } elseif ($current_date > $promotion['end_date']) {
                                    $status = 'expired';
                                    $badge_class = 'badge-expired';
                                } else {
                                    $status = 'active';
                                    $badge_class = 'badge-active';
                                }
                            } else {
                                $status = $promotion['status'];
                                $badge_class = 'badge-pending';
                            }
                        ?>
                            <div class="promotion-card">
                                <div class="promotion-image">
                                    <?php if ($promotion['image_path']): ?>
                                        <img src="uploads/promotions/<?php echo $promotion['image_path']; ?>" alt="<?php echo htmlspecialchars($promotion['title']); ?>">
                                    <?php else: ?>
                                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: linear-gradient(135deg, var(--secondary-color), var(--primary-color)); color: white; font-size: 24px;">
                                            <i class="fas fa-percentage"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="promotion-badge <?php echo $badge_class; ?>"><?php echo ucfirst($status); ?></span>
                                </div>
                                <div class="promotion-content">
                                    <h3 class="promotion-title"><?php echo htmlspecialchars($promotion['title']); ?></h3>
                                    <div class="promotion-service">For: <?php echo htmlspecialchars($promotion['service_title']); ?></div>
                                    <div class="promotion-discount"><?php echo $promotion['discount']; ?>% OFF</div>
                                    <div class="promotion-description"><?php echo htmlspecialchars($promotion['description']); ?></div>
                                    <div class="promotion-dates">
                                        <span>Start: <?php echo date('M d, Y', strtotime($promotion['start_date'])); ?></span>
                                        <span>End: <?php echo date('M d, Y', strtotime($promotion['end_date'])); ?></span>
                                    </div>
                                    <div class="promotion-actions">
                                        <?php if ($status == 'pending' || $status == 'active'): ?>
                                            <a href="#" class="btn btn-primary btn-sm">Edit</a>
                                        <?php endif; ?>
                                        <a href="#" class="btn btn-danger btn-sm">Delete</a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-promotions">
                            <i class="fas fa-bullhorn" style="font-size: 48px; color: #e0e0e0; margin-bottom: 15px;"></i>
                            <h3>No Promotions Yet</h3>
                            <p>Create your first promotion to attract more customers!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>