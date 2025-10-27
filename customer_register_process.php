<?php
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Basic validation
    $errors = [];
    
    if (empty($first_name)) {
        $errors[] = "First name is required";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Email already registered";
        }
    }
    
    // If no errors, register the user
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO customers (first_name, last_name, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)");
        
        try {
            $stmt->execute([$first_name, $last_name, $email, $phone, $password_hash]);
            
            // Auto-login after registration
            $customer_id = $pdo->lastInsertId();
            $_SESSION['customer_id'] = $customer_id;
            $_SESSION['customer_name'] = $first_name . ' ' . $last_name;
            $_SESSION['customer_email'] = $email;
            
            header('Location: 	Customer_dashboard.php?success=registered');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Registration failed. Please try again.";
        }
    }
    
    // If there are errors, redirect back with error message
    if (!empty($errors)) {
        $_SESSION['registration_errors'] = $errors;
        header('Location: Customer_register.php');
        exit;
    }
}
?>