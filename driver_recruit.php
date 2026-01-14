<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

/* does browser currently have logged in user? */
if (!isset($_SESSION['user_id'], $_SESSION['user_name'])) {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

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

$error = '';
$pilotName = '';

// ---- HANDLE DELETE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['delete_pilot_id'])) 
{
    $pilotId = (int)$_POST['delete_pilot_id'];

    $stmt = $conn->prepare(
        "DELETE FROM pilots WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $pilotId, $userId);
    $stmt->execute();
    $stmt->close();
}

// ---- HANDLE INSERT ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pilotName = trim($_POST['pilot_name'] ?? '');

    if ($pilotName === '') {
        $error = 'Please enter a pilot name.';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO pilots (user_id, name) VALUES (?, ?)"
        );
        $stmt->bind_param("is", $userId, $pilotName);
        $stmt->execute();
        $stmt->close();

        $pilotName = ''; // clear after insert
    }
}

// ---- FETCH USER PILOTS ----
$pilots = [];

$stmt = $conn->prepare(
    "SELECT id, name FROM pilots WHERE user_id = ? ORDER BY id ASC"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $pilots[] = $row;
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Pilot</title>
</head>
<body>

<nav>
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="logout.php">Exit User</a></li>
    </ul>
</nav>

<h1>Add a Pilot</h1>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>




<form method="post">
    <label>
        Pilot name:
        <br>
        <input type="text" name="pilot_name">
    </label>
    <br><br>
    <button type="submit">Add Pilot</button>
</form>
<?php $conn->close(); ?>

<?php if (!empty($pilots)): ?>
    <h2>Your Pilots</h2>

    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Pilot Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($pilots as $pilot): ?>
            <tr>
                <td><?= htmlspecialchars($pilot['name']) ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="delete_pilot_id"
                               value="<?= $pilot['id'] ?>">
                        <button type="submit"
                                onclick="return confirm('Delete this pilot?')">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>


</body>
</html>
