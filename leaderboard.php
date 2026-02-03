<?php

// error logging
ini_set('display_errors', 1);
error_reporting(E_ALL);


//session and input checks
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['season_id'])) {
    die("Missing season_id");
}

$userId   = $_SESSION['user_id'];
$seasonId = (int)$_GET['season_id'];



// db connection
$conn = new mysqli("localhost", "root", "", "sclr_2_0");
if ($conn->connect_error) {
    die("DB connection failed");
}


// ownership checks
$stmt = $conn->prepare(
    "SELECT name FROM seasons WHERE id = ? AND user_id = ?"
);
$stmt->bind_param("ii", $seasonId, $userId);
$stmt->execute();
$stmt->bind_result($seasonName);
$found = $stmt->fetch();
$stmt->close();

if (!$found) {
    $conn->close();
    header("Location: seasons.php");
    exit;
}


//fetch tracks in season
$tracks = [];

$stmt = $conn->prepare(
    "SELECT t.id, t.course, t.layout
     FROM season_tracks st
     JOIN tracks t ON t.id = st.track_id
     WHERE st.season_id = ? AND st.user_id = ?
     ORDER BY st.id ASC"
);
$stmt->bind_param("ii", $seasonId, $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $tracks[$row['id']] = $row['course'] . ' – ' . $row['layout'];
}
$stmt->close();

// fetch pilots in season
$pilots = [];

$stmt = $conn->prepare(
    "SELECT p.id, p.name
     FROM season_pilots sp
     JOIN pilots p ON p.id = sp.pilot_id
     WHERE sp.season_id = ? AND sp.user_id = ?
     ORDER BY p.name ASC"
);
$stmt->bind_param("ii", $seasonId, $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $pilots[$row['id']] = [
        'name'   => $row['name'],
        'points' => [],
        'total'  => 0
    ];
}
$stmt->close();


// fetch race results and populate
$stmt = $conn->prepare(
    "SELECT pilot_id, track_id, points_awarded
     FROM race_results
     WHERE season_id = ? AND user_id = ?"
);
$stmt->bind_param("ii", $seasonId, $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $pilotId = $row['pilot_id'];
    $trackId = $row['track_id'];
    $points  = $row['points_awarded'];

    if (isset($pilots[$pilotId])) {
        $pilots[$pilotId]['points'][$trackId] = $points;
        $pilots[$pilotId]['total'] += $points;
    }
}
$stmt->close();


// sort pilots by points (Descending)
uasort($pilots, function ($a, $b) {
    return $b['total'] <=> $a['total'];
});

// fetch user name for footer display
$stmt = $conn->prepare(
    "SELECT name FROM users WHERE id = ?"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($displayUserName);
$stmt->fetch();
$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leaderboard</title>
    <link rel="stylesheet" href="css/leaderboard.css">
</head>

<body>

<nav>
    <a href="dashboard.php">Dashboard</a> |
    <a href="seasons.php">Seasons</a> |
    <a href="logout.php">Exit User</a>
</nav>

<main>
  <div class="panel">

    <h1>Leaderboard</h1>
    <h2><?= htmlspecialchars($seasonName) ?></h2>

    <div class="table-wrap">
        <table class="leaderboard">
            <thead>
                <tr>
                    <th>Pilot</th>

                    <?php foreach ($tracks as $trackName): ?>
                        <th><?= htmlspecialchars($trackName) ?></th>
                    <?php endforeach; ?>

                    <th>Total</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($pilots as $pilot): ?>
                    <tr>
                        <td class="pilot-name"><?= htmlspecialchars($pilot['name']) ?></td>

                        <?php foreach ($tracks as $trackId => $trackName): ?>
                            <td>
                                <?= $pilot['points'][$trackId] ?? '—' ?>
                            </td>
                        <?php endforeach; ?>

                        <td class="total-points"><?= $pilot['total'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p class="user-line">
        Logged in as <?= htmlspecialchars($displayUserName) ?>
    </p>

  </div>
</main>

</body>

</html>

<?php
$conn->close();
