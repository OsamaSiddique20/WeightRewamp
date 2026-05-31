<?php
require 'auth.php';
require 'db.php';

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entryId = isset($_POST['entry_id']) ? (int)$_POST['entry_id'] : 0;
    $datetime = trim($_POST['datetime'] ?? '');
    $weight = trim($_POST['weight'] ?? '');

    if ($entryId <= 0 || empty($datetime) || !is_numeric($weight) || $weight <= 0) {
        $_SESSION['error_message'] = 'Please provide a valid date and weight for the entry.';
        header('Location: display.php');
        exit;
    }

    $stmt = $pdo->prepare('UPDATE user_info SET datetime = ?, weight = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([$datetime, $weight, $entryId, $userId]);

    $_SESSION['success_message'] = 'Entry updated successfully.';
    header('Location: display.php');
    exit;
}

$entryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($entryId <= 0) {
    header('Location: display.php');
    exit;
}

$stmt = $pdo->prepare('SELECT id, datetime, weight FROM user_info WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$entryId, $userId]);
$entry = $stmt->fetch();

if (!$entry) {
    header('Location: display.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit weigh-in</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="app-shell center-shell">
        <section class="panel auth-card">
            <div class="card-header">
                <a class="button button-ghost" href="display.php">← Back to dashboard</a>
                <h2>Edit weigh-in</h2>
                <p class="lead">Update the entry date or weight and keep your tracking accurate.</p>
            </div>
            <form class="form-stack" action="edit_entry.php" method="post">
                <input type="hidden" name="entry_id" value="<?php echo htmlspecialchars($entry['id']); ?>">
                <div class="field">
                    <label for="datetime">Date</label>
                    <input type="date" id="datetime" name="datetime" required value="<?php echo htmlspecialchars($entry['datetime']); ?>">
                </div>
                <div class="field">
                    <label for="weight">Weight</label>
                    <input type="number" id="weight" name="weight" step="0.1" min="1" required value="<?php echo htmlspecialchars($entry['weight']); ?>">
                </div>
                <button class="button-primary" type="submit">Save changes</button>
            </form>
        </section>
    </main>
</body>
</html>
