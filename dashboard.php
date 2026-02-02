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
        <li><a href="logout.php">Exit User</a> (This will log you out)</li>
    </ul>
</nav>

<h1>
    <?= $isReturning
        ? "Welcome back " . htmlspecialchars($userName)
        : "Welcome " . htmlspecialchars($userName)
    ?>
</h1>

<p>
  <a href="driver_recruit.php">Add and manage drivers</a><br>
  <small>
    Start here by creating a list of drivers. You’ll assign drivers to specific seasons in the Season Overview.
  </small>
</p>

<p>
  <a href="seasons.php">Season Overview</a><br>
  <small>
    Create and manage seasons. Assign drivers to each season and enter race results.
  </small>
</p>


</body>
</html>
