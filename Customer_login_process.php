<?php
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Please enter both email and password";
        header('Location: Customer_login.html?error=1');
        exit;
    }
    
    // Check user credentials
    $stmt = $pdo->prepare("SELECT customer_id, first_name, last_name, email, password_hash FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    $customer = $stmt->fetch();
    
    if ($customer && password_verify($password, $customer['password_hash'])) {
        // Login successful
        $_SESSION['customer_id'] = $customer['customer_id'];
        $_SESSION['customer_name'] = $customer['first_name'] . ' ' . $customer['last_name'];
        $_SESSION['customer_email'] = $customer['email'];
        
        header('Location: Customer_dashboard.html?success=login');
        exit;
    } else {
        // Login failed
        $_SESSION['login_error'] = "Invalid email or password";
        header('Location: Customer_login.html?error=1');
        exit;
    }
}
?>