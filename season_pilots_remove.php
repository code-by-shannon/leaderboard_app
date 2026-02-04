<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    exit;
}

$userId   = $_SESSION['user_id'];
$seasonId = (int)($_POST['season_id'] ?? 0);
$pilotId  = (int)($_POST['pilot_id'] ?? 0);

if ($seasonId <= 0 || $pilotId <= 0) {
    exit;
}

$conn = new mysqli("localhost", "ujlfg9acjgmgu", "", "dbggshhbizolvg");

$stmt = $conn->prepare("
    DELETE FROM season_pilots
    WHERE user_id = ? AND season_id = ? AND pilot_id = ?
");
$stmt->bind_param("iii", $userId, $seasonId, $pilotId);
$stmt->execute();
$stmt->close();

header("Location: season_pilots.php?season_id=$seasonId");
exit;
