<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "", "sclr_2_0");
if ($conn->connect_error) {
    die("DB connection failed");
}

$sql = "
    SELECT
        id,
        user_id,
        season_id,
        track_id,
        pilot_id,
        position,
        points_awarded,
        created_at
    FROM race_results
    ORDER BY created_at ASC
";

$result = $conn->query($sql);
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Leaderboard (Raw)</title>
<style>
    table { border-collapse: collapse; }
    th, td { border: 1px solid #000; padding: 6px 10px; }
</style>
</head>
<body>

<h1>Race Results (Raw View)</h1>

<table>
    <tr>
        <th>ID</th>
        <th>User</th>
        <th>Season</th>
        <th>Track</th>
        <th>Pilot</th>
        <th>Position</th>
        <th>Points</th>
        <th>Created</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['user_id'] ?></td>
            <td><?= $row['season_id'] ?></td>
            <td><?= $row['track_id'] ?></td>
            <td><?= $row['pilot_id'] ?></td>
            <td><?= $row['position'] ?></td>
            <td><?= $row['points_awarded'] ?></td>
            <td><?= $row['created_at'] ?></td>
        </tr>
    <?php endwhile; ?>

</table>

</body>
</html>

<?php
$conn->close();
