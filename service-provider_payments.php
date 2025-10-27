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

// Get payments for this provider
$payments_sql = "SELECT p.*, b.booking_date, b.booking_time, 
                u.name as customer_name, s.title as service_title
                FROM payments p
                JOIN bookings b ON p.booking_id = b.id
                JOIN users u ON b.user_id = u.id
                JOIN services s ON b.service_id = s.id
                WHERE b.service_provider_id = ?
                ORDER BY p.payment_date DESC";
$stmt = $conn->prepare($payments_sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$payments = $stmt->get_result();
$stmt->close();

// Calculate payment stats
$stats_sql = "SELECT 
              SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END) as total_earnings,
              COUNT(CASE WHEN p.status = 'completed' THEN 1 END) as completed_payments,
              COUNT(CASE WHEN p.status = 'pending' THEN 1 END) as pending_payments
              FROM payments p
              JOIN bookings b ON p.booking_id = b.id
              WHERE b.service_provider_id = ?";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Wedora - Payments</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
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
        }

        .logout-btn:hover {
            color: red;
        }

        /* Payments Section */
        .payments-container {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--shadow);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
        }

        .payment-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--text-light);
        }

        .total-earnings .stat-value {
            color: var(--success-color);
        }

        .completed-payments .stat-value {
            color: var(--primary-color);
        }

        .pending-payments .stat-value {
            color: var(--warning-color);
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
        }

        .payment-table th, 
        .payment-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .payment-table th {
            font-weight: 600;
            color: var(--dark-color);
            background-color: #f8f9fa;
        }

        .payment-table tr:hover {
            background-color: #f8f9fa;
        }

        .payment-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-completed {
            background-color: rgba(0, 184, 148, 0.2);
            color: var(--success-color);
        }

        .status-pending {
            background-color: rgba(253, 203, 110, 0.2);
            color: #b7950b;
        }

        .status-failed {
            background-color: rgba(214, 48, 49, 0.2);
            color: var(--danger-color);
        }

        .payment-method {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .payment-method i {
            font-size: 18px;
        }

        .credit-card {
            color: var(--primary-color);
        }

        .paypal {
            color: #003087;
        }

        .bank-transfer {
            color: var(--success-color);
        }

        .no-payments {
            text-align: center;
            padding: 40px;
            color: var(--text-light);
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

            .payment-table {
                display: block;
                overflow-x: auto;
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
                    <i class="fas fa-<?php echo getServiceIcon($provider['service_type']); ?>"></i>
                    <span><?php echo ucfirst($provider['service_type']); ?> Provider</span>
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
                    <a href="service-provider_payments.html" class="nav-link active">
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
                    <h1>Payment History</h1>
                    <p>View and manage all payment transactions</p>
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

            <div class="payments-container">
                <div class="section-header">
                    <h2 class="section-title">Payment Summary</h2>
                </div>

                <div class="payment-stats">
                    <div class="stat-card total-earnings">
                        <div class="stat-value">Rs. <?php echo number_format($stats['total_earnings'] ?? 0, 2); ?></div>
                        <div class="stat-label">Total Earnings</div>
                    </div>
                    <div class="stat-card completed-payments">
                        <div class="stat-value"><?php echo $stats['completed_payments'] ?? 0; ?></div>
                        <div class="stat-label">Completed Payments</div>
                    </div>
                    <div class="stat-card pending-payments">
                        <div class="stat-value"><?php echo $stats['pending_payments'] ?? 0; ?></div>
                        <div class="stat-label">Pending Payments</div>
                    </div>
                </div>

                <div class="section-header">
                    <h2 class="section-title">Recent Transactions</h2>
                    <select class="filter-select">
                        <option>All Transactions</option>
                        <option>Completed</option>
                        <option>Pending</option>
                        <option>Failed</option>
                    </select>
                </div>

                <div class="table-responsive">
                    <table class="payment-table">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($payments->num_rows > 0): ?>
                                <?php while ($payment = $payments->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $payment['transaction_id'] ? substr($payment['transaction_id'], 0, 8) : 'N/A'; ?></td>
                                        <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['service_title']); ?></td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?>
                                            <br>
                                            <small><?php echo date('h:i A', strtotime($payment['payment_date'])); ?></small>
                                        </td>
                                        <td>Rs. <?php echo number_format($payment['amount'], 2); ?></td>
                                        <td>
                                            <div class="payment-method">
                                                <?php if ($payment['payment_method'] == 'credit_card'): ?>
                                                    <i class="far fa-credit-card credit-card"></i>
                                                    <span>Credit Card</span>
                                                <?php elseif ($payment['payment_method'] == 'paypal'): ?>
                                                    <i class="fab fa-paypal paypal"></i>
                                                    <span>PayPal</span>
                                                <?php else: ?>
                                                    <i class="fas fa-university bank-transfer"></i>
                                                    <span>Bank Transfer</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="payment-status status-<?php echo $payment['status']; ?>">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-payments">
                                        <i class="fas fa-credit-card" style="font-size: 48px; color: #e0e0e0; margin-bottom: 15px;"></i>
                                        <h3>No Payments Yet</h3>
                                        <p>You haven't received any payments yet.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>