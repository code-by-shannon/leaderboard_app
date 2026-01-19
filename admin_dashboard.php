<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

/* ---- DB CONFIG ---- */
$conn = new mysqli("localhost", "root", "", "sclr_2_0");
if ($conn->connect_error) {
    die("Database connection failed");
}

/* ============================
   QUERY for rendering of seasons table
   ============================ */
$sqlSeasons = "
SELECT
    season_tracks.id,
    users.name AS user_name,
    seasons.name AS season_name,
    tracks.course,
    tracks.layout,
    season_tracks.created_at
FROM season_tracks
JOIN users
    ON users.id = season_tracks.user_id
JOIN seasons
    ON seasons.id = season_tracks.season_id
JOIN tracks
    ON tracks.id = season_tracks.track_id
ORDER BY season_tracks.id ASC
";
$season_tracks = $conn->query($sqlSeasons);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 40px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
        }

        th {
            background: #eee;
        }

        h2 {
            margin-top: 40px;
        }
    </style>
</head>

<body>

    <h1>Admin Dashboard</h1>

    

    <!-- ============================
     TABLE 2: HUMAN VIEW
     ============================ -->
    <h2>season_tracks </h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Season</th>
                <th>Track</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            
            <?php while ($row = $season_tracks->fetch_assoc()) : ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['user_name']) ?></td>
                    <td><?= htmlspecialchars($row['season_name']) ?></td>
                    <td><?= htmlspecialchars($row['course'] . ' - ' . $row['layout']) ?></td>
                    <td><?= $row['created_at'] ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</body>

</html>

<?php $conn->close(); ?>