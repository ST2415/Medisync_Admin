<?php
include "../db_conn.php";

$appointment_id = $_GET['appointment_id'] ?? $_GET['id'] ?? null;

if (!$appointment_id) {
    header("Location: appointment.php");
    exit();
}

// ตรวจสอบว่ามีการนัดหมายนี้อยู่ในระบบหรือไม่
$checkStmt = $conn->prepare("SELECT appointment_id FROM appointments WHERE appointment_id = ? LIMIT 1");
$checkStmt->bind_param("s", $appointment_id);
$checkStmt->execute();
$res = $checkStmt->get_result();

if (!$res || $res->num_rows === 0) {
    $checkStmt->close();
    header("Location: appointment.php?error=not_found");
    exit();
}
$checkStmt->close();

// ดำเนินการลบข้อมูล
$delStmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
$delStmt->bind_param("s", $appointment_id);
$result = $delStmt->execute();
$delStmt->close();

if ($result) {
    header("Location: appointment.php?msg=deleted");
    exit();
} else {
    echo "เกิดข้อผิดพลาดในการลบตารางนัดหมาย: " . $conn->error;
}
?>

