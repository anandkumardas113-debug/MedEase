<?php
function ensureColumn($conn,$table,$column,$definition){
 $db=$conn->query('SELECT DATABASE() db')->fetch_assoc()['db'];
 $s=$conn->prepare('SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?');
 $s->bind_param('sss',$db,$table,$column);$s->execute();
 if(!(int)$s->get_result()->fetch_assoc()['c']) $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
}
function ensureMedEaseSchema($conn){
 ensureColumn($conn,'users','profile_photo','VARCHAR(255) NULL');
 ensureColumn($conn,'doctors','profile_photo','VARCHAR(255) NULL');
 ensureColumn($conn,'doctors','availability','VARCHAR(10) NOT NULL DEFAULT \'No\'');
 ensureColumn($conn,'appointments','status',"VARCHAR(30) NOT NULL DEFAULT 'Pending'");
 $conn->query("CREATE TABLE IF NOT EXISTS doctor_recommendations (id INT AUTO_INCREMENT PRIMARY KEY, doctor_id INT NOT NULL, user_id INT NOT NULL, recommendation TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(user_id), INDEX(doctor_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function saveProfilePhoto($file,$prefix){
 if(empty($file['name']) || $file['error']===UPLOAD_ERR_NO_FILE) return null;
 if($file['error']!==UPLOAD_ERR_OK || $file['size']>5*1024*1024) return false;
 $info=@getimagesize($file['tmp_name']); if(!$info) return false;
 $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
 if(!isset($allowed[$info['mime']])) return false;
 $dir=__DIR__.'/uploads/profile_photos'; if(!is_dir($dir)) mkdir($dir,0775,true);
 $name=$prefix.'_'.bin2hex(random_bytes(8)).'.'.$allowed[$info['mime']];
 return move_uploaded_file($file['tmp_name'],$dir.'/'.$name)?'uploads/profile_photos/'.$name:false;
}
?>
