<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get favorite cars
$stmt = $conn->prepare("SELECT cars.* FROM favorites 
                       JOIN cars ON favorites.car_id = cars.id 
                       WHERE favorites.user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$favorites_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites - CarBazaar</title>
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
        <div class="page-header">
            <h1>My Favorite Cars</h1>
            <p>Your saved cars for easy access</p>
        </div>
        
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
