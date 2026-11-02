<?php
$pageTitle = "Edit Appointment";
include "../db_conn.php";

// ฟังก์ชัน Escape HTML
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$appointment_id = $_GET['appointment_id'] ?? $_GET['id'] ?? null;

if (!$appointment_id) {
    header("Location: appointment.php");
    exit();
}

$errorMsg = "";

// ดึงข้อมูลการนัดหมายเดิม
$stmt = $conn->prepare("
    SELECT a.*, u.name AS user_name, u.gender, u.disease, u.medicine
    FROM appointments a
    LEFT JOIN users u ON a.user_id = u.user_id
    WHERE a.appointment_id = ?
    LIMIT 1
");
$stmt->bind_param("s", $appointment_id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    $stmt->close();
    header("Location: appointment.php?error=not_found");
    exit();
}

$apt = $res->fetch_assoc();
$stmt->close();

// ดึงรายชื่อผู้ป่วยทั้งหมดสำหรับ Dropdown
$usersList = [];
$resUsers = mysqli_query($conn, "SELECT user_id, name, gender, disease, medicine FROM users ORDER BY name ASC");
if ($resUsers) {
    while ($u = mysqli_fetch_assoc($resUsers)) {
        $usersList[] = $u;
    }
}

// เมื่อ Submit Form แก้ไข
if (isset($_POST['submit'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $appointment_date = trim($_POST['appointment_date'] ?? '');
    $appointment_time = trim($_POST['appointment_time'] ?? '');
    $status = trim($_POST['status'] ?? 'รอยืนยัน');

    if ($appointment_time !== '' && strlen($appointment_time) === 5) {
        $appointment_time .= ":00";
    }

    if ($user_id <= 0) {
        $errorMsg = "กรุณาเลือกผู้ป่วยที่ต้องการนัดหมาย";
    } elseif ($appointment_date === '' || $appointment_time === '') {
        $errorMsg = "กรุณาระบุวันและเวลานัดหมายให้ครบถ้วน";
    } else {
        $updateStmt = $conn->prepare("
            UPDATE appointments
            SET user_id = ?, appointment_date = ?, appointment_time = ?, status = ?
            WHERE appointment_id = ?
        ");
        $updateStmt->bind_param("issss", $user_id, $appointment_date, $appointment_time, $status, $appointment_id);

        if ($updateStmt->execute()) {
            $updateStmt->close();
            header("Location: appointment.php?msg=updated");
            exit();
        } else {
            $errorMsg = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $conn->error;
            $updateStmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขการนัดหมาย (ID: <?= e($appointment_id) ?>) | Medisync</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Google Font: Prompt -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <style>
        :root {
            --color-primary: #294549;
            --color-secondary: #4E7F90;
            --color-teal: #4A807E;
            --color-mint: #90C2B7;
            --color-ice: #BBD7DA;
            --color-white: #FFFFFF;
            --color-black: #000000;

            --body-bg: #BBD7DA;
            --text-main: #294549;
            --text-muted: #537478;
            --card-bg: #FFFFFF;
            --card-border: rgba(74, 128, 126, 0.22);
            --sidebar-width: 270px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Prompt', sans-serif;
            background-color: var(--body-bg);
            background-image: 
                radial-gradient(circle at 12% 15%, rgba(144, 194, 183, 0.35) 0%, transparent 40%),
                radial-gradient(circle at 88% 85%, rgba(78, 127, 144, 0.2) 0%, transparent 45%);
            background-attachment: fixed;
            color: var(--text-main);
            font-size: 14px;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--color-primary) 0%, #1e3336 100%);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1050;
            padding: 24px 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 24px rgba(41, 69, 73, 0.25);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 12px;
            padding-bottom: 24px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(187, 215, 218, 0.15);
            text-decoration: none;
        }

        .brand-logo-img {
            max-width: 180px;
            height: auto;
            max-height: 42px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .brand-fallback {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .brand-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--color-mint);
            color: var(--color-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            box-shadow: 0 4px 12px rgba(144, 194, 183, 0.3);
        }

        .brand-text h4 {
            font-size: 19px;
            font-weight: 700;
            margin: 0;
            color: var(--color-white);
            letter-spacing: 0.5px;
        }

        .brand-text small {
            display: block;
            color: var(--color-ice);
            font-size: 11px;
            font-weight: 300;
            margin-top: 2px;
        }

        .menu-label {
            color: var(--color-mint);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding: 0 12px;
            margin-bottom: 10px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            flex-grow: 1;
        }

        .sidebar-menu li {
            margin-bottom: 6px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: 12px;
            color: #d7e7e8;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.22s ease;
            position: relative;
        }

        .sidebar-menu a i {
            width: 22px;
            text-align: center;
            font-size: 16px;
            color: var(--color-ice);
            transition: transform 0.2s ease, color 0.2s ease;
        }

        .sidebar-menu a:hover {
            background: rgba(78, 127, 144, 0.4);
            color: var(--color-white);
            transform: translateX(3px);
        }

        .sidebar-menu a:hover i {
            color: var(--color-mint);
            transform: scale(1.1);
        }

        .sidebar-menu a.active {
            background: linear-gradient(90deg, #4E7F90 0%, #3d6a79 100%);
            color: var(--color-white);
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(41, 69, 73, 0.25);
        }

        .sidebar-menu a.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 10%;
            height: 80%;
            width: 4px;
            background: var(--color-mint);
            border-radius: 0 4px 4px 0;
        }

        .sidebar-menu a.active i {
            color: var(--color-mint);
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease;
        }

        /* ===== TOPBAR ===== */
        .topbar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(74, 128, 126, 0.18);
            padding: 16px 34px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .breadcrumb-nav {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .breadcrumb-nav a {
            color: var(--color-secondary);
            font-weight: 500;
        }

        .content-wrapper {
            padding: 26px 34px 40px;
            flex-grow: 1;
        }

        /* Form Card */
        .form-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 26px rgba(41, 69, 73, 0.07);
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--color-secondary) 0%, var(--color-teal) 50%, var(--color-mint) 100%);
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 18px;
            border-bottom: 1px solid rgba(74, 128, 126, 0.15);
        }

        .form-header-title h4 {
            font-size: 20px;
            font-weight: 700;
            color: var(--color-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-header-title p {
            color: var(--text-muted);
            font-size: 12.5px;
            margin: 4px 0 0;
        }

        .btn-header-back {
            background: rgba(78, 127, 144, 0.12);
            color: var(--color-primary);
            border: 1px solid rgba(78, 127, 144, 0.3);
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-header-back:hover {
            background: var(--color-secondary);
            color: var(--color-white);
        }

        /* Form Inputs */
        .form-group-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--color-primary);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .custom-input {
            width: 100%;
            border: 1px solid rgba(74, 128, 126, 0.3);
            border-radius: 12px;
            padding: 11px 16px;
            font-family: inherit;
            font-size: 13px;
            color: var(--color-primary);
            background: #FAFCFC;
            transition: all 0.2s ease;
        }

        .custom-input:focus {
            outline: none;
            border-color: var(--color-secondary);
            background: #FFFFFF;
            box-shadow: 0 0 0 3px rgba(78, 127, 144, 0.18);
        }

        /* Date & Time Picker Indicator as Clickable Button */
        input[type="date"],
        input[type="time"],
        input[type="datetime-local"] {
            cursor: pointer;
        }

        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="datetime-local"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            background-color: rgba(74, 128, 126, 0.14);
            border: 1px solid rgba(74, 128, 126, 0.35);
            border-radius: 8px;
            padding: 6px 8px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23294549' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 15px 15px;
            box-shadow: 0 1px 4px rgba(41, 69, 73, 0.08);
            transition: all 0.2s ease;
        }

        input[type="time"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            background-color: rgba(74, 128, 126, 0.14);
            border: 1px solid rgba(74, 128, 126, 0.35);
            border-radius: 8px;
            padding: 6px 8px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23294549' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'%3E%3C/circle%3E%3Cpolyline points='12 6 12 12 16 14'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 15px 15px;
            box-shadow: 0 1px 4px rgba(41, 69, 73, 0.08);
            transition: all 0.2s ease;
        }

        input[type="date"]::-webkit-calendar-picker-indicator:hover,
        input[type="datetime-local"]::-webkit-calendar-picker-indicator:hover {
            background-color: #4A807E !important;
            border-color: #4A807E !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            background-size: 15px 15px !important;
            box-shadow: 0 2px 8px rgba(74, 128, 126, 0.45) !important;
            transform: scale(1.06);
        }

        input[type="time"]::-webkit-calendar-picker-indicator:hover {
            background-color: #4A807E !important;
            border-color: #4A807E !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'%3E%3C/circle%3E%3Cpolyline points='12 6 12 12 16 14'%3E%3C/polyline%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            background-size: 15px 15px !important;
            box-shadow: 0 2px 8px rgba(74, 128, 126, 0.45) !important;
            transform: scale(1.06);
        }

        .custom-input:read-only {
            background: rgba(187, 215, 218, 0.25);
            color: var(--text-muted);
            cursor: not-allowed;
            border-style: dashed;
        }

        /* Status Radio Pills */
        .status-radio-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .status-radio-label {
            flex: 1;
            min-width: 120px;
            cursor: pointer;
            margin: 0;
        }

        .status-radio-label input {
            display: none;
        }

        .status-pill-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 16px;
            border-radius: 12px;
            border: 1px solid rgba(74, 128, 126, 0.3);
            background: #FAFCFC;
            font-weight: 600;
            font-size: 13px;
            color: var(--color-primary);
            transition: all 0.2s ease;
        }

        .status-radio-label input[value="รอยืนยัน"]:checked + .status-pill-box {
            background: #FFF3CD;
            color: #856404;
            border-color: #ffeeba;
            box-shadow: 0 4px 12px rgba(243, 156, 18, 0.2);
        }

        .status-radio-label input[value="ยืนยัน"]:checked + .status-pill-box {
            background: rgba(144, 194, 183, 0.35);
            color: #175249;
            border-color: rgba(144, 194, 183, 0.8);
            box-shadow: 0 4px 12px rgba(46, 213, 115, 0.2);
        }

        .status-radio-label input[value="เลื่อน"]:checked + .status-pill-box {
            background: rgba(78, 127, 144, 0.2);
            color: var(--color-primary);
            border-color: var(--color-secondary);
            box-shadow: 0 4px 12px rgba(78, 127, 144, 0.2);
        }

        /* Quick Time Chips */
        .time-chips-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }

        .chip-time {
            background: rgba(187, 215, 218, 0.35);
            border: 1px solid rgba(74, 128, 126, 0.2);
            color: var(--color-primary);
            font-size: 11.5px;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.16s ease;
        }

        .chip-time:hover {
            background: var(--color-secondary);
            color: var(--color-white);
        }

        /* Selected Patient Details Box */
        .selected-patient-box {
            background: rgba(187, 215, 218, 0.25);
            border: 1px solid rgba(74, 128, 126, 0.2);
            border-radius: 12px;
            padding: 12px 16px;
            margin-top: 8px;
            font-size: 12.5px;
        }

        /* Form Actions */
        .form-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(74, 128, 126, 0.15);
            display: flex;
            gap: 14px;
            justify-content: flex-end;
            align-items: center;
        }

        .btn-submit-save {
            background: var(--color-secondary);
            color: var(--color-white);
            font-weight: 600;
            padding: 12px 28px;
            border-radius: 12px;
            border: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(78, 127, 144, 0.25);
            transition: all 0.22s ease;
        }

        .btn-submit-save:hover {
            background: var(--color-primary);
            transform: translateY(-2px);
        }

        .btn-cancel-link {
            background: rgba(187, 215, 218, 0.35);
            color: var(--color-primary);
            border: 1px solid rgba(74, 128, 126, 0.3);
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.22s ease;
        }

        .btn-cancel-link:hover {
            background: rgba(187, 215, 218, 0.7);
            color: var(--color-primary);
        }

        .footer {
            text-align: center;
            padding: 24px;
            color: var(--text-muted);
            font-size: 12px;
            border-top: 1px solid rgba(74, 128, 126, 0.15);
            background: rgba(255, 255, 255, 0.4);
            margin-top: auto;
        }

        .mobile-menu-btn {
            display: none;
            border: none;
            background: rgba(78, 127, 144, 0.12);
            color: var(--color-primary);
            width: 40px;
            height: 40px;
            border-radius: 10px;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
        }

        .sidebar-overlay {
            display: none;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .sidebar-overlay.show {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(41, 69, 73, 0.55);
                backdrop-filter: blur(4px);
                z-index: 1040;
            }
            .main-content {
                margin-left: 0;
            }
            .mobile-menu-btn {
                display: inline-flex;
            }
            .content-wrapper {
                padding: 20px 16px;
            }
            .form-card {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <a href="../index.php" class="sidebar-brand">
        <img src="../assets/logo-medisync.svg"
             alt="Medisync Logo"
             class="brand-logo-img"
             onerror="this.style.display='none'; document.getElementById('brandFallback').style.display='flex';">
        <div class="brand-fallback" id="brandFallback" style="display: none;">
            <div class="brand-icon-box">
                <i class="fa-solid fa-staff-snake"></i>
            </div>
            <div class="brand-text">
                <h4>Medisync</h4>
                <small>Medicine Management</small>
            </div>
        </div>
    </a>

    <div class="menu-label">เมนูหลัก / NAVIGATION</div>

    <ul class="sidebar-menu">
        <li>
            <a href="../index.php">
                <i class="fa-solid fa-chart-pie"></i>
                <span>หน้าหลัก (Dashboard)</span>
            </a>
        </li>
        <li>
            <a href="../user-page/user.php">
                <i class="fa-solid fa-users"></i>
                <span>ข้อมูลผู้ใช้ (Users)</span>
            </a>
        </li>
        <li>
            <a href="../medicine-page/medicine.php">
                <i class="fa-solid fa-pills"></i>
                <span>รายการยา (Medicines)</span>
            </a>
        </li>
        <li>
            <a href="appointment.php" class="active">
                <i class="fa-solid fa-calendar-check"></i>
                <span>การนัดหมาย (Appointments)</span>
            </a>
        </li>
    </ul>
</aside>

<!-- Main Content -->
<main class="main-content">
    <header class="topbar">
        <div>
            <div class="breadcrumb-nav">
                <a href="../index.php">หน้าหลัก</a>
                <i class="fa-solid fa-chevron-right" style="font-size: 10px;"></i>
                <a href="appointment.php">ตารางนัดหมาย</a>
                <i class="fa-solid fa-chevron-right" style="font-size: 10px;"></i>
                <span>แก้ไขการนัดหมาย</span>
            </div>
            <h5 class="fw-bold mb-0" style="color: var(--color-primary);">
                <i class="fa-solid fa-pen-to-square me-2" style="color: var(--color-secondary);"></i>
                แก้ไขการนัดหมาย: <?= e($appointment_id) ?>
            </h5>
        </div>

        <button class="mobile-menu-btn" id="mobileMenuBtn" type="button">
            <i class="fa-solid fa-bars"></i>
        </button>
    </header>

    <div class="content-wrapper">
        <div class="form-card">

            <div class="form-header">
                <div class="form-header-title">
                    <h4>
                        <i class="fa-solid fa-pen-to-square" style="color: var(--color-secondary);"></i>
                        แก้ไขรายละเอียดการนัดหมาย
                    </h4>
                    <p>รหัสนัดหมาย: <strong><?= e($appointment_id) ?></strong> &bull; ผู้ป่วย: <strong><?= e($apt['user_name'] ?? 'ผู้ใช้ ID #' . $apt['user_id']) ?></strong></p>
                </div>
                <a href="appointment.php" class="btn-header-back">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>กลับไปหน้าตารางนัดหมาย</span>
                </a>
            </div>

            <!-- Error Alert -->
            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius: 12px;">
                    <i class="fa-solid fa-circle-exclamation fs-5"></i>
                    <div><?= htmlspecialchars($errorMsg) ?></div>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" action="edit-appointment.php?appointment_id=<?= urlencode($appointment_id) ?>">
                <div class="row g-4">

                    <!-- Appointment ID (Readonly) -->
                    <div class="col-12 col-md-4">
                        <label class="form-group-label" for="appointment_id">
                            <i class="fa-solid fa-hashtag text-secondary"></i>
                            รหัสการนัดหมาย (Appointment ID)
                        </label>
                        <input type="text"
                               id="appointment_id"
                               class="custom-input"
                               value="<?= e($appointment_id) ?>"
                               readonly>
                        <small class="text-muted" style="font-size: 11px;">
                            * รหัสการนัดหมายไม่สามารถแก้ไขได้
                        </small>
                    </div>

                    <!-- User Selection -->
                    <div class="col-12 col-md-8">
                        <label class="form-group-label" for="user_id">
                            <i class="fa-solid fa-user-injured text-secondary"></i>
                            ผู้ป่วย / ผู้รับการนัดหมาย
                        </label>
                        <select id="user_id"
                                name="user_id"
                                class="custom-input"
                                onchange="onPatientSelected(this)"
                                required>
                            <?php foreach ($usersList as $u): ?>
                                <option value="<?= $u['user_id'] ?>"
                                        data-gender="<?= htmlspecialchars($u['gender']) ?>"
                                        data-disease="<?= htmlspecialchars($u['disease']) ?>"
                                        data-medicine="<?= htmlspecialchars($u['medicine']) ?>"
                                        <?= (int)$apt['user_id'] === (int)$u['user_id'] ? 'selected' : '' ?>>
                                    #<?= $u['user_id'] ?> - <?= htmlspecialchars($u['name']) ?> (โรค: <?= htmlspecialchars($u['disease'] ?: 'ไม่ระบุ') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Dynamic Patient Preview Box -->
                        <div id="patientInfoBox" class="selected-patient-box" style="display: block;">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fa-solid fa-hospital-user text-teal fs-4"></i>
                                <div>
                                    <div class="fw-bold" id="patientInfoName">
                                        #<?= e($apt['user_id']) ?> - <?= e($apt['user_name'] ?? 'ไม่ระบุชื่อ') ?>
                                    </div>
                                    <div class="text-muted small">
                                        โรคประจำตัว: <span id="patientInfoDisease" class="fw-semibold text-dark"><?= e($apt['disease'] ?? 'ไม่ระบุ') ?></span> &bull; ยาที่ใช้: <span id="patientInfoMedicine" class="fw-semibold text-dark"><?= e($apt['medicine'] ?? 'ไม่ระบุ') ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Appointment Date -->
                    <div class="col-12 col-md-6">
                        <label class="form-group-label" for="appointment_date">
                            <i class="fa-regular fa-calendar-days text-secondary"></i>
                            วันที่นัดหมาย (Appointment Date)
                        </label>
                        <input type="date"
                               id="appointment_date"
                               name="appointment_date"
                               class="custom-input"
                               value="<?= e($apt['appointment_date']) ?>"
                               onclick="this.showPicker && this.showPicker()"
                               required>
                    </div>

                    <!-- Appointment Time -->
                    <div class="col-12 col-md-6">
                        <label class="form-group-label" for="appointment_time">
                            <i class="fa-regular fa-clock text-secondary"></i>
                            เวลานัดหมาย (Appointment Time)
                        </label>
                        <input type="time"
                               id="appointment_time"
                               name="appointment_time"
                               class="custom-input"
                               value="<?= e(substr($apt['appointment_time'], 0, 5)) ?>"
                               onclick="this.showPicker && this.showPicker()"
                               required>
                        <!-- Quick Time Chips -->
                        <div class="time-chips-wrap">
                            <span class="chip-time" onclick="setAppointmentTime('08:30')">08:30 น.</span>
                            <span class="chip-time" onclick="setAppointmentTime('09:15')">09:15 น.</span>
                            <span class="chip-time" onclick="setAppointmentTime('10:00')">10:00 น.</span>
                            <span class="chip-time" onclick="setAppointmentTime('11:30')">11:30 น.</span>
                            <span class="chip-time" onclick="setAppointmentTime('13:15')">13:15 น.</span>
                            <span class="chip-time" onclick="setAppointmentTime('14:45')">14:45 น.</span>
                            <span class="chip-time" onclick="setAppointmentTime('15:30')">15:30 น.</span>
                        </div>
                    </div>

                    <!-- Status Radio Buttons -->
                    <div class="col-12">
                        <label class="form-group-label">
                            <i class="fa-solid fa-list-check text-secondary"></i>
                            สถานะการนัดหมาย (Status)
                        </label>
                        <div class="status-radio-group">
                            <label class="status-radio-label">
                                <input type="radio" name="status" value="รอยืนยัน" <?= $apt['status'] === 'รอยืนยัน' ? 'checked' : '' ?>>
                                <div class="status-pill-box">
                                    <i class="fa-solid fa-clock text-warning"></i>
                                    <span>รอยืนยัน</span>
                                </div>
                            </label>

                            <label class="status-radio-label">
                                <input type="radio" name="status" value="ยืนยัน" <?= $apt['status'] === 'ยืนยัน' ? 'checked' : '' ?>>
                                <div class="status-pill-box">
                                    <i class="fa-solid fa-circle-check text-success"></i>
                                    <span>ยืนยันแล้ว</span>
                                </div>
                            </label>

                            <label class="status-radio-label">
                                <input type="radio" name="status" value="เลื่อน" <?= $apt['status'] === 'เลื่อน' ? 'checked' : '' ?>>
                                <div class="status-pill-box">
                                    <i class="fa-solid fa-clock-rotate-left text-secondary"></i>
                                    <span>เลื่อนนัด</span>
                                </div>
                            </label>
                        </div>
                    </div>

                </div>

                <div class="form-actions">
                    <a href="appointment.php" class="btn-cancel-link">
                        <i class="fa-solid fa-xmark"></i>
                        <span>ยกเลิก</span>
                    </a>

                    <button type="submit" name="submit" class="btn-submit-save">
                        <i class="fa-solid fa-check"></i>
                        <span>บันทึกการแก้ไข</span>
                    </button>
                </div>

            </form>

        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        &copy; <?= date('Y') ?> <strong>Medisync</strong>. Personal Medicine Management System.
    </footer>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function setAppointmentTime(timeStr) {
        document.getElementById('appointment_time').value = timeStr;
    }

    function onPatientSelected(selectElem) {
        const selectedOpt = selectElem.options[selectElem.selectedIndex];
        const box = document.getElementById('patientInfoBox');

        if (selectElem.value !== '') {
            const disease = selectedOpt.getAttribute('data-disease') || 'ไม่ระบุ';
            const medicine = selectedOpt.getAttribute('data-medicine') || 'ไม่ระบุ';
            const text = selectedOpt.text;

            document.getElementById('patientInfoName').innerText = text;
            document.getElementById('patientInfoDisease').innerText = disease;
            document.getElementById('patientInfoMedicine').innerText = medicine;
            box.style.display = 'block';
        } else {
            box.style.display = 'none';
        }
    }

    // Sidebar Mobile Toggle
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const menuBtn = document.getElementById('mobileMenuBtn');

    if (menuBtn) {
        menuBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }
</script>

</body>
</html>

