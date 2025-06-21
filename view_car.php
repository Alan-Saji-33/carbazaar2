<?php
session_start();
require_once 'db_config.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$car_id = (int)$_GET['id'];

// Get car details
$stmt = $conn->prepare("SELECT cars.*, users.username AS seller_name, users.phone AS seller_phone, 
                       users.email AS seller_email, users.profile_image AS seller_image, 
                       users.user_type AS seller_type, users.location AS seller_location
                       FROM cars 
                       JOIN users ON cars.seller_id = users.id 
                       WHERE cars.id = ?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

$car = $result->fetch_assoc();

// Get additional car images
$stmt = $conn->prepare("SELECT image_path FROM car_images WHERE car_id = ?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$images_result = $stmt->get_result();
$additional_images = [];

while ($row = $images_result->fetch_assoc()) {
    $additional_images[] = $row['image_path'];
}

// Check if car is in favorites
$is_favorite = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND car_id = ?");
    $stmt->bind_param("ii", $_SESSION['user_id'], $car_id);
    $stmt->execute();
    $is_favorite = $stmt->get_result()->num_rows > 0;
}

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $message = trim($_POST['message']);
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $car['seller_id'];
    
    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (car_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $car_id, $sender_id, $receiver_id, $message);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Message sent successfully!";
            header("Location: view_car.php?id=$car_id");
            exit();
        }
    }
}

// Get messages for this car (if logged in and involved in conversation)
$messages = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $other_user_id = ($user_id == $car['seller_id']) ? null : $car['seller_id'];
    
    if ($other_user_id) {
        $stmt = $conn->prepare("SELECT * FROM messages WHERE car_id = ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) ORDER BY created_at ASC");
        $stmt->bind_param("iiiii", $car_id, $user_id, $other_user_id, $other_user_id, $user_id);
        $stmt->execute();
        $messages_result = $stmt->get_result();
        
        while ($row = $messages_result->fetch_assoc()) {
            $messages[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?> - CarBazaar</title>
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
                <?php if (isset($_SESSION['username'])): ?>
                    <div class="user-greeting">
                        Welcome, <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </div>
                    <a href="profile.php" class="btn btn-outline">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="logout.php" class="btn btn-outline">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
                <?php endif; ?>
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
        
        <div class="car-details-page">
            <div class="car-details-header">
                <h1><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h1>
                <div class="car-price">₹<?php echo number_format($car['price']); ?></div>
            </div>
            
            <div class="car-images">
                <div class="main-image">
                    <img src="<?php echo htmlspecialchars($car['image_path']); ?>" alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>">
                </div>
                
                <?php if (!empty($additional_images)): ?>
                    <div class="thumbnail-images">
                        <?php foreach ($additional_images as $image): ?>
                            <div class="thumbnail">
                                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="car-details-content">
                <div class="car-specs">
                    <h2>Specifications</h2>
                    
                    <div class="specs-grid">
                        <div class="spec-item">
                            <i class="fas fa-calendar-alt"></i>
                            <div class="spec-label">Year</div>
                            <div class="spec-value"><?php echo htmlspecialchars($car['year']); ?></div>
                        </div>
                        
                        <div class="spec-item">
                            <i class="fas fa-tachometer-alt"></i>
                            <div class="spec-label">Kilometers</div>
                            <div class="spec-value"><?php echo number_format($car['km_driven']); ?> km</div>
                        </div>
                        
                        <div class="spec-item">
                            <i class="fas fa-gas-pump"></i>
                            <div class="spec-label">Fuel Type</div>
                            <div class="spec-value"><?php echo htmlspecialchars($car['fuel_type']); ?></div>
                        </div>
                        
                        <div class="spec-item">
                            <i class="fas fa-cog"></i>
                            <div class="spec-label">Transmission</div>
                            <div class="spec-value"><?php echo htmlspecialchars($car['transmission']); ?></div>
                        </div>
                        
                        <div class="spec-item">
                            <i class="fas fa-car"></i>
                            <div class="spec-label">Body Type</div>
                            <div class="spec-value">Sedan</div>
                        </div>
                        
                        <div class="spec-item">
                            <i class="fas fa-paint-brush"></i>
                            <div class="spec-label">Color</div>
                            <div class="spec-value">White</div>
                        </div>
                    </div>
                </div>
                
                <div class="car-description">
                    <h2>Description</h2>
                    <p><?php echo nl2br(htmlspecialchars($car['description'])); ?></p>
                </div>
                
                <div class="seller-info">
                    <h2>Seller Information</h2>
                    
                    <div class="seller-card">
                        <div class="seller-image">
                            <img src="<?php echo !empty($car['seller_image']) ? htmlspecialchars($car['seller_image']) : 'images/default-profile.jpg'; ?>" alt="<?php echo htmlspecialchars($car['seller_name']); ?>">
                        </div>
                        
                        <div class="seller-details">
                            <h3><?php echo htmlspecialchars($car['seller_name']); ?></h3>
                            
                            <?php if ($car['seller_type'] == 'verified_seller'): ?>
                                <span class="badge verified-badge">Verified Seller</span>
                            <?php endif; ?>
                            
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($car['seller_location']); ?></p>
                            
                            <?php if (!empty($car['seller_phone'])): ?>
                                <p><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($car['seller_phone']); ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($car['seller_email'])): ?>
                                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($car['seller_email']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $car['seller_id']): ?>
                        <div class="seller-actions">
                            <form method="POST" action="toggle_favorite.php" style="display: inline;">
                                <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                <button type="submit" class="btn <?php echo $is_favorite ? 'btn-danger' : 'btn-outline'; ?>">
                                    <i class="fas fa-heart"></i> <?php echo $is_favorite ? 'Remove Favorite' : 'Add to Favorites'; ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $car['seller_id']): ?>
                    <div class="message-seller">
                        <h2>Message Seller</h2>
                        
                        <form method="POST" class="message-form">
                            <div class="form-group">
                                <textarea name="message" class="form-control" rows="4" placeholder="Type your message here..." required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" name="send_message" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Send Message
                                </button>
                            </div>
                        </form>
                        
                        <?php if (!empty($messages)): ?>
                            <div class="message-history">
                                <h3>Message History</h3>
                                
                                <?php foreach ($messages as $message): ?>
                                    <div class="message <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received'; ?>">
                                        <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                        <div class="message-time">
                                            <?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $car['seller_id'] || $_SESSION['user_type'] == 'admin')): ?>
                <div class="car-actions">
                    <a href="edit_car.php?id=<?php echo $car['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Car
                    </a>
                    
                    <form method="POST" action="delete_car.php" style="display: inline;">
                        <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this car?');">
                            <i class="fas fa-trash"></i> Delete Car
                        </button>
                    </form>
                    
                    <?php if (!$car['is_sold']): ?>
                        <form method="POST" action="mark_sold.php" style="display: inline;">
                            <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check-circle"></i> Mark as Sold
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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
</body>
</html>
