<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$userId = $_SESSION['user_id'];

if (
    !isset($_POST['season_id'], $_POST['track_id'], $_POST['results']) ||
    !is_array($_POST['results'])
) {
    die("Invalid submission");
}

$seasonId = (int)$_POST['season_id'];
$trackId  = (int)$_POST['track_id'];
$results  = $_POST['results']; // [position => pilot_id]

$conn = new mysqli("localhost", "ujlfg9acjgmgu", "", "dbggshhbizolvg");
if ($conn->connect_error) {
    die("DB connection failed");
}

/* ---- VERIFY SEASON + GET POINTS SYSTEM ---- */
$stmt = $conn->prepare(
    "SELECT points_system_id
     FROM seasons
     WHERE id = ? AND user_id = ?"
);
$stmt->bind_param("ii", $seasonId, $userId);
$stmt->execute();
$stmt->bind_result($pointsSystemId);
$found = $stmt->fetch();
$stmt->close();

if (!$found || !$pointsSystemId) {
    die("Invalid season");
}

/* ---- LOAD POINTS RULES INTO MAP ---- */
$pointsMap = []; // position => points

$stmt = $conn->prepare(
    "SELECT position, points
     FROM points_rules
     WHERE points_system_id = ?"
);
$stmt->bind_param("i", $pointsSystemId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $pointsMap[(int)$row['position']] = (int)$row['points'];
}
$stmt->close();

$delete = $conn->prepare(
    "DELETE FROM race_results
     WHERE season_id = ? AND track_id = ? AND user_id = ?"
);
$delete->bind_param("iii", $seasonId, $trackId, $userId);
$delete->execute();
$delete->close();


/* ---- PREPARE INSERT ---- */
$insert = $conn->prepare(
    "INSERT INTO race_results
     (user_id, season_id, track_id, pilot_id, points_system_id, position, points_awarded)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);


/* ---- INSERT ONE ROW PER DRIVER ---- */
foreach ($results as $position => $pilotId) {

    if ($pilotId === '') {
        continue; // position left blank
    }

    $position = (int)$position;
    $pilotId  = (int)$pilotId;

    if (!isset($pointsMap[$position])) {
        continue; // invalid position
    }

    $points = $pointsMap[$position];

    $insert->bind_param(
        "iiiiiii",
        $userId,
        $seasonId,
        $trackId,
        $pilotId,
        $pointsSystemId,
        $position,
        $points
    );
    
   

    $insert->execute();
}

$insert->close();
$conn->close();

/* ---- DONE ---- */
header("Location: race_results.php?season_id=" . $seasonId);
exit;
