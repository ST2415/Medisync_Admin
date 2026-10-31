<?php
include "../db_conn.php";
$id = $_GET['id'];

// ลบข้อมูลที่เกี่ยวข้องในตารางลูกก่อน เพื่อป้องกันข้อผิดพลาด Foreign Key Constraint
mysqli_query($conn, "DELETE ml FROM medication_log ml INNER JOIN schedules s ON ml.schedule_id = s.schedule_id WHERE s.user_id = $id");
mysqli_query($conn, "DELETE FROM schedules WHERE user_id = $id");
mysqli_query($conn, "DELETE FROM appointments WHERE user_id = $id");

// ลบข้อมูลผู้ใช้
$sql = "DELETE FROM `users` WHERE user_id = $id";
$result = mysqli_query($conn, $sql);

if($result){
    header("Location: user.php?msg=Record Delete Successfully");
    exit();
}
else {
    echo "Failed: " . mysqli_error($conn);
}
?>
