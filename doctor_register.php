<?php

$conn = new mysqli("localhost","root","","register");

if($conn->connect_error){
    die("Connection Failed : ".$conn->connect_error);
}

$conn->query("ALTER TABLE doctors ADD COLUMN IF NOT EXISTS availability VARCHAR(255) NULL");

$full_name = $_POST['full_name'];
$age = $_POST['age'];
$gender = $_POST['gender'];
$specialization = $_POST['specialization'];
$medical_registration_no = $_POST['medical_registration_no'];
$experience = $_POST['experience'];
$hospital = $_POST['hospital'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$address = $_POST['address'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO doctors(full_name,age,gender,specialization,medical_registration_no,experience,hospital,email,phone,address,password)
VALUES(?,?,?,?,?,?,?,?,?,?,?)");

$stmt->bind_param(
"sisssisssss",
$full_name,
$age,
$gender,
$specialization,
$medical_registration_no,
$experience,
$hospital,
$email,
$phone,
$address,
$password
);

if($stmt->execute()){

echo "<script>
alert('Doctor Registration Successful');
window.location='index.php';
</script>";

}else{

echo "Error : ".$stmt->error;

}

$stmt->close();
$conn->close();

?>