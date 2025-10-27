<?php
session_start();
require 'db.php'; 

// Check if user is logged in and is a service provider
if (!isset($_SESSION['provider_id'])) {
    die(json_encode(['success' => false, 'message' => 'You must be logged in to edit services.']));
}

$provider_id = $_SESSION['provider_id'];

// Handle service update
if (isset($_POST['update_service'])) {
    $service_id = $_POST['service_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $status = $_POST['status'];

    // Validate inputs
    if (empty($title) || empty($description) || empty($price)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    // Check if the service belongs to the logged-in provider
    $check_sql = "SELECT * FROM services WHERE id = ? AND provider_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $service_id, $provider_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Service not found or you do not have permission to edit it.']);
        exit;
    }

    // Handle file upload
    $image_path = $_POST['current_image']; // Keep current image by default

    if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['service_image'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];

        // Check file type
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_ext)) {
            if ($file_size <= 2097152) { // 2MB
                $new_file_name = uniqid('service_', true) . '.' . $file_ext;
                $upload_path = 'uploads/services/' . $new_file_name;

                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $image_path = $new_file_name;

                    if (!empty($_POST['current_image']) && $_POST['current_image'] !== 'default_service.jpg') {
                        $old_image_path = 'uploads/services/' . $_POST['current_image'];
                        if (file_exists($old_image_path)) unlink($old_image_path);
                    }
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'File size must be less than 2MB.']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.']);
            exit;
        }
    }

    // Update service in database
    $sql = "UPDATE services SET title = ?, description = ?, price = ?, category = ?, status = ?, image_path = ? WHERE id = ? AND provider_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdsssii", $title, $description, $price, $category, $status, $image_path, $service_id, $provider_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Service updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating service: ' . $conn->error]);
    }

    $stmt->close();
    exit;
}

// Handle service deletion
if (isset($_POST['delete_service'])) {
    $service_id = $_POST['service_id'];

    $check_sql = "SELECT image_path FROM services WHERE id = ? AND provider_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $service_id, $provider_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Service not found or you do not have permission to delete it.']);
        exit;
    }

    $service = $check_result->fetch_assoc();
    $image_path = $service['image_path'];

    $sql = "DELETE FROM services WHERE id = ? AND provider_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $service_id, $provider_id);

    if ($stmt->execute()) {
        if (!empty($image_path) && $image_path !== 'default_service.jpg') {
            $file_path = 'uploads/services/' . $image_path;
            if (file_exists($file_path)) unlink($file_path);
        }
        echo json_encode(['success' => true, 'message' => 'Service deleted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting service: ' . $conn->error]);
    }

    $stmt->close();
    exit;
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedora - Edit Service</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .modal-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .modal-close:hover {
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-hint {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 5px;
        }
        
        .image-preview {
            width: 100%;
            max-width: 200px;
            height: 150px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px dashed var(--border-color);
            margin-top: 10px;
            display: block;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            text-decoration: none;
            font-size: 14px;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            box-shadow: 0 4px 8px rgba(108, 92, 231, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a4bcf, #d02d8e);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(108, 92, 231, 0.4);
        }
        
        .btn-secondary {
            background-color: white;
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background-color: #f9f9f9;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c02b2b;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background-color: rgba(0, 184, 148, 0.15);
            color: var(--success-color);
        }
        
        .status-inactive {
            background-color: rgba(214, 48, 49, 0.15);
            color: var(--danger-color);
        }
        
        @media (max-width: 576px) {
            .modal-container {
                max-width: 100%;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-edit"></i>
                Edit Service
            </h2>
            <button class="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <form id="editServiceForm">
                <div class="form-group">
                    <label for="serviceTitle" class="form-label">Service Title</label>
                    <input type="text" id="serviceTitle" class="form-control" value="Premium Wedding Photography" required>
                </div>
                
                <div class="form-group">
                    <label for="serviceDescription" class="form-label">Description</label>
                    <textarea id="serviceDescription" class="form-control" required>Our premium wedding photography package includes 8 hours of coverage, a second photographer, an engagement session, all edited high-resolution digital images, and a beautiful custom wedding album.</textarea>
                </div>
                
                <div class="form-group">
                    <label for="servicePrice" class="form-label">Price (Rs.)</label>
                    <input type="number" id="servicePrice" class="form-control" value="125000" min="0" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="serviceCategory" class="form-label">Category</label>
                    <select id="serviceCategory" class="form-control" required>
                        <option value="photography" selected>Photography</option>
                        <option value="venue">Venue</option>
                        <option value="catering">Catering</option>
                        <option value="decor">Decor</option>
                        <option value="music">Music</option>
                        <option value="dressing">Dressing</option>
                        <option value="saloon">Saloon</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="serviceStatus" class="form-label">Status</label>
                    <select id="serviceStatus" class="form-control" required>
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="serviceImage" class="form-label">Service Image</label>
                    <input type="file" id="serviceImage" class="form-control" accept="image/*">
                    <p class="form-hint">Recommended size: 800x600 pixels, max 2MB</p>
                    
                    <img id="imagePreview" src="https://images.unsplash.com/photo-1554303442-6dec7568b3c9?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" alt="Service image" class="image-preview">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Current Status</label>
                    <div>
                        <span class="status-badge status-active">Active</span>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Service</button>
                    <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                    <button type="button" class="btn btn-danger" id="deleteBtn">Delete Service</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editServiceForm = document.getElementById('editServiceForm');
            const serviceImage = document.getElementById('serviceImage');
            const imagePreview = document.getElementById('imagePreview');
            const cancelBtn = document.getElementById('cancelBtn');
            const deleteBtn = document.getElementById('deleteBtn');
            const modalClose = document.querySelector('.modal-close');
            
            // Handle form submission
          editServiceForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    alert('Service updated successfully!');
    window.location.href = "service-provider_services.html"; // redirect
});

            
            // Handle image preview
            serviceImage.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        imagePreview.src = event.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
            
           // Handle cancel button
cancelBtn.addEventListener('click', function() {
    window.location.href = "service-provider_services.html"; // redirect
});

// Handle modal close button
modalClose.addEventListener('click', function() {
    window.location.href = "service-provider_services.html"; // redirect
});

// Handle delete service
deleteBtn.addEventListener('click', function() {
    if (confirm('Are you sure you want to delete this service? This action cannot be undone.')) {
        alert('Service deleted successfully!');
        window.location.href = "service-provider_services.html"; // redirect
    }
});
        });
    </script>
</body>
</html>