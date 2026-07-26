<?php
session_start();include 'config.php';if(!isset($_SESSION['user_id'])){header('Location:index.php');exit;}$id=(int)$_SESSION['user_id'];
$name=trim($_POST['name']??'');$age=(int)($_POST['age']??0);$gender=trim($_POST['gender']??'');$blood=trim($_POST['bloodGroup']??'');$email=trim($_POST['email']??'');$phone=trim($_POST['phone']??'');$address=trim($_POST['address']??'');
$photo=saveProfilePhoto($_FILES['profile_photo']??[],'patient_'.$id); if($photo===false) die('Invalid profile image. Use JPG, PNG or WEBP up to 5 MB.');
if($photo){$s=$conn->prepare('UPDATE users SET name=?,age=?,gender=?,bloodGroup=?,email=?,phone=?,address=?,profile_photo=? WHERE id=?');$s->bind_param('sissssssi',$name,$age,$gender,$blood,$email,$phone,$address,$photo,$id);}else{$s=$conn->prepare('UPDATE users SET name=?,age=?,gender=?,bloodGroup=?,email=?,phone=?,address=? WHERE id=?');$s->bind_param('sisssssi',$name,$age,$gender,$blood,$email,$phone,$address,$id);} $s->execute();$_SESSION['name']=$name;$_SESSION['email']=$email;header('Location:index.php?profile=1');exit;
?>
