<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

/* ---- DB CONFIG ---- */
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "sclr_2_0";

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
</head>

<body>

    <h1>Welcome to my fancy ass Leaderboard App</h1>


    <p>Already have a username? Type it in and go</p>
    <p>Are you a new to the app? Please create a usern</p>
    <?php if ($error) : ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <label>
            username
            <br>
            <input type="text" name="name" required>
        </label>
        <br><br>
        <button type="submit">Continue</button>
    </form>

</body>

</html>