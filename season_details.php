
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

/* ---- HANDLE ADD PILOT ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pilot_id'])) {
    $pilotId = (int)$_POST['pilot_id'];

    if ($pilotId > 0) {
        $stmt = $conn->prepare(
            "INSERT INTO season_pilots (user_id, season_id, pilot_id)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iii", $userId, $seasonId, $pilotId);
        $stmt->execute();
        $stmt->close();
    }

    // Prevent resubmit on refresh
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

/* ---- FETCH USER PILOTS FOR DROPDOWN ---- */
$allPilots = [];

$stmt = $conn->prepare(
    "SELECT id, name
     FROM pilots
     WHERE user_id = ?
     ORDER BY name ASC"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $allPilots[] = $row;
}

$stmt->close();


/* ---- FETCH TRACKS ALREADY IN THIS SEASON ---- */
$seasonTracks = [];

$stmt = $conn->prepare(
    "SELECT t.course, t.layout
     FROM season_tracks st
     JOIN tracks t ON t.id = st.track_id
     WHERE st.user_id = ? AND st.season_id = ?
     ORDER BY st.id ASC"
);
$stmt->bind_param("ii", $userId, $seasonId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $seasonTracks[] = $row;
}

$stmt->close();

/* ---- FETCH PILOTS IN THIS SEASON ---- */
$seasonPilots = [];

$stmt = $conn->prepare(
    "SELECT p.name
     FROM season_pilots sp
     JOIN pilots p ON p.id = sp.pilot_id
     WHERE sp.user_id = ? AND sp.season_id = ?
     ORDER BY sp.id ASC"
);
$stmt->bind_param("ii", $userId, $seasonId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $seasonPilots[] = $row;
}

$stmt->close();


/* ---- FETCH POINTS SYSTEMS + RULES ---- */
$pointsSystems = [];

$sql = "
    SELECT 
        ps.id AS system_id,
        ps.name AS system_name,
        pr.position,
        pr.points
    FROM points_systems ps
    JOIN points_rules pr
        ON pr.points_system_id = ps.id
    ORDER BY ps.id, pr.position ASC
";

$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $sid = $row['system_id'];

    if (!isset($pointsSystems[$sid])) {
        $pointsSystems[$sid] = [
            'name'  => $row['system_name'],
            'rules' => []
        ];
    }

    $pointsSystems[$sid]['rules'][] = [
        'position' => $row['position'],
        'points'   => $row['points']
    ];
}


$currentPointsSystemId = null;

$stmt = $conn->prepare(
    "SELECT points_system_id
     FROM seasons
     WHERE id = ? AND user_id = ?"
);
$stmt->bind_param("ii", $seasonId, $userId);
$stmt->execute();
$stmt->bind_result($currentPointsSystemId);
$stmt->fetch();
$stmt->close();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['points_system_id'])) {
    $pointsSystemId = (int)$_POST['points_system_id'];

    $stmt = $conn->prepare(
        "UPDATE seasons
         SET points_system_id = ?
         WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("iii", $pointsSystemId, $seasonId, $userId);
    $stmt->execute();
    $stmt->close();

    header("Location: season_details.php?season_id=" . $seasonId);
    exit;
}



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

<?php if (!empty($seasonTracks)): ?>
    <h2>Tracks in This Season</h2>
    <ul>
        <?php foreach ($seasonTracks as $track): ?>
            <li>
                <?= htmlspecialchars($track['course'] . ' – ' . $track['layout']) ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<hr>

<h2>Add Pilot to Season</h2>

<form method="post">
    <select name="pilot_id" required>
        <option value="">-- Select a pilot --</option>
        <?php foreach ($allPilots as $pilot): ?>
            <option value="<?= $pilot['id'] ?>">
                <?= htmlspecialchars($pilot['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Add Pilot</button>
</form>

<?php if (!empty($seasonPilots)): ?>
    <h2>Pilots in This Season</h2>
    <ul>
        <?php foreach ($seasonPilots as $pilot): ?>
            <li><?= htmlspecialchars($pilot['name']) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<hr>

<h2>Select Points System</h2>

<?php if ($currentPointsSystemId): ?>

    <p>
        <strong>
            You have chosen the
            <?= htmlspecialchars($pointsSystems[$currentPointsSystemId]['name']) ?>
            points system for this season.
        </strong>
    </p>

    <table border="1" cellpadding="6" style="max-width:400px;">
        <thead>
            <tr>
                <th>Position</th>
                <th>Points</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pointsSystems[$currentPointsSystemId]['rules'] as $rule): ?>
                <tr>
                    <td><?= $rule['position'] ?></td>
                    <td><?= $rule['points'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php else: ?>

    <form method="post">
        <?php foreach ($pointsSystems as $systemId => $system): ?>
            <div style="margin-bottom:20px; border:1px solid #ccc; padding:10px;">
                <label>
                    <input type="radio"
                           name="points_system_id"
                           value="<?= $systemId ?>"
                           required>
                    <strong><?= htmlspecialchars($system['name']) ?></strong>
                </label>

                <table border="1" cellpadding="6" style="margin-top:10px;">
                    <thead>
                        <tr>
                            <th>Position</th>
                            <th>Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($system['rules'] as $rule): ?>
                            <tr>
                                <td><?= $rule['position'] ?></td>
                                <td><?= $rule['points'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>

        <button type="submit">Confirm Points System</button>
    </form>

<?php endif; ?>




<p>
    This is the season details page.<br>
    Driver assignment, tracks, and results will live here.
</p>

</body>
</html>
