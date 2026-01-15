<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

/* ---- SESSION CHECK ---- */
if (!isset($_SESSION['user_id'], $_SESSION['user_name'])) {
    header("Location: index.php");
    exit;
}

$userId   = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

/* ---- VALIDATE season_id ---- */
if (!isset($_GET['season_id'])) {
    header("Location: seasons.php");
    exit;
}

$seasonId = (int)$_GET['season_id'];
if ($seasonId <= 0) {
    header("Location: seasons.php");
    exit;
}

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

/* ---- FETCH SEASON (OWNERSHIP CHECK INCLUDED) ---- */
$stmt = $conn->prepare(
    "SELECT name FROM seasons WHERE id = ? AND user_id = ?"
);
$stmt->bind_param("ii", $seasonId, $userId);
$stmt->execute();
$stmt->bind_result($seasonName);
$found = $stmt->fetch();
$stmt->close();

if (!$found) {
    // season does not exist OR does not belong to user
    $conn->close();
    header("Location: seasons.php");
    exit;
}

/* ---- HANDLE ADD TRACK ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track_id'])) {
    $trackId = (int)$_POST['track_id'];

    if ($trackId > 0) {
        $stmt = $conn->prepare(
            "INSERT INTO season_tracks (user_id, season_id, track_id)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iii", $userId, $seasonId, $trackId);
        $stmt->execute();
        $stmt->close();
    }

    // Prevent form re-submit on refresh
    header("Location: season_details.php?season_id=" . $seasonId);
    exit;
}


/* ---- FETCH ALL TRACKS FOR DROPDOWN ---- */
$allTracks = [];

$stmt = $conn->prepare(
    "SELECT id, course, layout
     FROM tracks
     ORDER BY course ASC, layout ASC"
);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $allTracks[] = $row;
}

$stmt->close();


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Season Details</title>
</head>
<body>

<nav>
    <a href="/SCLR_2_0/dashboard.php">Dashboard</a> |
    <a href="/SCLR_2_0/seasons.php">All Seasons</a> |
    <a href="/SCLR_2_0/logout.php">Exit User</a>
</nav>

<h1>Season: <?= htmlspecialchars($seasonName) ?></h1>

<p>Logged in as <?= htmlspecialchars($userName) ?></p>

<hr>

<h2>Add Track to Season</h2>

<form method="post">
    <select name="track_id" required>
        <option value="">-- Select a track --</option>
        <?php foreach ($allTracks as $track): ?>
            <option value="<?= $track['id'] ?>">
                <?= htmlspecialchars($track['course'] . " – " . $track['layout']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Add Track</button>
</form>


<p>
    This is the season details page.<br>
    Driver assignment, tracks, and results will live here.
</p>

</body>
</html>
