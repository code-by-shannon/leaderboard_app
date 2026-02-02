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
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

<nav class="top-nav">
    <ul>
        <li>
            <a href="logout.php">Exit User</a>
            <span class="logout-note">(This will log you out)</span>
        </li>
    </ul>
</nav>

<main class="page-center">
    <section class="dashboard-card">

        <h1>
            <?= $isReturning
                ? "Welcome back " . htmlspecialchars($userName)
                : "Welcome " . htmlspecialchars($userName)
            ?>
        </h1>

        <div class="dashboard-link">
            <a href="driver_recruit.php">Add and manage drivers</a>
            
        </div>

        <div class="dashboard-link">
            <a href="seasons.php">Season Overview</a>
           
        </div>

    </section>
</main>

</body>
</html>
