<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Get pending seller verifications
$stmt = $conn->prepare("SELECT * FROM users WHERE user_type = 'seller' AND aadhar_path IS NOT NULL");
$stmt->execute();
$pending_sellers = $stmt->get_result();

// Get all users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

// Get all cars
$cars = $conn->query("SELECT cars.*, users.username AS seller_name FROM cars JOIN users ON cars.seller_id = users.id ORDER BY created_at DESC");

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve_seller'])) {
        $user_id = (int)$_POST['user_id'];
        $stmt = $conn->prepare("UPDATE users SET user_type = 'verified_seller' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Seller approved successfully!";
            header("Location: admin.php");
            exit();
        }
    }
    
    if (isset($_POST['reject_seller'])) {
        $user_id = (int)$_POST['user_id'];
        $stmt = $conn->prepare("UPDATE users SET aadhar_path = NULL WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Seller verification rejected!";
            header("Location: admin.php");
            exit();
        }
    }
    
    if (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "User deleted successfully!";
            header("Location: admin.php");
            exit();
        }
    }
    
    if (isset($_POST['delete_car'])) {
        $car_id = (int)$_POST['car_id'];
        $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
        $stmt->bind_param("i", $car_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Car deleted successfully!";
            header("Location: admin.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CarBazaar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-car"></i>
                </div>
                <div class="logo-text">Car<span>Bazaar</span></div>
            </a>
            
            <nav>
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="index.php#cars"><i class="fas fa-car"></i> Cars</a></li>
                    <li><a href="index.php#about"><i class="fas fa-info-circle"></i> About</a></li>
                    <li><a href="index.php#contact"><i class="fas fa-phone-alt"></i> Contact</a></li>
                </ul>
            </nav>
            
            <div class="user-actions">
                <div class="user-greeting">
                    Welcome, <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <span class="badge admin-badge">Admin</span>
                </div>
                <a href="profile.php" class="btn btn-outline">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="logout.php" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-dashboard">
            <h1>Admin Dashboard</h1>
            
            <div class="admin-tabs">
                <button class="tab-btn active" onclick="openAdminTab('pending-sellers')">Pending Verifications</button>
                <button class="tab-btn" onclick="openAdminTab('users')">Users</button>
                <button class="tab-btn" onclick="openAdminTab('cars')">Cars</button>
            </div>
            
            <div class="tab-content active" id="pending-sellers">
                <h2>Pending Seller Verifications</h2>
                
                <?php if ($pending_sellers->num_rows > 0): ?>
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Location</th>
                                    <th>Aadhar Card</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($seller = $pending_sellers->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($seller['username']); ?></td>
                                        <td><?php echo htmlspecialchars($seller['email']); ?></td>
                                        <td><?php echo htmlspecialchars($seller['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($seller['location']); ?></td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($seller['aadhar_path']); ?>" target="_blank" class="btn btn-outline">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $seller['id']; ?>">
                                                <button type="submit" name="approve_seller" class="btn btn-success">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $seller['id']; ?>">
                                                <button type="submit" name="reject_seller" class="btn btn-danger">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle" style="font-size: 60px; color: var(--light-gray); margin-bottom: 20px;"></i>
                        <h3>No pending verifications</h3>
                        <p>There are no sellers waiting for verification.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content" id="users">
                <h2>All Users</h2>
                
                <?php if ($users->num_rows > 0): ?>
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Type</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars(ucfirst($user['user_type'])); ?>
                                            <?php if ($user['user_type'] == 'verified_seller'): ?>
                                                <span class="badge verified-badge">Verified</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="delete_user" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users" style="font-size: 60px; color: var(--light-gray); margin-bottom: 20px;"></i>
                        <h3>No users found</h3>
                        <p>There are no users registered in the system.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content" id="cars">
                <h2>All Cars</h2>
                
                <?php if ($cars->num_rows > 0): ?>
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Car</th>
                                    <th>Seller</th>
                                    <th>Price</th>
                                    <th>Year</th>
                                    <th>Status</th>
                                    <th>Posted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($car = $cars->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></td>
                                        <td><?php echo htmlspecialchars($car['seller_name']); ?></td>
                                        <td>₹<?php echo number_format($car['price']); ?></td>
                                        <td><?php echo htmlspecialchars($car['year']); ?></td>
                                        <td>
                                            <?php if ($car['is_sold']): ?>
                                                <span class="badge sold-badge">Sold</span>
                                            <?php else: ?>
                                                <span class="badge available-badge">Available</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($car['created_at'])); ?></td>
                                        <td>
                                            <a href="view_car.php?id=<?php echo $car['id']; ?>" class="btn btn-outline">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                                <button type="submit" name="delete_car" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this car?');">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-car" style="font-size: 60px; color: var(--light-gray); margin-bottom: 20px;"></i>
                        <h3>No cars found</h3>
                        <p>There are no cars listed in the system.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-bottom">
                <p>© <?php echo date('Y'); ?> CarBazaar. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
    <script>
        function openAdminTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show the selected tab content
            document.getElementById(tabId).classList.add('active');
            
            // Add active class to the clicked tab button
            event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>
