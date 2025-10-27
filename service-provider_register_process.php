<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Require database connection
require 'db_connection.php';

// Check if $pdo is valid
if (!$pdo) {
    error_log("Database connection failed in service-provider_register.php");
    header("Location: service-provider_register.php?error=" . urlencode("Database connection failed"));
    exit();
}

// Set PDO error mode to exception (redundant if already set in db_connection.php, but ensures consistency)
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Required fields
        $required_fields = [
            'company_name', 'owner_name', 'email', 'password',
            'confirm_password', 'phone', 'service_type', 'location'
        ];

        // Check for empty fields
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("All fields are required");
            }
        }

        // Validate password match
        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("Passwords do not match");
        }

        // Validate email
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Sanitize inputs (trim only, as PDO prepared statements handle SQL injection)
        $company_name = trim($_POST['company_name']);
        $owner_name = trim($_POST['owner_name']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $phone = trim($_POST['phone']);
        $service_type = trim($_POST['service_type']);
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location']);
        $profile_image_path = null;

        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/profile/';

            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    throw new Exception("Failed to create upload directory");
                }
            }

            $file_name = $_FILES['profile_image']['name'];
            $file_tmp = $_FILES['profile_image']['tmp_name'];
            $file_size = $_FILES['profile_image']['size'];
            $file_type = $_FILES['profile_image']['type'];

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB

            // Validate file type
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception("Invalid image type. Only JPG, PNG, and GIF are allowed.");
            }

            // Validate file size
            if ($file_size > $max_size) {
                throw new Exception("File too large. Max 2MB allowed.");
            }

            // Additional content-type check
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $file_mime = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);
            if (!in_array($file_mime, $allowed_types)) {
                throw new Exception("Invalid file content type.");
            }

            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_filename = 'profile_' . uniqid() . '.' . $file_ext;
            $destination = $upload_dir . $new_filename;

            // Move uploaded file
            if (!move_uploaded_file($file_tmp, $destination)) {
                throw new Exception("Failed to upload image.");
            }

            // Store relative path
            $profile_image_path = 'profile/' . $new_filename;
        }

        // Check if email already exists
        $check_email_sql = "SELECT id FROM s_provider_register WHERE email = :email";
        $check_email = $pdo->prepare($check_email_sql);
        if (!$check_email) {
            throw new Exception("Failed to prepare email check query: " . implode(", ", $pdo->errorInfo()));
        }

        $check_email->execute(['email' => $email]);
        if ($check_email->rowCount() > 0) {
            throw new Exception("Email already registered. Please login.");
        }

        // Insert into database
        $sql = "INSERT INTO s_provider_register (
            company_name, owner_name, email, password, phone,
            service_type, description, location, profile_image
        ) VALUES (:company_name, :owner_name, :email, :password, :phone, 
                 :service_type, :description, :location, :profile_image)";

        $stmt = $pdo->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare insert query: " . implode(", ", $pdo->errorInfo()));
        }

        // Execute with parameters
        $params = [
            'company_name' => $company_name,
            'owner_name' => $owner_name,
            'email' => $email,
            'password' => $password,
            'phone' => $phone,
            'service_type' => $service_type,
            'description' => $description,
            'location' => $location,
            'profile_image' => $profile_image_path
        ];

        if (!$stmt->execute($params)) {
            throw new Exception("Failed to execute insert query: " . implode(", ", $stmt->errorInfo()));
        }

        // Log successful insertion
        error_log("Successfully inserted provider: $email");

        // Create session
        $_SESSION['provider_id'] = $pdo->lastInsertId();
        $_SESSION['email'] = $email;
        $_SESSION['company_name'] = $company_name;
        $_SESSION['service_type'] = $service_type;
        $_SESSION['profile_image'] = $profile_image_path;

        // Verify dashboard file exists
        $dashboard_file = "dashboards/{$service_type}_dashboard.php";
        if (!file_exists($dashboard_file)) {
            throw new Exception("Dashboard file not found: $dashboard_file");
        }

        // Redirect to service-type dashboard
        header("Location: $dashboard_file");
        exit();

    } else {
        // If not a POST request, redirect to form
        header("Location: service-provider_register.php");
        exit();
    }

} catch (Exception $e) {
    // Log the error
    error_log("Error in service-provider_register.php: " . $e->getMessage());
    
    // Redirect with error message
    header("Location: service-provider_register.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>