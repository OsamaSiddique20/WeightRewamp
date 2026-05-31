<?php
require 'auth.php';
require 'db.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT id, datetime, weight FROM user_info WHERE user_id = ? ORDER BY datetime ASC');
$stmt->execute([$userId]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
?>
