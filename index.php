<?php
require 'auth.php';
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Weight Tracker</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <?php
        // Check if the user is logged in
        if (isset($_SESSION['user_name'])) {
            $userName = htmlspecialchars($_SESSION['user_name']);
        } else {
            // Redirect to login if user is not logged in
            header('Location: login.php');
            exit;
        }
    ?>

    <main class="app-shell">
        <nav class="nav">
            <a class="brand" href="index.php">
                <span class="brand-mark">W</span>
                <span>Weight Tracker</span>
            </a>
            <div class="nav-actions">
                <a class="button button-ghost" href="display.php">View results</a>
            </div>
        </nav>

        <section class="hero-grid">
            <div class="hero-copy">
                <p class="eyebrow">Daily check-in</p>
                <h1>Hi <?php echo $userName; ?>, log today with less friction.</h1>
                <p class="lead">Capture each weigh-in, then let the graph reveal your trend over time. Clean data in, calmer decisions out.</p>
            </div>

            <div class="panel entry-card">
                <div class="card-header">
                    <h2>New weigh-in</h2>
                    <p>Add the date and your current weight in kilograms.</p>
                </div>

                <?php
                    // Check if error message is set in session
                    if (isset($_SESSION['error_message']) && isset($_SESSION['redirect_time']) && time() < $_SESSION['redirect_time']) {
                        echo '<p class="message message-error">' . htmlspecialchars($_SESSION['error_message']) . '</p>';
                        unset($_SESSION['error_message']); // Remove the error message from session
                    }
                ?>

                <form class="form-stack" action="insert.php" method="post">
                    <div class="field">
                        <label for="datetime">Date</label>
                        <input type="date" id="datetime" name="datetime" required>
                    </div>
                    <div class="field">
                        <label for="weight">Weight</label>
                        <input type="number" id="weight" name="weight" step="0.1" min="1" placeholder="72.5" required>
                    </div>
                    <button class="button-primary" type="submit">Save entry</button>
                    <a class="button button-secondary" href="display.php">Check results</a>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
