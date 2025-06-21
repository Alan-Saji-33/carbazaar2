<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'seller' && $_SESSION['user_type'] != 'verified_seller' && $_SESSION['user_type'] != 'admin')) {
    header("Location: login.php");
    exit();
}

// Check if seller has reached their car limit (3 cars for unverified sellers)
if ($_SESSION['user_type'] == 'seller') {
    $stmt = $conn->prepare("SELECT COUNT(*) as car_count FROM cars WHERE seller_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $car_count = $result->fetch_assoc()['car_count'];
    
    if ($car_count >= 3) {
        $_SESSION['error'] = "You have reached your car listing limit. Please verify your account to list more cars.";
        header("Location: profile.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $brand = trim($_POST['brand']);
    $model = trim($_POST['model']);
    $year = (int)$_POST['year'];
    $price = (float)$_POST['price'];
    $km_driven = (int)$_POST['km_driven'];
    $fuel_type = $_POST['fuel_type'];
    $transmission = $_POST['transmission'];
    $description = trim($_POST['description']);
    $seller_id = $_SESSION['user_id'];
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "Uploads/cars/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_filename = uniqid() . '.' . $file_ext;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = $target_file;
            } else {
                $error = "Error uploading image file.";
            }
        } else {
            $error = "Invalid image format. Only JPG, JPEG, PNG, and GIF are allowed.";
        }
    } else {
        $error = "Please upload an image of the car.";
    }
    
    if (empty($error)) {
        $stmt = $conn->prepare("INSERT INTO cars (seller_id, brand, model, year, price, km_driven, fuel_type, transmission, image_path, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issidsssss", $seller_id, $brand, $model, $year, $price, $km_driven, $fuel_type, $transmission, $image_path, $description);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Car added successfully!";
            header("Location: index.php");
            exit();
        } else {
            $error = "Error adding car: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Car - CarBazaar</title>
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
            <h1>Add New Car</h1>
            <p>Fill in the details of your car below</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="form">
            <div class="form-row">
                <div class="form-group">
                    <label for="brand">Brand</label>
                    <input type="text" id="brand" name="brand" class="form-control" placeholder="e.g. Toyota" required>
                </div>
                
                <div class="form-group">
                    <label for="model">Model</label>
                    <input type="text" id="model" name="model" class="form-control" placeholder="e.g. Corolla" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="year">Year</label>
                    <input type="number" id="year" name="year" class="form-control" min="1900" max="<?php echo date('Y'); ?>" placeholder="e.g. 2020" required>
                </div>
                
                <div class="form-group">
                    <label for="price">Price (₹)</label>
                    <input type="number" id="price" name="price" class="form-control" min="0" step="1" placeholder="e.g. 500000" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="km_driven">Kilometers Driven</label>
                    <input type="number" id="km_driven" name="km_driven" class="form-control" min="0" placeholder="e.g. 25000" required>
                </div>
                
                <div class="form-group">
                    <label for="fuel_type">Fuel Type</label>
                    <select id="fuel_type" name="fuel_type" class="form-control" required>
                        <option value="">Select Fuel Type</option>
                        <option value="Petrol">Petrol</option>
                        <option value="Diesel">Diesel</option>
                        <option value="Electric">Electric</option>
                        <option value="Hybrid">Hybrid</option>
                        <option value="CNG">CNG</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="transmission">Transmission</label>
                    <select id="transmission" name="transmission" class="form-control" required>
                        <option value="">Select Transmission</option>
                        <option value="Automatic">Automatic</option>
                        <option value="Manual">Manual</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="image">Car Image</label>
                    <input type="file" id="image" name="image" class="form-control" accept="image/*" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="5" placeholder="Add details about the car's condition, features, etc." required></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Car
                </button>
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
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
