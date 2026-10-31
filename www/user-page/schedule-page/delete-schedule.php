<?php
include "../../db_conn.php";
$id = $_GET['id'];

// ลบข้อมูลที่เกี่ยวข้องในตารางลูกก่อน เพื่อป้องกันข้อผิดพลาด Foreign Key Constraint
mysqli_query($conn, "DELETE FROM `medication_log` WHERE schedule_id = '$id'");

// ลบข้อมูลตารางยา
$sql = "DELETE FROM `schedules` WHERE schedule_id = '$id'";
$result = mysqli_query($conn, $sql);

if($result){
    if(isset($_GET['user_id'])){
        header("Location: ../user-details.php?id=" . $_GET['user_id'] . "&tab=schedules&msg=Record Delete Successfully");
    } else {
        header("Location: ../user.php?msg=Record Delete Successfully");
    }
    exit();
}
else {
    echo "Failed: " . mysqli_error($conn);
}
?>
