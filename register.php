<?php
session_start();

// If the user is logged in, redirect to index.php
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=user_data', 'osama', 'some_pass');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']); // Get the name from the form

    if (empty($name)) {
        $_SESSION['error_message'] = 'Please enter a name.';
        header('Location: register.php');
        exit;
    }

    try {
        // Check if the user already exists
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE name = ?');
        $stmt->execute([$name]);
        $userExists = $stmt->fetchColumn();

        if ($userExists) {
            $_SESSION['error_message'] = "User '$name' is already registered.";
            header('Location: register.php');
            exit;
        }

        // Insert the new user into the users table
        $stmt = $pdo->prepare('INSERT INTO users (name) VALUES (?)');
        $stmt->execute([$name]);

        $_SESSION['success_message'] = "User '$name' has been registered successfully!";
        header('Location: login.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
        header('Location: register.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Weight Tracker</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="app-shell center-shell">
        <section class="panel auth-card">
            <div class="card-header">
                <div class="brand">
                    <span class="brand-mark">W</span>
                    <span>Weight Tracker</span>
                </div>
            </div>
            <p class="eyebrow">Start tracking</p>
            <h2>Create your profile.</h2>
            <p class="lead">A simple profile is all you need to start logging weigh-ins and seeing your trend.</p>

            <?php
            if (isset($_SESSION['error_message'])) {
                echo '<p class="message message-error">' . htmlspecialchars($_SESSION['error_message']) . '</p>';
                unset($_SESSION['error_message']);
            }
            if (isset($_SESSION['success_message'])) {
                echo '<p class="message message-success">' . htmlspecialchars($_SESSION['success_message']) . '</p>';
                unset($_SESSION['success_message']);
            }
            ?>

            <form class="form-stack" method="POST" action="register.php">
                <div class="field">
                    <label for="name">Name</label>
                    <input id="name" type="text" name="name" placeholder="Enter your name" autocomplete="name" required>
                </div>
                <button class="button-primary" type="submit">Register</button>
            </form>
            <p class="auth-switch">Already registered? <a href="login.php">Back to login</a></p>
        </section>
    </main>
</body>
</html>
