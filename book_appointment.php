<?php
session_start();
include "config.php";

if(!isset($_SESSION['user_id'])){
    header("Location:index.php");
    exit();
}

$user_id=$_SESSION['user_id'];

$doctor=$_POST['doctor'];
$speciality=$_POST['speciality'];
$date=$_POST['date'];
$time=$_POST['time'];
$reason=$_POST['reason'];

$stmt=$conn->prepare("INSERT INTO appointments(user_id,doctor_name,speciality,appointment_date,appointment_time,reason)
VALUES(?,?,?,?,?,?)");

$stmt->bind_param("isssss",
$user_id,
$doctor,
$speciality,
$date,
$time,
$reason);

if($stmt->execute()){
header("Location:index.php?profile=1&booked=1");
}else{
echo $stmt->error;
}