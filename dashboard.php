<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'], $_SESSION['user_name'])) {
    header("Location: index.php");
    exit;
}

$userName = $_SESSION['user_name'];
$isReturning = $_SESSION['is_returning'] ?? false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome</title>
</head>
<body>
<nav>
    <ul>
        <li><a href="index.php">index.php</a></li>
        <li><a href="logout.php">Exit User</a></li>
    </ul>
</nav>

<h1>
    <?= $isReturning
        ? "Welcome back " . htmlspecialchars($userName)
        : "Welcome " . htmlspecialchars($userName)
    ?>
</h1>

<p><a href="driver_recruit.php">Recruit / view your drivers</a></p>
<p><a href="seasons.php">Season Overview</a></p>

</body>
</html>
