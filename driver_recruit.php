<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

/* does browser currently have logged in user? */
if (!isset($_SESSION['user_id'], $_SESSION['user_name'])) {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

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
$pilotName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pilotName = trim($_POST['pilot_name'] ?? '');

    if ($pilotName === '') {
        $error = 'Please enter a pilot name.';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO pilots (user_id, name) VALUES (?, ?)"
        );
        $stmt->bind_param("is", $userId, $pilotName);
        $stmt->execute();
        $stmt->close();

        $pilotName = ''; // clear after insert
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Pilot</title>
</head>
<body>

<nav>
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="logout.php">Exit User</a></li>
    </ul>
</nav>

<h1>Add a Pilot</h1>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>




<form method="post">
    <label>
        Pilot name:
        <br>
        <input type="text" name="pilot_name">
    </label>
    <br><br>
    <button type="submit">Add Pilot</button>
</form>
<?php $conn->close(); ?>

</body>
</html>
