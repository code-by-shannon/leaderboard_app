<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

/* ---------- SESSION CHECK ---------- */
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$userId = $_SESSION['user_id'];

/* ---------- VALIDATE SEASON ---------- */
if (!isset($_GET['season_id']) || (int)$_GET['season_id'] <= 0) {
    die("Missing or invalid season_id.");
}
$seasonId = (int)$_GET['season_id'];

/* ---------- DB ---------- */
$conn = new mysqli("localhost", "root", "", "SCLR_2_0");
if ($conn->connect_error) {
    die("DB connection failed");
}

/* ---------- HANDLE POST ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $selectedPilots = $_POST['pilots'] ?? [];

    // 1) Remove existing assignments for this season
    $stmt = $conn->prepare("
        DELETE FROM season_pilots
        WHERE user_id = ? AND season_id = ?
    ");
    $stmt->bind_param("ii", $userId, $seasonId);
    $stmt->execute();
    $stmt->close();

    // 2) Insert selected pilots
    if (!empty($selectedPilots)) {
        $stmt = $conn->prepare("
            INSERT INTO season_pilots (user_id, season_id, pilot_id)
            VALUES (?, ?, ?)
        ");

        foreach ($selectedPilots as $pilotId) {
            $pilotId = (int)$pilotId;
            $stmt->bind_param("iii", $userId, $seasonId, $pilotId);
            $stmt->execute();
        }
        $stmt->close();
    }

    $_SESSION['season_pilots_success'] = true;
    header("Location: season_pilots.php?season_id=$seasonId");
    exit;
    
}

/* ---------- LOAD PILOTS ---------- */
$pilots = [];
$result = $conn->prepare("
    SELECT id, name
    FROM pilots
    WHERE user_id = ?
    ORDER BY name
");
$result->bind_param("i", $userId);
$result->execute();
$res = $result->get_result();
while ($row = $res->fetch_assoc()) {
    $pilots[] = $row;
}
$result->close();

/* ---------- LOAD ASSIGNED PILOTS ---------- */
$assigned = [];
$result = $conn->prepare("
    SELECT pilot_id
    FROM season_pilots
    WHERE user_id = ? AND season_id = ?
");
$result->bind_param("ii", $userId, $seasonId);
$result->execute();
$res = $result->get_result();
while ($row = $res->fetch_assoc()) {
    $assigned[] = $row['pilot_id'];
}
$result->close();

$assignedPilots = [];
$stmt = $conn->prepare("
    SELECT p.id, p.name
    FROM season_pilots sp
    JOIN pilots p ON p.id = sp.pilot_id
    WHERE sp.user_id = ? AND sp.season_id = ?
    ORDER BY p.name
");
$stmt->bind_param("ii", $userId, $seasonId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $assignedPilots[] = $row;
}
$stmt->close();


/* ---------- LOAD SEASON ---------- */
$seasonName = '';

$stmt = $conn->prepare("
    SELECT name
    FROM seasons
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $seasonId, $userId);
$stmt->execute();
$stmt->bind_result($seasonName);
$stmt->fetch();
$stmt->close();

if ($seasonName === '') {
    die("Season not found or access denied.");
}
?>




<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Assign Pilots to Season</title>
</head>
<body>

<h1>Assign Pilots to: <?= htmlspecialchars($seasonName) ?></h1>

<?php if (!empty($_SESSION['season_pilots_success'])): ?>
    <p style="color: green; font-weight: bold;">
        Season drivers updated successfully.
    </p>
    <?php unset($_SESSION['season_pilots_success']); ?>
<?php endif; ?>



<p>
    <a href="seasons.php">← Back to Seasons</a>
</p>

<form method="post">
    <table border="1" cellpadding="6">
        <tr>
            <th>In Season?</th>
            <th>Pilot Name</th>
        </tr>

        <?php foreach ($pilots as $pilot): ?>
            <tr>
                <td style="text-align:center;">
                    <input
                        type="checkbox"
                        name="pilots[]"
                        value="<?= $pilot['id'] ?>"
                        <?= in_array($pilot['id'], $assigned) ? 'checked' : '' ?>
                    >
                </td>
                <td><?= htmlspecialchars($pilot['name']) ?></td>
            </tr>
        <?php endforeach; ?>

        <?php if (empty($pilots)): ?>
            <tr>
                <td colspan="2">No pilots found.</td>
            </tr>
        <?php endif; ?>
    </table>

    <br>
    <button type="submit">Save Season Drivers</button>
</form>

<?php if (!empty($assignedPilots)): ?>
    <h2>Current Season Drivers</h2>

    <ul>
        <?php foreach ($assignedPilots as $pilot): ?>
            <li>
                <?= htmlspecialchars($pilot['name']) ?>
                <form method="post" action="season_pilots_remove.php" style="display:inline;">
                    <input type="hidden" name="season_id" value="<?= $seasonId ?>">
                    <input type="hidden" name="pilot_id" value="<?= $pilot['id'] ?>">
                    <button type="submit">Remove</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>


</body>
</html>
