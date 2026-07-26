<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {

    $appointment_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE appointments SET status='Cancelled' WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $appointment_id, $user_id);

    if ($stmt->execute()) {
        header("Location: index.php?profile=1");
        exit();
    } else {
        echo "Error updating status.";
    }
}
?>