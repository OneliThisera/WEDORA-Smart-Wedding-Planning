<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Wedora - Customer Registration</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
  <style>
    /* Your existing CSS remains unchanged */
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
    .error {
        color: red;
        font-size: 14px;
        margin-bottom: 20px;
        text-align: center;
    }
  </style>
</head>
<body>
  <div class="auth-container">
    <div class="logo">Wedora</div>
    <div class="auth-card">
      <div class="auth-header">
        <h2>Customer Registration</h2>
        <p>Create your Wedora customer account</p>
      </div>
      <?php
      // Display errors if any
      if (isset($_SESSION['registration_errors'])) {
          echo '<div class="error">';
          foreach ($_SESSION['registration_errors'] as $error) {
              echo "<p>$error</p>";
          }
          echo '</div>';
          unset($_SESSION['registration_errors']); // Clear errors after displaying
      }
      ?>
      <form action="customer_register_process.php" method="POST" class="auth-form">
        <div class="form-group">
          <label for="first_name">First Name</label>
          <input type="text" id="first_name" name="first_name" required />
        </div>
        <div class="form-group">
          <label for="last_name">Last Name</label>
          <input type="text" id="last_name" name="last_name" required />
        </div>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" required />
        </div>
        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input type="text" id="phone" name="phone" />
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required />
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input type="password" id="confirm_password" name="confirm_password" required />
        </div>
        <button type="submit" class="btn btn-primary">Register</button>
      </form>
      <div class="auth-footer">
        <p>Already have an account? <a href="Customer_login.html">Login here</a></p>
      </div>
    </div>
  </div>
</body>
</html>