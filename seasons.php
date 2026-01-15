<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

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

// ---- HANDLE DELETE SEASON----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_season_id'])) {
    $seasonId = (int)$_POST['delete_season_id'];

    $stmt = $conn->prepare(
        "DELETE FROM seasons WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $seasonId, $userId);
    $stmt->execute();
    $stmt->close();
}

// INSERT LOGIC
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seasonName = trim($_POST['season_name'] ?? '');

    if ($seasonName === '') {
        $error = 'Please enter a season name.';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO seasons (user_id, name) VALUES (?, ?)"
        );
        $stmt->bind_param("is", $userId, $seasonName);
        $stmt->execute();
        $stmt->close();
    }
}

// ---- FETCH USER SEASONS ----
$seasons = [];

$stmt = $conn->prepare(
    "SELECT id, name FROM seasons WHERE user_id = ? ORDER BY created_at DESC"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $seasons[] = $row;
}

$stmt->close();

?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Season</title>
</head>
<body>

<nav>
    <a href="dashboard.php">Dashboard</a> |
    <a href="logout.php">Exit User</a>
</nav>

<h1>Create a New Season</h1>

<p>Logged in as <?= htmlspecialchars($userName) ?></p>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post">
    <label>
        Season name:
        <br>
        <input type="text" name="season_name" required>
    </label>
    <br><br>
    <button type="submit">Create Season</button>
</form>

<h2>Your Seasons</h2>
<?php if (empty($seasons)): ?>
    <p>You haven’t created any seasons yet.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Season Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($seasons as $season): ?>
            <tr>
    <td>
        <a href="season_details.php?season_id=<?= $season['id'] ?>">
            <?= htmlspecialchars($season['name']) ?>
        </a>
    </td>
    <td>
        <form method="post" style="display:inline;">
            <input type="hidden"
                   name="delete_season_id"
                   value="<?= $season['id'] ?>">
            <button type="submit"
                    onclick="return confirm('Delete this season?')">
                Delete
            </button>
        </form>
    </td>
</tr>

        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>




</body>
</html>
