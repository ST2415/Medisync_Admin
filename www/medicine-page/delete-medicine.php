<?php
include "../db_conn.php";

$tpu_code = $_GET['tpu_code'] ?? $_GET['id'] ?? null;
$force = isset($_GET['force']) && ($_GET['force'] === '1' || $_GET['force'] === 'true');

if (!$tpu_code) {
    header("Location: medicine.php");
    exit();
}

$tpu_code = (int)$tpu_code;

// ตรวจสอบว่ามียานี้อยู่จริงหรือไม่
$medStmt = $conn->prepare("SELECT trade_name FROM medicines WHERE tpu_code = ? LIMIT 1");
$medStmt->bind_param("i", $tpu_code);
$medStmt->execute();
$medRes = $medStmt->get_result();

if (!$medRes || $medRes->num_rows === 0) {
    $medStmt->close();
    header("Location: medicine.php?error=not_found");
    exit();
}

$medData = $medRes->fetch_assoc();
$medName = $medData['trade_name'];
$medStmt->close();

// ตรวจสอบ Foreign Key: ดูว่ามียานี้ถูกใช้งานใน schedules หรือไม่
$schedStmt = $conn->prepare("SELECT COUNT(*) AS total FROM schedules WHERE tpu_code = ?");
$schedStmt->bind_param("i", $tpu_code);
$schedStmt->execute();
$schedRes = $schedStmt->get_result();
$usageCount = 0;
if ($schedRes && $row = $schedRes->fetch_assoc()) {
    $usageCount = (int)$row['total'];
}
$schedStmt->close();

if ($usageCount > 0 && !$force) {
    // ถ้ามียานี้ผูกอยู่ใน schedules และไม่ได้กดยืนยัน force delete ให้ส่งแจ้งเตือนกลับ
    header("Location: medicine.php?error=referenced&name=" . urlencode($medName));
    exit();
}

// หากผู้ใช้ยืนยันลบแบบ cascade หรือไม่มีข้อมูลผูกพัน
if ($usageCount > 0 && $force) {
    // 1. ลบ medication_log ที่ผูกกับ schedules ของยานี้
    $delLogStmt = $conn->prepare("
        DELETE ml 
        FROM medication_log ml 
        INNER JOIN schedules s ON ml.schedule_id = s.schedule_id 
        WHERE s.tpu_code = ?
    ");
    $delLogStmt->bind_param("i", $tpu_code);
    $delLogStmt->execute();
    $delLogStmt->close();

    // 2. ลบ schedules ของยานี้
    $delSchedStmt = $conn->prepare("DELETE FROM schedules WHERE tpu_code = ?");
    $delSchedStmt->bind_param("i", $tpu_code);
    $delSchedStmt->execute();
    $delSchedStmt->close();
}

// 3. ลบข้อมูลจากตาราง medicines
$delMedStmt = $conn->prepare("DELETE FROM medicines WHERE tpu_code = ?");
$delMedStmt->bind_param("i", $tpu_code);
$result = $delMedStmt->execute();
$delMedStmt->close();

if ($result) {
    header("Location: medicine.php?msg=deleted");
    exit();
} else {
    echo "เกิดข้อผิดพลาดในการลบข้อมูลยา: " . $conn->error;
}
?>

