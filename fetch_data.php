<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=user_data', 'osama', 'some_pass');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

// Fetch data for the logged-in user
$userId = $_SESSION['user_id']; // Get user_id from session
$stmt = $pdo->prepare('SELECT datetime, weight FROM user_info WHERE user_id = ? ORDER BY datetime ASC');
$stmt->execute([$userId]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($data);
?>
