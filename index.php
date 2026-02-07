<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// DB CONFIG 
if ($_SERVER['HTTP_HOST'] === 'localhost') {
    // LOCAL
    $DB_HOST = "localhost";
    $DB_USER = "root";
    $DB_PASS = "";
    $DB_NAME = "sclr_2_0";
} else {
    // SITEGROUND
    $DB_HOST = "localhost";
    $DB_USER = "ujlfg9acjgmgu";
    $DB_PASS = "YOUR_SITEGROUND_PASSWORD";
    $DB_NAME = "dbggshhbizolvg";
}


/* ---- CONNECT ---- */
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $error = "Please enter a name.";
    } else {
        // Check if user already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->bind_result($userId);
        $found = $stmt->fetch();
        $stmt->close();

        // If user does not exist, create them
        if (!$found) {
            $stmt = $conn->prepare("INSERT INTO users (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $userId = $stmt->insert_id;
            $stmt->close();
        }

        // Start session for this user
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        
        $_SESSION['is_returning'] = $found;

        $conn->close();

        // Go to confirmation page
        header("Location: dashboard.php");
        exit;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>SCLR 2.0 – Choose User</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/index.css">
    
</head>

<body class="page-index">

    <main class="page-center">
        <section class="login-card">

            <h1>Leaderboard App</h1>

            <?php if ($error) : ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="post">
                <label>
                    New or Existing User Name
                    <input type="text" name="name" required>
                </label>

                <button type="submit">Continue</button>
            </form>

        </section>
    </main>

    <!-- Hidden modal for GUEST MODE-->
<div id="guest-warning" class="guest-warning hidden">
    <strong>Guest Mode (Beta)</strong><br>
    Your data is stored in this browser only.<br>
    Clearing cookies, using private browsing, or switching devices will reset your data.<br>
    Account creation is coming later.<br>
    <button id="dismiss-warning">Got it</button>
</div>

<script src="js/guest-warning.js"></script>
</body>


</html>