<?php
require 'db.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['provider_id'])) {
    header('Location: service-provider_dashboard.php');
    exit();
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM service_providers WHERE email = ?");
        $stmt->execute([$email]);
        $provider = $stmt->fetch();
        
        if ($provider && password_verify($password, $provider['password'])) {
            if ($provider['is_approved']) {
                // Update last login
                $stmt = $pdo->prepare("UPDATE service_providers SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$provider['id']]);
                
                // Log login attempt
                $stmt = $pdo->prepare("INSERT INTO login_audit (provider_id, ip_address, user_agent) VALUES (?, ?, ?)");
                $stmt->execute([
                    $provider['id'],
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT']
                ]);
                
                // Set session
                $_SESSION['provider_id'] = $provider['id'];
                $_SESSION['email'] = $provider['email'];
                $_SESSION['company_name'] = $provider['company_name'];
                $_SESSION['logged_in'] = true;
                
                header('Location: service-provider_dashboard.php');
                exit();
            } else {
                $error = 'Your account is pending approval by admin';
            }
        } else {
            $error = 'Invalid email or password';
        }
    } catch(PDOException $e) {
        $error = 'Login error: ' . $e->getMessage();
    }
}
?>
