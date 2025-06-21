<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "car_rental_db";

// Initialize session with secure settings
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

// Database connection with error handling
try {
    // First connect without database to create it if needed
    $temp_conn = new mysqli($db_host, $db_user, $db_pass);
    if ($temp_conn->connect_error) {
        throw new Exception("Initial connection failed: " . $temp_conn->connect_error);
    }

    // Create database if not exists
    $create_db = "CREATE DATABASE IF NOT EXISTS $db_name";
    if (!$temp_conn->query($create_db)) {
        throw new Exception("Error creating database: " . $temp_conn->error);
    }
    $temp_conn->close();

    // Connect with database selected
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die($e->getMessage());
}

// Create tables if they don't exist
$tables = [
    "users" => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(15) NULL,
        user_type ENUM('admin', 'buyer', 'seller', 'verified_seller') NOT NULL DEFAULT 'buyer',
        profile_image VARCHAR(255) DEFAULT 'images/default-profile.jpg',
        location VARCHAR(100) NULL,
        aadhar_path VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "cars" => "CREATE TABLE IF NOT EXISTS cars (
        id INT AUTO_INCREMENT PRIMARY KEY,
        seller_id INT NOT NULL,
        model VARCHAR(100) NOT NULL,
        brand VARCHAR(50) NOT NULL,
        year INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        km_driven INT NOT NULL,
        fuel_type ENUM('Petrol', 'Diesel', 'Electric', 'Hybrid', 'CNG') NOT NULL,
        transmission ENUM('Automatic', 'Manual') NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        description TEXT,
        is_sold BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    "car_images" => "CREATE TABLE IF NOT EXISTS car_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        car_id INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
    )",

    "favorites" => "CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        car_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
        UNIQUE KEY unique_favorite (user_id, car_id)
    )",

    "messages" => "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        car_id INT NOT NULL,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    )"
];

foreach ($tables as $table_name => $sql) {
    if (!$conn->query($sql)) {
        die("Error creating table $table_name: " . $conn->error);
    }
}

// Create admin user if not exists
$admin_check = $conn->query("SELECT * FROM users WHERE username = 'admin' AND user_type = 'admin'");
if ($admin_check->num_rows == 0) {
    $admin_password = password_hash('admin', PASSWORD_DEFAULT);
    $admin_email = 'admin@carbazaar.com';
    $admin_sql = "INSERT INTO users (username, password, email, user_type) VALUES ('admin', '$admin_password', '$admin_email', 'admin')";
    if (!$conn->query($admin_sql)) {
        die("Error creating admin user: " . $conn->error);
    }
}
?>
