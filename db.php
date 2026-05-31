<?php
// Database connection helper and optional settings table initialization.
try {
    $pdo = new PDO('mysql:host=localhost;dbname=user_data', 'osama', 'some_pass');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_settings'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec(
            "CREATE TABLE user_settings (
                user_id INT(11) NOT NULL PRIMARY KEY,
                goal_weight DECIMAL(5,2) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
} catch (PDOException $e) {
    // If the settings table cannot be created, continue without breaking the app.
}
