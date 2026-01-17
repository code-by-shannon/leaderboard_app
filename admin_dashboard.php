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
   QUERY 1: RAW season_tracks
   ============================ */
$sqlRaw = "
SELECT
    id,
    user_id,
    season_id,
    track_id,
    created_at
FROM season_tracks
ORDER BY id ASC
";
$resultRaw = $conn->query($sqlRaw);

/* ============================
   QUERY 2: READABLE season_tracks
   ============================ */
$sqlReadable = "
SELECT
    st.id,
    u.name AS user_name,
    s.name AS season_name,
    t.course,
    t.layout,
    st.created_at
FROM season_tracks st
JOIN users   u ON u.id = st.user_id
JOIN seasons s ON s.id = st.season_id
JOIN tracks  t ON t.id = st.track_id
ORDER BY st.id ASC
";
$resultReadable = $conn->query($sqlReadable);
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
th, td {
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
     TABLE 1: RAW VIEW
     ============================ -->
<h2>season_tracks (RAW / MACHINE VIEW)</h2>

<table>
<thead>
<tr>
    <th>ID</th>
    <th>User ID</th>
    <th>Season ID</th>
    <th>Track ID</th>
    <th>Created</th>
</tr>
</thead>
<tbody>
<?php while ($row = $resultRaw->fetch_assoc()): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= $row['user_id'] ?></td>
    <td><?= $row['season_id'] ?></td>
    <td><?= $row['track_id'] ?></td>
    <td><?= $row['created_at'] ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<!-- ============================
     TABLE 2: HUMAN VIEW
     ============================ -->
<h2>season_tracks (HUMAN-READABLE VIEW)</h2>

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
<?php while ($row = $resultReadable->fetch_assoc()): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['user_name']) ?></td>
    <td><?= htmlspecialchars($row['season_name']) ?></td>
    <td><?= htmlspecialchars($row['course'] . ' – ' . $row['layout']) ?></td>
    <td><?= $row['created_at'] ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</body>
</html>

<?php $conn->close(); ?>
