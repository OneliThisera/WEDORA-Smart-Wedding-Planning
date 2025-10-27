<?php
require 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM service_providers WHERE email = ?");
        $stmt->execute([$email]);
        $provider = $stmt->fetch();
        
        if ($provider) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token
            $stmt = $pdo->prepare("UPDATE service_providers SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $provider['id']]);
            
            // Send email (in production)
            $reset_link = "https://yourdomain.com/reset_password.php?token=$token";
            // mail() or PHPMailer implementation would go here
            
            $success = 'Password reset instructions sent to your email';
        } else {
            $error = 'No account found with that email';
        }
    } catch(PDOException $e) {
        $error = 'Error processing your request: ' . $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedora - Change Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
                        url('https://images.unsplash.com/photo-1519225421980-715cb0215aed?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            color: #fff;
        }
        
        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 10px;
            color: #f645a3;
        }
        
        .tagline {
            text-align: center;
            font-size: 18px;
            margin-bottom: 30px;
            font-weight: 300;
            letter-spacing: 1px;
        }
        
        .auth-container {
            width: 100%;
            max-width: 450px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            padding: 40px;
            color: #333;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .auth-header h2 {
            color: #6c5ce7;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .auth-header p {
            color: #666;
            font-size: 14px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none; /* Hidden by default */
        }
        
        .alert-success {
            background-color: #e6fffa;
            color: #00b894;
            border: 1px solid #00b894;
        }
        
        .alert-danger {
            background-color: #fff5f5;
            color: #ff5252;
            border: 1px solid #ff5252;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-size: 14px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #6c5ce7;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
            border: none;
            width: 100%;
        }
        
        .btn-primary {
            background-color: #6c5ce7;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #5649c0;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .auth-footer a {
            color: #6c5ce7;
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .password-strength {
            height: 5px;
            background: #eee;
            margin-top: 5px;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .strength-meter {
            height: 100%;
            width: 0;
            background: transparent;
            transition: all 0.3s;
        }
        
        .password-requirements {
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 3px;
        }
        
        .requirement i {
            margin-right: 5px;
            font-size: 10px;
        }
        
        .fa-check {
            color: #00b894;
        }
        
        .fa-times {
            color: #ff5252;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="logo">Wedora</div>
        <div class="tagline">Secure your account with a new password</div>
        
        <div class="auth-card">
            <div class="auth-header">
                <h2>Change Password</h2>
                <p>Create a strong new password for your account</p>
            </div>
            
            <div id="successAlert" class="alert alert-success">
                Password changed successfully! Redirecting to login page...
            </div>
            
            <div id="errorAlert" class="alert alert-danger">
                Please fix the errors below
            </div>
            
            <form id="changePasswordForm" class="auth-form">
                <div class="form-group">
                    <label for="currentPassword">Current Password</label>
                    <input type="password" id="currentPassword" name="currentPassword" required>
                </div>
                
                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" name="newPassword" required>
                    <div class="password-strength">
                        <div id="strengthMeter" class="strength-meter"></div>
                    </div>
                    <div class="password-requirements">
                        <div class="requirement">
                            <i id="lengthCheck" class="fas fa-times"></i>
                            <span>At least 8 characters</span>
                        </div>
                        <div class="requirement">
                            <i id="uppercaseCheck" class="fas fa-times"></i>
                            <span>At least 1 uppercase letter</span>
                        </div>
                        <div class="requirement">
                            <i id="numberCheck" class="fas fa-times"></i>
                            <span>At least 1 number</span>
                        </div>
                        <div class="requirement">
                            <i id="specialCheck" class="fas fa-times"></i>
                            <span>At least 1 special character</span>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword">Confirm New Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                    <div id="passwordMatch" style="color: #ff5252; font-size: 12px; margin-top: 5px; display: none;">
                        Passwords do not match
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Change Password</button>
            </form>
            
            <div class="auth-footer">
                <p><a href="service-provider_login.html"><i class="fas fa-arrow-left"></i> Back to login</a></p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('changePasswordForm');
            const newPassword = document.getElementById('newPassword');
            const confirmPassword = document.getElementById('confirmPassword');
            const passwordMatch = document.getElementById('passwordMatch');
            const successAlert = document.getElementById('successAlert');
            const errorAlert = document.getElementById('errorAlert');
            
            // Password strength checker
            newPassword.addEventListener('input', function() {
                const password = this.value;
                const strengthMeter = document.getElementById('strengthMeter');
                const checks = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[^A-Za-z0-9]/.test(password)
                };
                
                // Update requirement icons
                document.getElementById('lengthCheck').className = checks.length ? 'fas fa-check' : 'fas fa-times';
                document.getElementById('uppercaseCheck').className = checks.uppercase ? 'fas fa-check' : 'fas fa-times';
                document.getElementById('numberCheck').className = checks.number ? 'fas fa-check' : 'fas fa-times';
                document.getElementById('specialCheck').className = checks.special ? 'fas fa-check' : 'fas fa-times';
                
                // Calculate strength
                const strength = Object.values(checks).filter(Boolean).length;
                const strengthPercent = (strength / 4) * 100;
                
                // Update strength meter
                strengthMeter.style.width = strengthPercent + '%';
                strengthMeter.style.backgroundColor = 
                    strengthPercent < 25 ? '#ff5252' :
                    strengthPercent < 50 ? '#ff9e22' :
                    strengthPercent < 75 ? '#ffd600' : '#00b894';
            });
            
            // Confirm password checker
            confirmPassword.addEventListener('input', function() {
                if (newPassword.value !== this.value && this.value.length > 0) {
                    passwordMatch.style.display = 'block';
                } else {
                    passwordMatch.style.display = 'none';
                }
            });
            
            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Hide alerts
                successAlert.style.display = 'none';
                errorAlert.style.display = 'none';
                
                // Validate form
                const currentPassword = document.getElementById('currentPassword').value;
                const newPasswordValue = newPassword.value;
                const confirmPasswordValue = confirmPassword.value;
                
                let isValid = true;
                
                if (!currentPassword) {
                    isValid = false;
                }
                
                if (newPasswordValue.length < 8) {
                    isValid = false;
                }
                
                if (newPasswordValue !== confirmPasswordValue) {
                    passwordMatch.style.display = 'block';
                    isValid = false;
                }
                
                if (isValid) {
                    // In a real application, you would send this to your server
                    console.log('Password change submitted');
                    
                    // Show success message
                    successAlert.style.display = 'block';
                    
                    // Simulate successful password change
                    setTimeout(function() {
                        window.location.href = 'service-provider_login.html?password=changed';
                    }, 2000);
                } else {
                    errorAlert.style.display = 'block';
                }
            });
        });
    </script>
</body>
</html>