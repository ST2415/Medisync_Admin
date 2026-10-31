<?php
include "../../db_conn.php";
$id = $_GET['id'];

$sql = "DELETE FROM `medication_log` WHERE log_id = '$id'";
$result = mysqli_query($conn, $sql);

if($result){
    if(isset($_GET['user_id'])){
        header("Location: ../user-details.php?id=" . $_GET['user_id'] . "&tab=logs&msg=Record Delete Successfully");
    } else {
        header("Location: ../user.php?msg=Record Delete Successfully");
    }
    exit();
}
else {
    echo "Failed: " . mysqli_error($conn);
}
?>
