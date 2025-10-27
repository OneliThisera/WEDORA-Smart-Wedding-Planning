<?php
// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1); // Enable if using HTTPS
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['provider_id'])) {
    error_log("Unauthorized access attempt: No provider_id in session");
    header("Location: service-provider_login.php");
    exit();
}

// Include database connection
require_once '../db_connection.php';

// Cache session data
$provider_id = $_SESSION['provider_id'];

// Define utility functions
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

// Get service provider data
try {
    $sql = "SELECT * FROM service_providers WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $provider_id]);
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$provider) {
        error_log("No provider found for ID: $provider_id");
        header("Location: service-provider_login.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching provider: " . $e->getMessage());
    die("Error loading profile. Please try again later.");
}

// Handle form submission
$success_message = '';
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Process form data
    $company_name = trim($_POST['company_name'] ?? '');
    $owner_name = trim($_POST['owner_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validate inputs
    if (empty($company_name) || empty($owner_name) || empty($email) || empty($phone) || empty($location)) {
        $error_message = "All required fields must be filled.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif (!preg_match("/^[0-9]{10,15}$/", $phone)) {
        $error_message = "Invalid phone number format.";
    } else {
        // Check for email uniqueness
        try {
            $sql = "SELECT id FROM service_providers WHERE email = :email AND id != :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['email' => $email, 'id' => $provider_id]);
            if ($stmt->fetch()) {
                $error_message = "Email is already in use by another provider.";
            }
        } catch (PDOException $e) {
            error_log("Error checking email uniqueness: " . $e->getMessage());
            $error_message = "Error validating email. Please try again.";
        }
        
        // Handle file upload
        $new_filename = $provider['profile_image'] ?? null;
        if (!$error_message && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
            $target_dir = "Uploads/";
            $max_file_size = 2 * 1024 * 1024; // 2MB
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            
            $imageFileType = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
            if (!in_array($imageFileType, $allowed_types)) {
                $error_message = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            } elseif ($_FILES["profile_image"]["size"] > $max_file_size) {
                $error_message = "File size exceeds 2MB limit.";
            } elseif (getimagesize($_FILES["profile_image"]["tmp_name"]) === false) {
                $error_message = "File is not a valid image.";
            } else {
                $new_filename = uniqid() . '.' . $imageFileType;
                $target_file = $target_dir . $new_filename;
                if (!move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                    $error_message = "Failed to upload image.";
                } elseif ($provider['profile_image'] && file_exists("Uploads/" . $provider['profile_image'])) {
                    unlink("Uploads/" . $provider['profile_image']); // Delete old image
                }
            }
        }
        
        // Update profile if no errors
        if (!$error_message) {
            try {
                if ($new_filename) {
                    $update_sql = "UPDATE service_providers SET 
                                  company_name = :company_name, owner_name = :owner_name, email = :email, 
                                  phone = :phone, location = :location, description = :description, 
                                  profile_image = :profile_image
                                  WHERE id = :id";
                    $stmt = $pdo->prepare($update_sql);
                    $stmt->execute([
                        'company_name' => $company_name,
                        'owner_name' => $owner_name,
                        'email' => $email,
                        'phone' => $phone,
                        'location' => $location,
                        'description' => $description,
                        'profile_image' => $new_filename,
                        'id' => $provider_id
                    ]);
                } else {
                    $update_sql = "UPDATE service_providers SET 
                                  company_name = :company_name, owner_name = :owner_name, email = :email, 
                                  phone = :phone, location = :location, description = :description
                                  WHERE id = :id";
                    $stmt = $pdo->prepare($update_sql);
                    $stmt->execute([
                        'company_name' => $company_name,
                        'owner_name' => $owner_name,
                        'email' => $email,
                        'phone' => $phone,
                        'location' => $location,
                        'description' => $description,
                        'id' => $provider_id
                    ]);
                }
                
                $success_message = "Profile updated successfully!";
                // Refresh provider data
                $sql = "SELECT * FROM service_providers WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['id' => $provider_id]);
                $provider = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Update session data
                $_SESSION['company_name'] = $company_name;
                $_SESSION['email'] = $email;
                $_SESSION['service_type'] = $provider['service_type'];
                $_SESSION['profile_image'] = $new_filename ?? $provider['profile_image'] ?? 'default_profile.jpg';
            } catch (PDOException $e) {
                error_log("Error updating profile: " . $e->getMessage());
                $error_message = "Error updating profile. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedora - Profile</title>
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
            background: none;
            border: none;
            cursor: pointer;
        }

        .logout-btn:hover {
            color: red;
        }

        .profile-container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .profile-sidebar {
            width: 300px;
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 5px solid var(--secondary-color);
            box-shadow: 0 0 15px rgba(162, 155, 254, 0.5);
        }

        .profile-name {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .profile-type {
            color: var(--primary-color);
            font-weight: 500;
            margin-bottom: 20px;
        }

        .profile-stats {
            margin-top: 20px;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .stat-label {
            color: var(--text-light);
        }

        .stat-value {
            font-weight: 500;
        }

        .profile-content {
            flex: 1;
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--shadow);
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .form-group {
            margin-bottom: 20px;
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
            transition: border-color 0.3s, background-color 0.3s;
        }

        .form-control:hover {
            border-color: var(--accent-color);
            background-color: #fafaff;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background-image: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            box-shadow: 0 4px 8px rgba(108, 92, 231, 0.3);
            transition: all 0.3s ease-in-out;
        }

        .btn-primary:hover {
            background-image: linear-gradient(135deg, #5a4bcf, #d02d8e);
            transform: scale(1.02);
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

        @media (max-width: 992px) {
            .profile-container {
                flex-direction: column;
            }

            .profile-sidebar {
                width: 100%;
            }
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

            .nav-link {
                justify-content: center;
            }

            .nav-link i {
                margin-right: 0;
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
                    <i class="fas fa-<?php echo htmlspecialchars(getServiceIcon($provider['service_type'])); ?>"></i>
                    <span><?php echo htmlspecialchars(ucfirst($provider['service_type'])); ?> Provider</span>
                </div>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="decor_dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="service-provider_profile.html" class="nav-link active">
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
                    <h1>Profile Settings</h1>
                    <p>Manage your business profile information</p>
                </div>
                <div class="user-profile">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($provider['owner_name']); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars(ucfirst($provider['service_type'])); ?> Provider</div>
                    </div>
                    <img src="<?php echo $provider['profile_image'] ? 'Uploads/' . htmlspecialchars($provider['profile_image']) : 'https://ui-avatars.com/api/?name=' . urlencode($provider['owner_name']) . '&background=6c5ce7&color=fff'; ?>" alt="Profile Image">
                    <form action="logout.php" method="POST">
                        <button type="submit" class="logout-btn"><i class="fas fa-sign-out-alt"></i></button>
                    </form>
                </div>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="profile-container">
                <div class="profile-sidebar">
                    <img src="<?php echo $provider['profile_image'] ? 'Uploads/' . htmlspecialchars($provider['profile_image']) : 'https://ui-avatars.com/api/?name=' . urlencode($provider['company_name']) . '&background=6c5ce7&color=fff'; ?>" class="profile-image" alt="Profile Image"/>
                    <h2 class="profile-name"><?php echo htmlspecialchars($provider['company_name']); ?></h2>
                    <div class="profile-type"><?php echo htmlspecialchars(ucfirst($provider['service_type'])); ?> Services</div>
                    <div class="profile-stats">
                        <div class="stat-item"><span class="stat-label">Member Since</span><span class="stat-value"><?php echo date('M Y', strtotime($provider['created_at'])); ?></span></div>
                        <div class="stat-item"><span class="stat-label">Total Bookings</span><span class="stat-value"><?php echo getTotalBookings($pdo, $provider_id); ?></span></div>
                        <div class="stat-item"><span class="stat-label">Average Rating</span><span class="stat-value"><?php echo number_format(getAverageRating($pdo, $provider_id), 1); ?>/5</span></div>
                    </div>
                </div>

                <div class="profile-content">
                    <h2 class="section-title">Business Information</h2>
                    <form action="service-provider_profile.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="company_name">Company Name</label>
                            <input type="text" id="company_name" name="company_name" class="form-control" value="<?php echo htmlspecialchars($provider['company_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="owner_name">Owner/Manager Name</label>
                            <input type="text" id="owner_name" name="owner_name" class="form-control" value="<?php echo htmlspecialchars($provider['owner_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Business Email</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($provider['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($provider['phone']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="location">Business Location</label>
                            <input type="text" id="location" name="location" class="form-control" value="<?php echo htmlspecialchars($provider['location']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Business Description</label>
                            <textarea id="description" name="description" class="form-control"><?php echo htmlspecialchars($provider['description']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="profile_image">Profile Image</label>
                            <input type="file" id="profile_image" name="profile_image" class="form-control" accept="image/*">
                            <small>Recommended size: 300x300 pixels, max 2MB</small>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>