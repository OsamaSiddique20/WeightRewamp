<?php
require 'auth.php';
require 'db.php';

$userId = $_SESSION['user_id'];
$goalWeight = isset($_POST['goal_weight']) ? trim($_POST['goal_weight']) : null;

if ($goalWeight === '') {
    $stmt = $pdo->prepare('DELETE FROM user_settings WHERE user_id = ?');
    $stmt->execute([$userId]);
    $_SESSION['success_message'] = 'Goal weight cleared successfully.';
    header('Location: display.php');
    exit;
}

if (!is_numeric($goalWeight) || $goalWeight <= 0) {
    $_SESSION['error_message'] = 'Please enter a valid target weight.';
    header('Location: display.php');
    exit;
}

$stmt = $pdo->prepare('INSERT INTO user_settings (user_id, goal_weight) VALUES (?, ?) ON DUPLICATE KEY UPDATE goal_weight = VALUES(goal_weight)');
$stmt->execute([$userId, $goalWeight]);

$_SESSION['success_message'] = 'Goal weight saved successfully.';
header('Location: display.php');
exit;
