<?php
session_start();
// Database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=user_data', 'osama', 'some_pass');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

// Get user input
$datetime = $_POST['datetime'];
$weight = $_POST['weight'];

// Check if date type and weight are empty
if (empty($datetime) || empty($weight)) {
    // Set error message in session variable
    $_SESSION['error_message'] = 'Please provide both date and weight.';
    $_SESSION['redirect_time'] = time() + 5; 

    // Redirect to the referring page
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Insert data into the database
$userId = $_SESSION['user_id']; // Get user_id from session
$datetime = $_POST['datetime']; // e.g. '2025-06-26'
$stmt = $pdo->prepare('INSERT INTO user_info (user_id, datetime, weight) VALUES (?, ?, ?)');
$stmt->execute([$userId, $datetime, $weight]);

header('Location: index.php');
?>
