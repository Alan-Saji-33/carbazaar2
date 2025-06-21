<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['car_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$car_id = (int)$_POST['car_id'];

// Check if car is already in favorites
$stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND car_id = ?");
$stmt->bind_param("ii", $user_id, $car_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Remove from favorites
    $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND car_id = ?");
    $action = "removed from";
} else {
    // Add to favorites
    $stmt = $conn->prepare("INSERT INTO favorites (user_id, car_id) VALUES (?, ?)");
    $action = "added to";
}

$stmt->bind_param("ii", $user_id, $car_id);

if ($stmt->execute()) {
    $_SESSION['message'] = "Car $action your favorites!";
} else {
    $_SESSION['error'] = "Error updating favorites: " . $conn->error;
}

// Redirect back to previous page
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: $referer");
exit();
?>
