<?php
require 'auth.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: display.php');
    exit;
}

$userId = $_SESSION['user_id'];
$entryId = isset($_POST['entry_id']) ? (int)$_POST['entry_id'] : 0;

if ($entryId <= 0) {
    $_SESSION['error_message'] = 'Invalid entry selected for deletion.';
    header('Location: display.php');
    exit;
}

$stmt = $pdo->prepare('DELETE FROM user_info WHERE id = ? AND user_id = ?');
$stmt->execute([$entryId, $userId]);

$_SESSION['success_message'] = 'Entry removed successfully.';
header('Location: display.php');
exit;
