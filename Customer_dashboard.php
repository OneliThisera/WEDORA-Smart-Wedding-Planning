<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Wedora - Customer Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
  <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        color: #333;
    }
    
    /* Header */
    .header {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        padding: 15px 0;
        position: sticky;
        top: 0;
        z-index: 100;
    }
    .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 20px;
    }
    .logo {
        font-family: 'Playfair Display', serif;
        font-size: 28px;
        font-weight: 600;
        color: #f645a3;
    }
    .nav-menu {
        display: flex;
        list-style: none;
        gap: 30px;
        align-items: center;
    }
    .nav-menu a {
        text-decoration: none;
        color: #333;
        font-weight: 500;
        transition: color 0.3s;
    }
    .nav-menu a:hover {
        color: #6c5ce7;
    }
    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6c5ce7, #f645a3);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
    }
    .logout-btn {
        padding: 8px 16px;
        background: #6c5ce7;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s;
    }
    .logout-btn:hover {
        background: #5649c0;
    }

    /* Main Content */
    .main-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 20px;
    }
    
    /* Welcome Section */
    .welcome-section {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
    .welcome-title {
        font-size: 28px;
        color: #6c5ce7;
        margin-bottom: 10px;
    }
    .welcome-subtitle {
        color: #666;
        font-size: 16px;
        margin-bottom: 20px;
    }
    .quick-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .stat-card {
        background: linear-gradient(135deg, #6c5ce7, #a29bfe);
        color: white;
        padding: 20px;
        border-radius: 12px;
        text-align: center;
    }
    .stat-number {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .stat-label {
        font-size: 14px;
        opacity: 0.9;
    }

    /* Quick Actions */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .action-card {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s, box-shadow 0.3s;
        cursor: pointer;
    }
    .action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    }
    .action-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6c5ce7, #f645a3);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
        color: white;
        font-size: 20px;
    }
    .action-title {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
    }
    .action-description {
        color: #666;
        font-size: 14px;
        line-height: 1.5;
    }

    /* Recent Activity */
    .recent-activity {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
    .section-title {
        font-size: 20px;
        font-weight: 600;
        color: #333;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .activity-list {
        list-style: none;
    }
    .activity-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px solid #eee;
    }
    .activity-item:last-child {
        border-bottom: none;
    }
    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c5ce7;
    }
    .activity-content {
        flex: 1;
    }
    .activity-title {
        font-weight: 500;
        color: #333;
        margin-bottom: 3px;
    }
    .activity-time {
        font-size: 12px;
        color: #999;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .nav-menu {
            display: none;
        }
        .quick-stats {
            grid-template-columns: repeat(2, 1fr);
        }
        .quick-actions {
            grid-template-columns: 1fr;
        }
        .welcome-title {
            font-size: 24px;
        }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header class="header">
    <div class="nav-container">
      <div class="logo">Wedora</div>
      <ul class="nav-menu">
        <li><a href="#"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="Browse_service.html"><i class="fas fa-search"></i> Browse Services</a></li>
        <li><a href="Appointment_booking.html"><i class="fas fa-calendar"></i> My Bookings</a></li>
        <li><a href="Favorites.html"><i class="fas fa-heart"></i> Favorites</a></li>
        <li><a href="User_profile_management.html"><i class="fas fa-user"></i> Profile</a></li>
      </ul>
      <div class="user-info">
        <div class="user-avatar">JD</div>
        <span>John Doe</span>
        <button onclick="window.location='HomePage.html'" class="logout-btn">
          <i class="fas fa-sign-out-alt"></i> Logout
        </button>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <div class="main-container">
    <!-- Welcome Section -->
    <div class="welcome-section">
      <h1 class="welcome-title">Welcome back, John! 🎉</h1>
      <p class="welcome-subtitle">Let's continue planning your perfect wedding day</p>
      
      <div class="quick-stats">
        <div class="stat-card">
          <div class="stat-number">5</div>
          <div class="stat-label">Saved Vendors</div>
        </div>
        <div class="stat-card">
          <div class="stat-number">3</div>
          <div class="stat-label">Upcoming Meetings</div>
        </div>
        <div class="stat-card">
          <div class="stat-number">8</div>
          <div class="stat-label">Services Booked</div>
        </div>
        <div class="stat-card">
          <div class="stat-number">75%</div>
          <div class="stat-label">Planning Progress</div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
      <div class="action-card">
        <div class="action-icon">
          <i class="fas fa-search"></i>
        </div>
        <h3 class="action-title">Find Wedding Services</h3>
        <p class="action-description">Browse through hundreds of verified wedding vendors in your area</p>
      </div>

      <div class="action-card">
        <div class="action-icon">
          <i class="fas fa-calendar-plus"></i>
        </div>
        <h3 class="action-title">Book Appointment</h3>
        <p class="action-description">Schedule consultations with your favorite vendors</p>
      </div>

      <div class="action-card">
        <div class="action-icon">
          <i class="fas fa-heart"></i>
        </div>
        <h3 class="action-title">Manage Favorites</h3>
        <p class="action-description">Keep track of your preferred vendors and services</p>
      </div>

      <div class="action-card">
        <div class="action-icon">
          <i class="fas fa-star"></i>
        </div>
        <h3 class="action-title">Leave Reviews</h3>
        <p class="action-description">Share your experience and help other couples</p>
      </div>

      <div class="action-card">
        <div class="action-icon">
          <i class="fas fa-credit-card"></i>
        </div>
        <h3 class="action-title">Payment History</h3>
        <p class="action-description">View your transaction history and receipts</p>
      </div>

      <div class="action-card">
        <div class="action-icon">
          <i class="fas fa-user-cog"></i>
        </div>
        <h3 class="action-title">Profile Settings</h3>
        <p class="action-description">Update your personal information and preferences</p>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity">
      <h2 class="section-title">
        <i class="fas fa-clock"></i>
        Recent Activity
      </h2>
      <ul class="activity-list">
        <li class="activity-item">
          <div class="activity-icon">
            <i class="fas fa-heart"></i>
          </div>
          <div class="activity-content">
            <div class="activity-title">Added "Elegant Photography Studio" to favorites</div>
            <div class="activity-time">2 hours ago</div>
          </div>
        </li>
        <li class="activity-item">
          <div class="activity-icon">
            <i class="fas fa-calendar"></i>
          </div>
          <div class="activity-content">
            <div class="activity-title">Booked appointment with "Dream Wedding Planners"</div>
            <div class="activity-time">1 day ago</div>
          </div>
        </li>
        <li class="activity-item">
          <div class="activity-icon">
            <i class="fas fa-star"></i>
          </div>
          <div class="activity-content">
            <div class="activity-title">Left review for "Golden Moments Photography"</div>
            <div class="activity-time">3 days ago</div>
          </div>
        </li>
        <li class="activity-item">
          <div class="activity-icon">
            <i class="fas fa-search"></i>
          </div>
          <div class="activity-content">
            <div class="activity-title">Searched for "wedding decorators in Colombo"</div>
            <div class="activity-time">5 days ago</div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</body>
</html>