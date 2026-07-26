<?php

session_start();

$conn=new mysqli("localhost","root","","register");

if($conn->connect_error){

die("Connection Failed");

}

$medical_registration_no=$_POST['medical_registration_no'];

$password=$_POST['password'];

$stmt=$conn->prepare("SELECT * FROM doctors WHERE medical_registration_no=?");

$stmt->bind_param("s",$medical_registration_no);

$stmt->execute();

$result=$stmt->get_result();

if($result->num_rows>0){

$row=$result->fetch_assoc();

if(password_verify($password,$row['password'])){

$_SESSION['doctor_id']=$row['id'];

$_SESSION['doctor_name']=$row['full_name'];

header("Location: doctor_dashboard.php");

}else{

echo "<script>

alert('Wrong Password');

window.location='index.php';

</script>";

}

}else{

echo "<script>

alert('Doctor Not Found');

window.location='index.php';

</script>";

}

?>