<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'], $_SESSION['user_name'])) {
    header("Location: index.php");
    exit;
}

$userId   = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

/* ---- DB CONFIG ---- */
$conn = new mysqli("localhost", "root", "", "sclr_2_0");
if ($conn->connect_error) {
    die("Database connection failed");
}

$error = "";

/* =========================
   HANDLE DELETE SEASON
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_season_id'])) {
    $seasonId = (int)$_POST['delete_season_id'];

    $stmt = $conn->prepare(
        "DELETE FROM seasons WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $seasonId, $userId);
    $stmt->execute();
    $stmt->close();
}

/* =========================
   HANDLE CREATE SEASON
   ========================= */
   if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['season_name'])) {
    $seasonName = trim($_POST['season_name']);

    if ($seasonName === '') {
        $error = "Please enter a season name.";
    } else {

        // 1️⃣ Check for duplicate season name for this user
        $check = $conn->prepare(
            "SELECT id FROM seasons WHERE user_id = ? AND name = ? LIMIT 1"
        );
        $check->bind_param("is", $userId, $seasonName);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "You already have a season with that name.";
            $check->close();
        } else {
            $check->close();

            // 2️⃣ Safe to insert
            $stmt = $conn->prepare(
                "INSERT INTO seasons (user_id, name) VALUES (?, ?)"
            );
            $stmt->bind_param("is", $userId, $seasonName);
            $stmt->execute();
            $stmt->close();
        }
    }
}

/* =========================
   FETCH USER SEASONS
   ========================= */
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
$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seasons</title>
    <link rel="stylesheet" href="css/seasons.css">
</head>
<body>

<nav>
    <a href="dashboard.php">Dashboard</a> |
    <a href="logout.php">Exit User</a>
</nav>

<main>
    <div class="panel">

    <h1>View Current Season or Create New Season</h1>

<p>Logged in as <?= htmlspecialchars($userName) ?></p>

<?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post">
    <label>
        Season name:<br>
        <input type="text" name="season_name" required>
    </label>
    <br><br>
    <button type="submit">Create Season</button>
</form>

<h2>Your Seasons</h2>

<?php if (empty($seasons)): ?>
    <p>You haven’t created any seasons yet.</p>
<?php else: ?>
    <table class="seasons-table">

        <thead>
            <tr>
                <th>Season Name</th>
                <th>Season Settings</th>
                <th>Season Pilots</th>
                <th>Enter Race Results</th>
                <th>Leaderboard</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($seasons as $season): ?>
            <tr>
                <td><?= htmlspecialchars($season['name']) ?></td>
                <td><a href="season_details.php?season_id=<?= (int)$season['id'] ?>">Settings</a></td>
                <td><a href="season_pilots.php?season_id=<?= (int)$season['id'] ?>">Pilots</a></td>

                <td><a href="race_results.php?season_id=<?= (int)$season['id'] ?>">Enter Results</a></td>
                <td><a href="leaderboard.php?season_id=<?= (int)$season['id'] ?>">View Leaderboard</a></td>


                <td>
                    <form method="post" style="display:inline;"
                          onsubmit="return confirm('Delete this season?');">
                        <input type="hidden"
                               name="delete_season_id"
                               value="<?= (int)$season['id'] ?>">
                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

    </div>
</main>




</body>
</html>
