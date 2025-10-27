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

// Get bookings for this provider
$bookings_sql = "SELECT b.*, u.name as customer_name, u.email as customer_email, 
                u.phone as customer_phone, s.title as service_title
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                JOIN services s ON b.service_id = s.id
                WHERE b.service_provider_id = ?
                ORDER BY b.booking_date DESC, b.booking_time DESC";
$stmt = $conn->prepare($bookings_sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$bookings = $stmt->get_result();
$stmt->close();

// Handle booking status change
if (isset($_GET['action']) && isset($_GET['booking_id'])) {
    $booking_id = $_GET['booking_id'];
    $action = $_GET['action'];
    
    // Validate action
    $valid_actions = ['confirm', 'cancel', 'complete'];
    if (in_array($action, $valid_actions)) {
        $new_status = $action . 'd'; // confirmed, cancelled, completed
        
        $update_sql = "UPDATE bookings SET status = ? WHERE id = ? AND service_provider_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sii", $new_status, $booking_id, $provider_id);
        
        if ($stmt->execute()) {
            $success_message = "Booking has been " . $new_status . " successfully!";
        } else {
            $error_message = "Error updating booking: " . $conn->error;
        }
        $stmt->close();
        
        // Refresh bookings
        $stmt = $conn->prepare($bookings_sql);
        $stmt->bind_param("i", $provider_id);
        $stmt->execute();
        $bookings = $stmt->get_result();
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Wedora - Bookings</title>
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

        /* Bookings Section */
        .bookings-container {
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

        .filter-controls {
            display: flex;
            gap: 10px;
        }

        .filter-select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
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

        .btn-warning {
            background-color: var(--warning-color);
            color: var(--dark-color);
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

        /* Bookings Table */
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
        }

        .bookings-table th, 
        .bookings-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .bookings-table th {
            font-weight: 600;
            color: var(--dark-color);
            background-color: #f8f9fa;
        }

        .bookings-table tr:hover {
            background-color: #f8f9fa;
        }

        .customer-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            background-color: var(--secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-pending {
            background-color: rgba(253, 203, 110, 0.2);
            color: #b7950b;
        }

        .status-confirmed {
            background-color: rgba(0, 184, 148, 0.2);
            color: var(--success-color);
        }

        .status-cancelled {
            background-color: rgba(214, 48, 49, 0.2);
            color: var(--danger-color);
        }

        .status-completed {
            background-color: rgba(108, 92, 231, 0.2);
            color: var(--primary-color);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .no-bookings {
            text-align: center;
            padding: 40px;
            color: var(--text-light);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: modalFadeIn 0.3s;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: var(--text-light);
        }

        .close-btn:hover {
            color: var(--danger-color);
        }

        .modal-body {
            padding: 20px;
        }

        .booking-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .detail-item {
            margin-bottom: 15px;
        }

        .detail-label {
            font-size: 12px;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .detail-value {
            font-weight: 500;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
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

            .bookings-table {
                display: block;
                overflow-x: auto;
            }
            
            .booking-details-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
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
                    <i class="fas fa-ring"></i>
                    <span>Venue Provider</span>
                </div>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="service-provider_dashboard.html" class="nav-link ">
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
                    <a href="service-provider_bookings.html" class="nav-link active">
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
                    <h1>Bookings Management</h1>
                    <p>View and manage all your customer bookings</p>
                </div>
                <div class="user-profile">
                    <div class="user-info">
                        <div class="user-name">John Smith</div>
                        <div class="user-role">Venue Provider</div>
                    </div>
                    <img src="https://ui-avatars.com/api/?name=John+Smith&background=6c5ce7&color=fff" alt="Profile Image">
                    <a href="service-provider_login.html" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>

            <div class="bookings-container">
                <div class="section-header">
                    <h2 class="section-title">All Bookings</h2>
                    <div class="filter-controls">
                        <select class="filter-select">
                            <option>All Status</option>
                            <option>Pending</option>
                            <option>Confirmed</option>
                            <option>Cancelled</option>
                            <option>Completed</option>
                        </select>
                        <select class="filter-select">
                            <option>All Services</option>
                            <option>Wedding Venue</option>
                            <option>Reception Hall</option>
                            <option>Garden Party</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="bookings-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-avatar">
                                            E
                                        </div>
                                        <div>
                                            <div>Emma Johnson</div>
                                            <small>emma.j@example.com</small>
                                        </div>
                                    </div>
                                </td>
                                <td>Luxury Wedding Venue</td>
                                <td>
                                    Jun 15, 2023
                                    <br>
                                    <small>02:30 PM</small>
                                </td>
                                <td>
                                    <span class="status-badge status-pending">
                                        Pending
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="#" class="btn btn-success btn-sm">Confirm</a>
                                        <a href="#" class="btn btn-danger btn-sm">Cancel</a>
                                        <button class="btn btn-sm details-btn" data-booking-id="1001">Details</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-avatar">
                                            M
                                        </div>
                                        <div>
                                            <div>Michael Brown</div>
                                            <small>michael.b@example.com</small>
                                        </div>
                                    </div>
                                </td>
                                <td>Garden Wedding Package</td>
                                <td>
                                    Jul 22, 2023
                                    <br>
                                    <small>04:00 PM</small>
                                </td>
                                <td>
                                    <span class="status-badge status-confirmed">
                                        Confirmed
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="#" class="btn btn-primary btn-sm">Complete</a>
                                        <button class="btn btn-sm details-btn" data-booking-id="1002">Details</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-avatar">
                                            S
                                        </div>
                                        <div>
                                            <div>Sarah Williams</div>
                                            <small>sarah.w@example.com</small>
                                        </div>
                                    </div>
                                </td>
                                <td>Reception Hall Booking</td>
                                <td>
                                    Aug 05, 2023
                                    <br>
                                    <small>06:30 PM</small>
                                </td>
                                <td>
                                    <span class="status-badge status-completed">
                                        Completed
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm details-btn" data-booking-id="1003">Details</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Details Modal -->
    <div class="modal" id="detailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Booking Details</h3>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="booking-details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Booking ID</div>
                        <div class="detail-value" id="detail-id">#1001</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Customer Name</div>
                        <div class="detail-value" id="detail-customer">Emma Johnson</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Service</div>
                        <div class="detail-value" id="detail-service">Luxury Wedding Venue</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Booking Date</div>
                        <div class="detail-value" id="detail-date">Jun 15, 2023</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Booking Time</div>
                        <div class="detail-value" id="detail-time">02:30 PM</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Duration</div>
                        <div class="detail-value" id="detail-duration">5 hours</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Guests</div>
                        <div class="detail-value" id="detail-guests">120 people</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Status</div>
                        <div class="detail-value" id="detail-status"><span class="status-badge status-pending">Pending</span></div>
                    </div>
                    <div class="detail-item full-width">
                        <div class="detail-label">Special Requests</div>
                        <div class="detail-value" id="detail-requests">We would like to have a floral arch at the entrance and need assistance with parking arrangements for elderly guests.</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Total Amount</div>
                        <div class="detail-value" id="detail-amount">$2,500.00</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Payment Status</div>
                        <div class="detail-value" id="detail-payment">Deposit Paid</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn" id="closeModalBtn">Close</button>
                <button class="btn btn-primary" id="editBookingBtn">Edit Booking</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal elements
            const detailsModal = document.getElementById('detailsModal');
            const closeModalBtn = document.getElementById('closeModalBtn');
            const closeBtn = document.querySelector('.close-btn');
            
            // Sample booking data
            const bookings = {
                1001: {
                    id: 1001,
                    customer: "Emma Johnson",
                    service: "Luxury Wedding Venue",
                    date: "Jun 15, 2023",
                    time: "02:30 PM",
                    duration: "5 hours",
                    guests: "120 people",
                    status: "pending",
                    requests: "We would like to have a floral arch at the entrance and need assistance with parking arrangements for elderly guests.",
                    amount: "$2,500.00",
                    payment: "Deposit Paid"
                },
                1002: {
                    id: 1002,
                    customer: "Michael Brown",
                    service: "Garden Wedding Package",
                    date: "Jul 22, 2023",
                    time: "04:00 PM",
                    duration: "6 hours",
                    guests: "80 people",
                    status: "confirmed",
                    requests: "We would like to have an outdoor ceremony and indoor reception. Please set up a backup plan in case of rain.",
                    amount: "$3,200.00",
                    payment: "Fully Paid"
                },
                1003: {
                    id: 1003,
                    customer: "Sarah Williams",
                    service: "Reception Hall Booking",
                    date: "Aug 05, 2023",
                    time: "06:30 PM",
                    duration: "4 hours",
                    guests: "60 people",
                    status: "completed",
                    requests: "We need a microphone and projector for presentations during the event.",
                    amount: "$1,800.00",
                    payment: "Fully Paid"
                }
            };
            
            // Function to show modal with booking details
            function showBookingDetails(bookingId) {
                const booking = bookings[bookingId];
                if (booking) {
                    // Populate modal with booking details
                    document.getElementById('detail-id').textContent = '#' + booking.id;
                    document.getElementById('detail-customer').textContent = booking.customer;
                    document.getElementById('detail-service').textContent = booking.service;
                    document.getElementById('detail-date').textContent = booking.date;
                    document.getElementById('detail-time').textContent = booking.time;
                    document.getElementById('detail-duration').textContent = booking.duration;
                    document.getElementById('detail-guests').textContent = booking.guests;
                    document.getElementById('detail-requests').textContent = booking.requests;
                    document.getElementById('detail-amount').textContent = booking.amount;
                    document.getElementById('detail-payment').textContent = booking.payment;
                    
                    // Set status with appropriate badge
                    const statusElement = document.getElementById('detail-status');
                    statusElement.innerHTML = '';
                    const badge = document.createElement('span');
                    badge.className = `status-badge status-${booking.status}`;
                    badge.textContent = booking.status.charAt(0).toUpperCase() + booking.status.slice(1);
                    statusElement.appendChild(badge);
                    
                    // Show the modal
                    detailsModal.style.display = 'flex';
                }
            }
            
            // Function to hide modal
            function hideModal() {
                detailsModal.style.display = 'none';
            }
            
            // Event listeners for details buttons
            document.querySelectorAll('.details-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const bookingId = this.getAttribute('data-booking-id');
                    showBookingDetails(bookingId);
                });
            });
            
            // Event listeners for closing modal
            closeModalBtn.addEventListener('click', hideModal);
            closeBtn.addEventListener('click', hideModal);
            
            // Close modal when clicking outside of it
            window.addEventListener('click', function(event) {
                if (event.target === detailsModal) {
                    hideModal();
                }
            });
            
            // Action buttons functionality
            document.addEventListener('click', function(e) {
                // Confirm booking button
                if (e.target.classList.contains('btn-success')) {
                    e.preventDefault();
                    const bookingId = e.target.closest('tr').querySelector('.details-btn').getAttribute('data-booking-id');
                    alert(`Booking #${bookingId} has been confirmed successfully.`);
                    
                    // Update the status badge visually
                    const statusBadge = e.target.closest('tr').querySelector('.status-badge');
                    statusBadge.className = 'status-badge status-confirmed';
                    statusBadge.textContent = 'Confirmed';
                    
                    // Update action buttons
                    const actionButtons = e.target.closest('.action-buttons');
                    actionButtons.innerHTML = `
                        <a href="#" class="btn btn-primary btn-sm">Complete</a>
                        <button class="btn btn-sm details-btn" data-booking-id="${bookingId}">Details</button>
                    `;
                    
                    // Reattach event listener to the new details button
                    actionButtons.querySelector('.details-btn').addEventListener('click', function() {
                        showBookingDetails(bookingId);
                    });
                }
                
                // Cancel booking button
                if (e.target.classList.contains('btn-danger')) {
                    e.preventDefault();
                    const bookingId = e.target.closest('tr').querySelector('.details-btn').getAttribute('data-booking-id');
                    alert(`Booking #${bookingId} has been cancelled successfully.`);
                    
                    // Update the status badge visually
                    const statusBadge = e.target.closest('tr').querySelector('.status-badge');
                    statusBadge.className = 'status-badge status-cancelled';
                    statusBadge.textContent = 'Cancelled';
                    
                    // Update action buttons
                    const actionButtons = e.target.closest('.action-buttons');
                    actionButtons.innerHTML = `
                        <button class="btn btn-sm details-btn" data-booking-id="${bookingId}">Details</button>
                    `;
                    
                    // Reattach event listener to the new details button
                    actionButtons.querySelector('.details-btn').addEventListener('click', function() {
                        showBookingDetails(bookingId);
                    });
                }
                
                // Complete booking button
                if (e.target.classList.contains('btn-primary') && e.target.textContent === 'Complete') {
                    e.preventDefault();
                    const bookingId = e.target.closest('tr').querySelector('.details-btn').getAttribute('data-booking-id');
                    alert(`Booking #${bookingId} has been completed successfully.`);
                    
                    // Update the status badge visually
                    const statusBadge = e.target.closest('tr').querySelector('.status-badge');
                    statusBadge.className = 'status-badge status-completed';
                    statusBadge.textContent = 'Completed';
                    
                    // Update action buttons
                    const actionButtons = e.target.closest('.action-buttons');
                    actionButtons.innerHTML = `
                        <button class="btn btn-sm details-btn" data-booking-id="${bookingId}">Details</button>
                    `;
                    
                    // Reattach event listener to the new details button
                    actionButtons.querySelector('.details-btn').addEventListener('click', function() {
                        showBookingDetails(bookingId);
                    });
                }
            });
        });
    </script>
</body>
</html>