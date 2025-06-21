<?php
session_start();
require_once 'db_config.php';

// Get cars for listing
$search_where = "WHERE is_sold = FALSE";
$search_params = [];
$param_types = "";

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = trim($_GET['search']);
    $min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
    $max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 10000000;
    $fuel_type = isset($_GET['fuel_type']) ? $_GET['fuel_type'] : '';
    $transmission = isset($_GET['transmission']) ? $_GET['transmission'] : '';

    $search_where = "WHERE (model LIKE ? OR brand LIKE ? OR description LIKE ?) AND price BETWEEN ? AND ? AND is_sold = FALSE";
    $search_params = ["%$search%", "%$search%", "%$search%", $min_price, $max_price];
    $param_types = "sssii";

    if (!empty($fuel_type)) {
        $search_where .= " AND fuel_type = ?";
        $search_params[] = $fuel_type;
        $param_types .= "s";
    }

    if (!empty($transmission)) {
        $search_where .= " AND transmission = ?";
        $search_params[] = $transmission;
        $param_types .= "s";
    }
}

$sql_cars_list = "SELECT cars.*, users.username AS seller_name, users.phone AS seller_phone, 
                 users.email AS seller_email, users.profile_image AS seller_image, 
                 users.user_type AS seller_type, users.location AS seller_location
                 FROM cars 
                 JOIN users ON cars.seller_id = users.id 
                 $search_where 
                 ORDER BY created_at DESC 
                 LIMIT 12";

$stmt = $conn->prepare($sql_cars_list);
if ($stmt === false) {
    die("Error: " . htmlspecialchars($conn->error));
}

if (!empty($search_params)) {
    $stmt->bind_param($param_types, ...$search_params);
}

if (!$stmt->execute()) {
    die("Error: " . htmlspecialchars($stmt->error));
}
$cars_result = $stmt->get_result();

// Get favorite cars
$favorites = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT car_id FROM favorites WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $favorites_result = $stmt->get_result();
    
    while ($row = $favorites_result->fetch_assoc()) {
        $favorites[] = $row['car_id'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarBazaar - Used Car Selling Platform</title>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Find Your Perfect Used Car</h1>
            <p>Buy and sell quality used cars from trusted sellers across India</p>
            <div class="hero-buttons">
                <a href="#cars" class="btn btn-primary">
                    <i class="fas fa-car"></i> Browse Cars
                </a>
                <?php if (isset($_SESSION['user_type']) && ($_SESSION['user_type'] == 'seller' || $_SESSION['user_type'] == 'verified_seller' || $_SESSION['user_type'] == 'admin')): ?>
                    <a href="add_car.php" class="btn btn-outline">
                        <i class="fas fa-plus"></i> Add Car
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Search Section -->
    <div class="container">
        <div class="search-section" id="search">
            <div class="search-title">
                <h2>Find Your Dream Car</h2>
                <p>Search through our extensive inventory of quality used cars</p>
            </div>
            
            <form method="GET" class="search-form">
                <div class="form-group">
                    <label for="search"><i class="fas fa-search"></i> Keywords</label>
                    <input type="text" id="search" name="search" class="form-control" placeholder="Toyota, Honda, SUV..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="min_price"><i class="fas fa-rupee-sign"></i> Min Price</label>
                    <input type="number" id="min_price" name="min_price" class="form-control" min="0" placeholder="₹10,000" value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : '0'; ?>">
                </div>
                
                <div class="form-group">
                    <label for="max_price"><i class="fas fa-rupee-sign"></i> Max Price</label>
                    <input type="number" id="max_price" name="max_price" class="form-control" min="0" placeholder="₹50,00,000" value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : '10000000'; ?>">
                </div>
                
                <div class="form-group">
                    <label for="fuel_type"><i class="fas fa-gas-pump"></i> Fuel Type</label>
                    <select id="fuel_type" name="fuel_type" class="form-control">
                        <option value="">Any Fuel Type</option>
                        <option value="Petrol" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'Petrol' ? 'selected' : ''; ?>>Petrol</option>
                        <option value="Diesel" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                        <option value="Electric" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'Electric' ? 'selected' : ''; ?>>Electric</option>
                        <option value="Hybrid" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                        <option value="CNG" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'CNG' ? 'selected' : ''; ?>>CNG</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="transmission"><i class="fas fa-cog"></i> Transmission</label>
                    <select id="transmission" name="transmission" class="form-control">
                        <option value="">Any Transmission</option>
                        <option value="Automatic" <?php echo (isset($_GET['transmission']) && $_GET['transmission'] == 'Automatic' ? 'selected' : ''; ?>>Automatic</option>
                        <option value="Manual" <?php echo (isset($_GET['transmission']) && $_GET['transmission'] == 'Manual' ? 'selected' : ''; ?>>Manual</option>
                    </select>
                </div>
                
                <div class="form-group form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search Cars
                    </button>
                    <a href="index.php" class="btn btn-outline">
                        <i class="fas fa-sync-alt"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Cars Section -->
        <section id="cars">
            <div class="section-header">
                <h2 class="section-title">Available Cars</h2>
                <?php if (isset($_SESSION['user_type']) && ($_SESSION['user_type'] == 'seller' || $_SESSION['user_type'] == 'verified_seller' || $_SESSION['user_type'] == 'admin')): ?>
                    <a href="add_car.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Car
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="cars-grid">
                <?php if ($cars_result->num_rows > 0): ?>
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
                                    <span class="car-spec"><i class="fas fa-cog"></i> <?php echo htmlspecialchars($car['transmission']); ?></span>
                                </div>
                                
                                <div class="seller-info">
                                    <div class="seller-image">
                                        <img src="<?php echo !empty($car['seller_image']) ? htmlspecialchars($car['seller_image']) : 'images/default-profile.jpg'; ?>" alt="<?php echo htmlspecialchars($car['seller_name']); ?>">
                                    </div>
                                    <div class="seller-details">
                                        <h4><?php echo htmlspecialchars($car['seller_name']); ?></h4>
                                        <?php if ($car['seller_type'] == 'verified_seller'): ?>
                                            <span class="badge verified-badge">Verified Seller</span>
                                        <?php endif; ?>
                                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($car['seller_location']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="car-actions">
                                    <form method="POST" action="toggle_favorite.php" style="display: inline;">
                                        <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                        <button type="submit" class="favorite-btn <?php echo in_array($car['id'], $favorites) ? 'active' : ''; ?>">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    </form>
                                    <a href="view_car.php?id=<?php echo $car['id']; ?>" class="btn btn-outline">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                        <i class="fas fa-car" style="font-size: 60px; color: var(--light-gray); margin-bottom: 20px;"></i>
                        <h3 style="color: var(--gray);">No cars found matching your criteria</h3>
                        <p>Try adjusting your search filters or check back later for new listings</p>
                        <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-sync-alt"></i> Reset Search
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-container">
                <div class="footer-column">
                    <h3>CarBazaar</h3>
                    <p>Your trusted platform for buying and selling quality used cars across India.</p>
                    <div class="footer-social">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="index.php#cars">Browse Cars</a></li>
                        <li><a href="index.php#about">About Us</a></li>
                        <li><a href="index.php#contact">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Help & Support</h3>
                    <ul>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms & Conditions</a></li>
                        <li><a href="#">Shipping Policy</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> 123 Street, Mumbai, India</li>
                        <li><i class="fas fa-phone-alt"></i> +91 9876543210</li>
                        <li><i class="fas fa-envelope"></i> info@carbazaar.com</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>© <?php echo date('Y'); ?> CarBazaar. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
