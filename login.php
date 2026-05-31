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
        header('Location: login.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE name = ?');
        $stmt->execute([$name]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_id'] = $user['id'];
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['error_message'] = 'User not found. Please register first.';
            header('Location: login.php');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
        header('Location: login.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Weight Tracker</title>
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
            <p class="eyebrow">Welcome back</p>
            <h2>Log in to your progress.</h2>
            <p class="lead">Enter your name to keep your weight log and trend chart close at hand.</p>

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

            <form class="form-stack" method="POST" action="login.php">
                <div class="field">
                    <label for="name">Name</label>
                    <input id="name" type="text" name="name" placeholder="Enter your name" autocomplete="name" required>
                </div>
                <button class="button-primary" type="submit">Login</button>
            </form>
            <p class="auth-switch">New here? <a href="register.php">Create your profile</a></p>
        </section>
    </main>
</body>
</html>
