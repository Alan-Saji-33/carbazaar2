<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get user's cars
$stmt = $conn->prepare("SELECT * FROM cars WHERE seller_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cars_result = $stmt->get_result();

// Get favorite cars
$stmt = $conn->prepare("SELECT cars.* FROM favorites 
                       JOIN cars ON favorites.car_id = cars.id 
                       WHERE favorites.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$favorites_result = $stmt->get_result();

// Get messages
$stmt = $conn->prepare("SELECT m.*, c.brand, c.model, u.username AS sender_name 
                       FROM messages m
                       JOIN cars c ON m.car_id = c.id
                       JOIN users u ON m.sender_id = u.id
                       WHERE m.receiver_id = ?
                       ORDER BY m.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$messages_result = $stmt->get_result();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $location = trim($_POST['location']);
    
    // Handle profile image upload
    $profile_image = $user['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "Uploads/profiles/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_filename = uniqid() . '.' . $file_ext;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                // Delete old profile image if it's not the default
                if ($profile_image != 'images/default-profile.jpg' && file_exists($profile_image)) {
                    unlink($profile_image);
                }
                $profile_image = $target_file;
            }
        }
    }
    
    // Handle Aadhar upload for sellers wanting verification
    $aadhar_path = $user['aadhar_path'];
    if ($_SESSION['user_type'] == 'seller' && isset($_FILES['aadhar_card']) && $_FILES['aadhar_card']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "Uploads/aadhar/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['aadhar_card']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_filename = uniqid() . '.' . $file_ext;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['aadhar_card']['tmp_name'], $target_file)) {
                // Delete old Aadhar file if exists
                if (!empty($aadhar_path) && file_exists($aadhar_path)) {
                    unlink($aadhar_path);
                }
                $aadhar_path = $target_file;
            }
        }
    }
    
    // Update user in database
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, location = ?, profile_image = ?, aadhar_path = ? WHERE id = ?");
    $stmt->bind_param("ssssssi", $username, $email, $phone, $location, $profile_image, $aadhar_path, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['phone'] = $phone;
        $_SESSION['profile_image'] = $profile_image;
        
        $_SESSION['message'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit();
    } else {
        $error = "Error updating profile: " . $conn->error;
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password == $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Password changed successfully!";
                header("Location: profile.php");
                exit();
            } else {
                $password_error = "Error changing password: " . $conn->error;
            }
        } else {
            $password_error = "New passwords do not match.";
        }
    } else {
        $password_error = "Current password is incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - CarBazaar</title>
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
                    <?php if ($_SESSION['user_type'] == 'verified_seller'): ?>
                        <span class="badge verified-badge">Verified</span>
                    <?php endif; ?>
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
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-page">
            <div class="profile-header">
                <div class="profile-image">
                    <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="<?php echo htmlspecialchars($user['username']); ?>">
                </div>
                
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    <?php if (!empty($user['phone'])): ?>
                        <p><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($user['phone']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($user['location'])): ?>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($user['location']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($_SESSION['user_type'] == 'seller' && empty($user['aadhar_path'])): ?>
                        <div class="alert alert-warning">
                            <p>You can list up to 3 cars. To become a verified seller and list more cars, please upload your Aadhar card.</p>
                        </div>
                    <?php elseif ($_SESSION['user_type'] == 'seller' && !empty($user['aadhar_path']) && $user['user_type'] != 'verified_seller'): ?>
                        <div class="alert alert-info">
                            <p>Your Aadhar card has been submitted for verification. Please wait for admin approval.</p>
                        </div>
                    <?php elseif ($_SESSION['user_type'] == 'verified_seller'): ?>
                        <div class="alert alert-success">
                            <p>You are a verified seller. You can list unlimited cars.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="profile-tabs">
                <button class="tab-btn active" onclick="openTab('profile-details')">Profile Details</button>
                <?php if ($_SESSION['user_type'] == 'seller' || $_SESSION['user_type'] == 'verified_seller'): ?>
                    <button class="tab-btn" onclick="openTab('my-cars')">My Cars</button>
                <?php endif; ?>
                <button class="tab-btn" onclick="openTab('favorites')">Favorites</button>
                <button class="tab-btn" onclick="openTab('messages')">Messages</button>
            </div>
            
            <div class="tab-content active" id="profile-details">
                <div class="profile-form">
                    <h2>Update Profile</h2>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="location">Location</label>
                                <input type="text" id="location" name="location" class="form-control" value="<?php echo htmlspecialchars($user['location']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="profile_image">Profile Image</label>
                            <input type="file" id="profile_image" name="profile_image" class="form-control" accept="image/*">
                        </div>
                        
                        <?php if ($_SESSION['user_type'] == 'seller' && empty($user['aadhar_path'])): ?>
                            <div class="form-group">
                                <label for="aadhar_card">Aadhar Card (for verification)</label>
                                <input type="file" id="aadhar_card" name="aadhar_card" class="form-control" accept="image/*,.pdf">
                                <small>Upload your Aadhar card to become a verified seller and list more than 3 cars</small>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="password-form">
                    <h2>Change Password</h2>
                    
                    <?php if (isset($password_error)): ?>
                        <div class="alert alert-error">
                            <?php echo htmlspecialchars($password_error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($_SESSION['user_type'] == 'seller' || $_SESSION['user_type'] == 'verified_seller'): ?>
                <div class="tab-content" id="my-cars">
                    <h2>My Cars</h2>
                    
                    <?php if ($cars_result->num_rows > 0): ?>
                        <div class="cars-grid">
                            <?php while ($car = $cars_result->fetch_assoc()): ?>
                                <div class="car-card">
                                    <?php if ($car['is_sold']): ?>
                                        <div class="sold-badge">SOLD</div>
                                    <?php else: ?>
                                        <div class="car-badge">NEW</div>
                                    <?php endif; ?>
                                    
                                    <div class="car-image">
                                        <img src="<?php echo htmlspecialchars($car['image_path']); ?>" alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>">
                                    </div>
                                    
                                    <div class="car-details">
                                        <h3 class="car-title"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h3>
                                        <div class="car-price">₹<?php echo number_format($car['price']); ?></div>
                                        
                                        <div class="car-specs">
                                            <span class="car-spec"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($car['year']); ?></span>
                                            <span class="car-spec"><i class="fas fa-tachometer-alt"></i> <?php echo number_format($car['km_driven']); ?> km</span>
                                            <span class="car-spec"><i class="fas fa-gas-pump"></i> <?php echo htmlspecialchars($car['fuel_type']); ?></span>
                                        </div>
                                        
                                        <div class="car-actions">
                                            <a href="view_car.php?id=<?php echo $car['id']; ?>" class="btn btn-outline">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="edit_car.php?id=<?php echo $car['id']; ?>" class="btn btn-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-car" style="font-size: 60px; color: var(--light-gray); margin-bottom: 20px;"></i>
                            <h3>No cars listed yet</h3>
                            <p>You haven't listed any cars for sale yet.</p>
                            <a href="add_car.php" class="btn btn-primary" style="margin-top: 20px;">
                                <i class="fas fa-plus"></i> Add Your First Car
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="tab-content" id="favorites">
                <h2>Favorite Cars</h2>
                
                <?php if ($favorites_result->num_rows > 0): ?>
                    <div class="cars-grid">
                        <?php while ($car = $favorites_result->fetch_assoc()): ?>
                            <div class="car-card">
                                <?php if ($car['is_sold']): ?>
                                    <div class="sold-badge">SOLD</div>
                                <?php else: ?>
                                    <div class="car-badge">NEW</div>
                                <?php endif; ?>
                                
                                <div class="car-image">
                                    <img src="<?php echo htmlspecialchars($car['image_path']); ?>" alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>">
                                </div>
                                
                                <div class="car-details">
                                    <h3 class="car-title"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h3>
                                    <div class="car-price">₹<?php echo number_format($car['price']); ?></div>
                                    
                                    <div class="car-specs">
                                        <span class="car-spec"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($car['year']); ?></span>
                                        <span class="car-spec"><i class="fas fa-tachometer-alt"></i> <?php echo number_format($car['km_driven']); ?> km</span>
                                        <span class="car-spec"><i class="fas fa-gas-pump"></i> <?php echo htmlspecialchars($car['fuel_type']); ?></span>
                                    </div>
                                    
                                    <div class="car-actions">
                                        <form method="POST" action="toggle_favorite.php" style="display: inline;">
                                            <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fas fa-heart"></i> Remove
                                            </button>
                                        </form>
                                        <a href="view_car.php?id=<?php echo $car['id']; ?>" class="btn btn-outline">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-heart" style="font-size: 60px; color: var(--light-gray); margin-bottom: 20px;"></i>
                        <h3>No favorite cars</h3>
                        <p>You haven't added any cars to your favorites yet.</p>
                        <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-car"></i> Browse Cars
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content" id="messages">
                <h2>Messages</h2>
                
                <?php if ($messages_result->num_rows > 0): ?>
                    <div class="messages-list">
                        <?php while ($message = $messages_result->fetch_assoc()): ?>
                            <div class="message-item">
                                <div class="message-header">
                                    <h4><?php echo htmlspecialchars($message['sender_name']); ?></h4>
                                    <span class="message-time"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></span>
                                </div>
                                
                                <div class="message-car">
                                    <i class="fas fa-car"></i> <?php echo htmlspecialchars($message['brand'] . ' ' . $message['model']); ?>
                                </div>
                                
                                <div class="message-content">
                                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                </div>
                                
                                <div class="message-actions">
                                    <a href="view_car.php?id=<?php echo $message['car_id']; ?>" class="btn btn-outline">
                                        <i class="fas fa-eye"></i> View Car
                                    </a>
                                    <a href="view_message.php?id=<?php echo $message['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-reply"></i> Reply
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-envelope" style="font-size: 60px; color: var(--light-gray); margin-bottom: 20px;"></i>
                        <h3>No messages</h3>
                        <p>You haven't received any messages yet.</p>
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
        function openTab(tabId) {
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
