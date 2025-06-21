<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['car_id']) || 
    ($_SESSION['user_type'] != 'seller' && $_SESSION['user_type'] != 'verified_seller' && $_SESSION['user_type'] != 'admin')) {
    header("Location: login.php");
    exit();
}

$car_id = (int)$_POST['car_id'];

// Verify that the user owns the car (unless admin)
if ($_SESSION['user_type'] != 'admin') {
    $stmt = $conn->prepare("SELECT id FROM cars WHERE id = ? AND seller_id = ?");
    $stmt->bind_param("ii", $car_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $_SESSION['error'] = "You don't have permission to delete this car.";
        header("Location: index.php");
        exit();
    }
}

// Delete the car
$stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
$stmt->bind_param("i", $car_id);

if ($stmt->execute()) {
    $_SESSION['message'] = "Car deleted successfully!";
} else {
    $_SESSION['error'] = "Error deleting car: " . $conn->error;
}

header("Location: index.php");
exit();
?>
