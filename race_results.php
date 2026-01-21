<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['user_id'];

if (!isset($_GET['season_id'])) {
    die("Missing season_id");
}

$seasonId = (int)$_GET['season_id'];

$conn = new mysqli("localhost", "root", "", "sclr_2_0");
if ($conn->connect_error) {
    die("DB connection failed");
}

/* ---- VERIFY SEASON OWNERSHIP + GET POINTS SYSTEM ---- */
$stmt = $conn->prepare(
    "SELECT name, points_system_id
     FROM seasons
     WHERE id = ? AND user_id = ?"
);
$stmt->bind_param("ii", $seasonId, $userId);
$stmt->execute();
$stmt->bind_result($seasonName, $pointsSystemId);
$found = $stmt->fetch();
$stmt->close();

if (!$found || !$pointsSystemId) {
    die("Invalid season or no points system assigned.");
}

/* ---- TRACKS IN SEASON ---- */
$tracks = [];
$stmt = $conn->prepare(
    "SELECT t.id, t.course, t.layout
     FROM season_tracks st
     JOIN tracks t ON t.id = st.track_id
     WHERE st.season_id = ? AND st.user_id = ?
     ORDER BY t.course, t.layout"
);
$stmt->bind_param("ii", $seasonId, $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $tracks[] = $row;
}
$stmt->close();

/* ---- PILOTS IN SEASON ---- */
$pilots = [];
$stmt = $conn->prepare(
    "SELECT p.id, p.name
     FROM season_pilots sp
     JOIN pilots p ON p.id = sp.pilot_id
     WHERE sp.season_id = ? AND sp.user_id = ?
     ORDER BY p.name"
);
$stmt->bind_param("ii", $seasonId, $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pilots[] = $row;
}
$stmt->close();

/* ---- POINTS RULES ---- */
$pointsRules = [];
$stmt = $conn->prepare(
    "SELECT position, points
     FROM points_rules
     WHERE points_system_id = ?
     ORDER BY position ASC"
);
$stmt->bind_param("i", $pointsSystemId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pointsRules[] = $row;
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Enter Race Results</title>
<style>
    body { font-family: Arial, sans-serif; }
    section { border: 1px solid #ccc; padding: 20px; margin-bottom: 30px; }
    select { width: 260px; }
</style>
</head>
<body>

<h1>Enter Race Results</h1>
<h2><?= htmlspecialchars($seasonName) ?></h2>

<form method="post" action="save_results.php">

<!-- ================= TRACK ================= -->
<section>
    <h3>1) Choose the track</h3>

    <?php foreach ($tracks as $track): ?>
        <label>
            <input type="radio" name="track_id" value="<?= $track['id'] ?>" required>
            <?= htmlspecialchars($track['course'] . ' – ' . $track['layout']) ?>
        </label><br>
    <?php endforeach; ?>
</section>

<!-- ================= RESULTS ================= -->
<section>
    <h3>2) Assign positions to drivers</h3>

    <?php foreach ($pointsRules as $rule): ?>
        <div style="margin-bottom:8px;">
            <strong><?= $rule['position'] ?><?= match($rule['position']) {
                1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th'
            } ?></strong>

            <select name="results[<?= $rule['position'] ?>]">
                <option value="">— Select driver —</option>
                <?php foreach ($pilots as $pilot): ?>
                    <option value="<?= $pilot['id'] ?>">
                        <?= htmlspecialchars($pilot['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            (<?= $rule['points'] ?> pts)
        </div>
    <?php endforeach; ?>
</section>

<input type="hidden" name="season_id" value="<?= $seasonId ?>">

<button type="submit">Save Results</button>

</form>

</body>
</html>
