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

// Handle form submission for adding new service
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_service'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/services/";
        $imageFileType = strtolower(pathinfo($_FILES["service_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $imageFileType;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["service_image"]["tmp_name"], $target_file)) {
            $image_path = $new_filename;
        }
    }
    
    // Insert new service
    $insert_sql = "INSERT INTO services (provider_id, title, description, price, category, image_path, status) 
                   VALUES (?, ?, ?, ?, ?, ?, 'active')";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("isssss", $provider_id, $title, $description, $price, $category, $image_path);
    
    if ($stmt->execute()) {
        $success_message = "Service added successfully!";
    } else {
        $error_message = "Error adding service: " . $conn->error;
    }
    $stmt->close();
}

// Handle service status change
if (isset($_GET['action']) && $_GET['action'] == 'toggle_status' && isset($_GET['service_id'])) {
    $service_id = $_GET['service_id'];
    
    // Get current status
    $status_sql = "SELECT status FROM services WHERE id = ? AND provider_id = ?";
    $stmt = $conn->prepare($status_sql);
    $stmt->bind_param("ii", $service_id, $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $new_status = $row['status'] == 'active' ? 'inactive' : 'active';
        
        // Update status
        $update_sql = "UPDATE services SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $new_status, $service_id);
        $stmt->execute();
    }
    $stmt->close();
}

// Get all services for this provider
$services_sql = "SELECT * FROM services WHERE provider_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($services_sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$services = $stmt->get_result();
$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedora - My Services</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Reuse styles from dashboard.css */
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
        
        /* Sidebar styles */
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

        /* Main content styles */
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
        
        /* Services styles */
        .services-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .section {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--shadow);
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
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
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
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
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
        
        /* Services table */
        .services-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .services-table th, .services-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .services-table th {
            font-weight: 600;
            color: var(--dark-color);
            background-color: #f8f9fa;
        }
        
        .services-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .service-image {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            object-fit: cover;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: rgba(0, 184, 148, 0.1);
            color: var(--success-color);
        }
        
        .status-inactive {
            background-color: rgba(214, 48, 49, 0.1);
            color: var(--danger-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        /* Add service form */
        .add-service-form {
            display: none;
            margin-top: 20px;
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
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .services-table {
                display: block;
                overflow-x: auto;
            }
        }

        .btn-primary {
      background-image: linear-gradient(135deg, var(--primary-color), var(--accent-color));
      box-shadow: 0 4px 8px rgba(108, 92, 231, 0.3);
    }
    .btn-primary:hover {
      background-image: linear-gradient(135deg, #5a4bcf, #d02d8e);
      transform: scale(1.02);
    }
    .sidebar-header {
      text-align: center;
      padding: 0 20px 20px;
    }
    .logo {
      font-size: 24px;
      font-weight: bold;
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
      box-shadow: 0 0 15px rgba(162, 155, 254, 0.5);
    }

    .logout-btn {
            color: var(--danger-color);
            font-size: 18px;
            margin-left: 10px;
        }

        .logout-btn:hover {
            color: red;
        }
        
    .form-control:hover {
      border-color: var(--accent-color);
      background-color: #fafaff;
    }
    .status-badge {
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .services-table td, .services-table th {
      vertical-align: middle;
    }
  </style>
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
                    <i class="fas fa-<?php echo getServiceIcon($service_type); ?>"></i>
                    <span><?php echo ucfirst($service_type); ?> Provider</span>
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
                    <a href="service-provider_services.html" class="nav-link active">
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
                    <h1>My Services</h1>
                    <p>Manage your wedding services and packages</p>
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
            
            <div class="services-container">
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">My Services</h2>
                        <button id="toggleAddService" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Service
                        </button>
                    </div>
                    
                    <!-- Add Service Form (hidden by default) -->
                    <div id="addServiceForm" class="add-service-form">
                        <form action="service_provider_services.html" method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="title">Service Title</label>
                                <input type="text" id="title" name="title" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" class="form-control" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="price">Price (Rs.)</label>
                                <input type="number" id="price" name="price" class="form-control" min="0" step="0.01" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select id="category" name="category" class="form-control" required>
                                    <option value="">Select category</option>
                                    <option value="venue">Venue</option>
                                    <option value="catering">Catering</option>
                                    <option value="photography">Photography</option>
                                    <option value="decor">Decor</option>
                                    <option value="music">Music</option>
                                    <option value="dressing">Dressing</option>
                                    <option value="saloon">Saloon</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="service_image">Service Image</label>
                                <input type="file" id="service_image" name="service_image" class="form-control" accept="image/*">
                                <small>Recommended size: 800x600 pixels, max 2MB</small>
                            </div>
                            
                            <button type="submit" name="add_service" class="btn btn-primary">Add Service</button>
                            <button type="button" id="cancelAddService" class="btn btn-danger">Cancel</button>
                        </form>
                    </div>
                    
                    <!-- Services Table -->
                    <div class="table-responsive">
                        <table class="services-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($services->num_rows > 0): ?>
                                    <?php while ($service = $services->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <?php if ($service['image_path']): ?>
                                                    <img src="uploads/services/<?php echo $service['image_path']; ?>" alt="<?php echo htmlspecialchars($service['title']); ?>" class="service-image">
                                                <?php else: ?>
                                                    <div class="no-image">No Image</div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($service['title']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($service['description'], 0, 50)); ?>...</td>
                                            <td>Rs. <?php echo number_format($service['price'], 2); ?></td>
                                            <td><?php echo ucfirst($service['category']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $service['status']; ?>">
                                                    <?php echo ucfirst($service['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="service_provider_services.php?action=toggle_status&service_id=<?php echo $service['id']; ?>" 
                                                       class="btn btn-sm <?php echo $service['status'] == 'active' ? 'btn-danger' : 'btn-success'; ?>">
                                                        <?php echo $service['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>
                                                    </a>
                                                    <a href="service-provider_edit_service.html?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-primary">
                                                        Edit
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center;">No services found. Add your first service!</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Toggle add service form
        document.getElementById('toggleAddService').addEventListener('click', function() {
            const form = document.getElementById('addServiceForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        });
        
        document.getElementById('cancelAddService').addEventListener('click', function() {
            document.getElementById('addServiceForm').style.display = 'none';
        });
    </script>
</body>
</html>