<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'], $_SESSION['user_name'])) {
    header("Location: index.php");
    exit;
}

$userName = $_SESSION['user_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome</title>
</head>
<body>
<nav><ul>
    <li><a href="index.php">index.php</a></li>
</ul></nav>
<h1>Welcome <?= htmlspecialchars($userName) ?></h1>

</body>
</html>
