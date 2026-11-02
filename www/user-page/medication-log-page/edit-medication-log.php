<?php
include "../../db_conn.php";
$id = $_GET['id'] ?? $_GET['log_id'];

$sql = "SELECT * FROM `medication_log` WHERE log_id = '$id' LIMIT 1";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

if(isset($_POST['submit'])){
    $schedule_id = $_POST['schedule_id'] ?? $row['schedule_id'];
    $action_time = $_POST['action_time'];
    $status = $_POST['status'];
    $side_effect = $_POST['side_effect'];

    $sql = "UPDATE `medication_log` SET `schedule_id`='$schedule_id',`action_time`='$action_time',`status`='$status',`side_effect`='$side_effect' WHERE log_id='$id'";

    $result = mysqli_query($conn, $sql);
    if($result) {
        $user_id = $_GET['user_id'] ?? '';
        if(!empty($user_id)){
            header("Location: ../user-details.php?id=$user_id&tab=logs&msg=Data Updated successfully");
        } else {
            header("Location: ../user.php?msg=Data Updated successfully");
        }
        exit();
    }
    else{
        echo "Failed: " . mysqli_error($conn);
    }
}

$current_user_id = $_GET['user_id'] ?? '';
$schedules_query = mysqli_query($conn, "SELECT s.*, u.name AS user_name, m.trade_name FROM schedules s LEFT JOIN users u ON s.user_id = u.user_id LEFT JOIN medicines m ON s.tpu_code = m.tpu_code");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขประวัติการทานยา | Medisync</title>

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
            color: inherit;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #294549 0%, #1d3336 100%);
            color: var(--color-white);
            padding: 24px 16px;
            z-index: 1050;
            overflow-y: auto;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 4px 0 24px rgba(41, 69, 73, 0.18);
            display: flex;
            flex-direction: column;
        }

        .brand-container {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 12px;
            padding: 8px 12px 24px 12px;
            border-bottom: 1px solid rgba(187, 215, 218, 0.18);
            margin-bottom: 24px;
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

        .brand-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #90C2B7 0%, #4A807E 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-primary);
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            flex-shrink: 0;
        }

        .brand-text h1 {
            font-size: 19px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
            color: var(--color-white);
        }

        .brand-text p {
            font-size: 11px;
            color: var(--color-mint);
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nav-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: rgba(187, 215, 218, 0.6);
            margin: 16px 12px 8px;
            font-weight: 600;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 11px 16px;
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.82);
            font-weight: 400;
            transition: all 0.2s ease;
        }

        .nav-item a:hover {
            background: rgba(144, 194, 183, 0.18);
            color: var(--color-white);
            transform: translateX(4px);
        }

        .nav-item.active a {
            background: linear-gradient(90deg, #4E7F90 0%, #4A807E 100%);
            color: var(--color-white);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(41, 69, 73, 0.35);
        }

        .nav-item a i {
            font-size: 16px;
            width: 20px;
            text-align: center;
            color: var(--color-mint);
        }

        .nav-item.active a i {
            color: var(--color-white);
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease;
        }

        /* Topbar */
        .topbar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--card-border);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .breadcrumb-text {
            font-size: 13px;
            color: var(--text-muted);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .breadcrumb-text a {
            color: var(--color-secondary);
            font-weight: 500;
        }

        .breadcrumb-text a:hover {
            text-decoration: underline;
        }

        .content-body {
            padding: 24px 28px 48px;
            flex: 1;
        }

        /* Header Card */
        .page-header-card {
            background: linear-gradient(135deg, #294549 0%, #4E7F90 100%);
            border-radius: 18px;
            padding: 24px 28px;
            color: var(--color-white);
            box-shadow: 0 8px 24px rgba(41, 69, 73, 0.15);
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-header-card h2 {
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 6px 0;
        }

        .page-header-card p {
            margin: 0;
            color: var(--color-ice);
            font-size: 13.5px;
        }

        .btn-back-header {
            background: rgba(255, 255, 255, 0.15);
            color: var(--color-white);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-back-header:hover {
            background: rgba(255, 255, 255, 0.25);
            color: var(--color-white);
        }

        /* Form Card */
        .form-card {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--card-border);
            box-shadow: 0 4px 20px rgba(41, 69, 73, 0.08);
            padding: 28px;
            margin-bottom: 24px;
        }

        .form-section-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--color-primary);
            padding-bottom: 10px;
            border-bottom: 2px solid var(--color-ice);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section-title i {
            color: var(--color-teal);
        }

        .form-label {
            font-weight: 600;
            font-size: 13px;
            color: var(--color-primary);
            margin-bottom: 6px;
            display: block;
        }

        .form-label .required {
            color: #dc3545;
            margin-left: 2px;
        }

        .form-control, .form-select {
            border: 1.5px solid rgba(74, 128, 126, 0.28);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13.5px;
            font-family: inherit;
            color: var(--color-primary);
            transition: all 0.2s ease;
            background-color: #fafdfd;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--color-teal);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 128, 126, 0.18);
            background-color: #ffffff;
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

        .form-control[readonly] {
            background-color: #eef5f5;
            cursor: not-allowed;
            color: var(--text-muted);
            font-weight: 600;
        }

        /* Status Pill Selection */
        .status-pill-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-top: 6px;
        }

        .status-pill-option {
            position: relative;
        }

        .status-pill-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .status-pill-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 14px;
            border: 1.5px solid rgba(74, 128, 126, 0.28);
            border-radius: 12px;
            background-color: #fafdfd;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 600;
            font-size: 13.5px;
            color: var(--color-primary);
        }

        .status-pill-label i {
            font-size: 16px;
        }

        .status-pill-taken {
            color: #198754;
        }
        .status-pill-missed {
            color: #b07d08;
        }
        .status-pill-pending {
            color: var(--color-secondary);
        }

        .status-pill-option input[type="radio"]:checked + .status-pill-label {
            box-shadow: 0 4px 12px rgba(41, 69, 73, 0.15);
        }

        .status-pill-option input[type="radio"][value="กินแล้ว"]:checked + .status-pill-label {
            border-color: #198754;
            background: rgba(25, 135, 84, 0.12);
            color: #198754;
        }

        .status-pill-option input[type="radio"][value="ลืมกินยา"]:checked + .status-pill-label {
            border-color: #f59e0b;
            background: rgba(245, 158, 11, 0.12);
            color: #b07d08;
        }

        .status-pill-option input[type="radio"][value="ยังไม่ได้กิน"]:checked + .status-pill-label {
            border-color: var(--color-secondary);
            background: rgba(78, 127, 144, 0.12);
            color: var(--color-secondary);
        }

        /* Chips styling */
        .chip-container {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }

        .chip-btn {
            background: rgba(144, 194, 183, 0.2);
            color: var(--color-primary);
            border: 1px solid rgba(74, 128, 126, 0.25);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            user-select: none;
        }

        .chip-btn:hover {
            background: var(--color-teal);
            color: var(--color-white);
            border-color: var(--color-teal);
            transform: translateY(-1px);
        }

        /* Buttons */
        .btn-submit-custom {
            background: linear-gradient(135deg, #294549 0%, #4A807E 100%);
            color: var(--color-white);
            border: none;
            border-radius: 10px;
            padding: 11px 24px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(41, 69, 73, 0.25);
        }

        .btn-submit-custom:hover {
            background: linear-gradient(135deg, #1d3336 0%, #3a6866 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(41, 69, 73, 0.35);
            color: var(--color-white);
        }

        .btn-cancel-custom {
            background: rgba(78, 127, 144, 0.12);
            color: var(--color-primary);
            border: 1px solid rgba(78, 127, 144, 0.25);
            border-radius: 10px;
            padding: 11px 20px;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-cancel-custom:hover {
            background: rgba(78, 127, 144, 0.22);
            color: var(--color-primary);
        }

        .btn-now-helper {
            background: rgba(74, 128, 126, 0.15);
            color: var(--color-teal);
            border: 1px solid rgba(74, 128, 126, 0.3);
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-now-helper:hover {
            background: var(--color-teal);
            color: var(--color-white);
        }

        .btn-toggle-sidebar {
            display: none;
            background: transparent;
            border: none;
            font-size: 20px;
            color: var(--color-primary);
            cursor: pointer;
        }

        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .btn-toggle-sidebar {
                display: block;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <a href="../../index.php" class="brand-container">
            <img src="../../assets/logo-medisync.svg"
                 alt="Medisync Logo"
                 class="brand-logo-img"
                 onerror="this.style.display='none'; document.getElementById('brandFallback').style.display='flex';">
            <div class="brand-fallback" id="brandFallback" style="display: none; align-items: center; justify-content: center; gap: 12px;">
                <div class="brand-icon">
                    <i class="fa-solid fa-staff-snake"></i>
                </div>
                <div class="brand-text">
                    <h1>Medisync</h1>
                    <p>Clinic Administration</p>
                </div>
            </div>
        </a>

        <div class="nav-label">เมนูหลัก (Navigation)</div>
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="../../index.php">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>แผงควบคุม (Dashboard)</span>
                </a>
            </li>
            <li class="nav-item active">
                <a href="../user.php">
                    <i class="fa-solid fa-hospital-user"></i>
                    <span>ข้อมูลผู้ป่วย (Patients)</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../../medicine-page/medicine.php">
                    <i class="fa-solid fa-pills"></i>
                    <span>คลังยา (Medicines)</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../../appointment-page/appointment.php">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>การนัดหมาย (Appointments)</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn-toggle-sidebar" id="toggleSidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="breadcrumb-text">
                    <a href="../../index.php"><i class="fa-solid fa-house"></i> หน้าหลัก</a>
                    <i class="fa-solid fa-chevron-right" style="font-size: 10px;"></i>
                    <a href="../user.php">ผู้ป่วย</a>
                    <?php if (!empty($current_user_id)): ?>
                        <i class="fa-solid fa-chevron-right" style="font-size: 10px;"></i>
                        <a href="../user-details.php?id=<?= urlencode($current_user_id) ?>&tab=logs">ประวัติผู้ป่วย</a>
                    <?php endif; ?>
                    <i class="fa-solid fa-chevron-right" style="font-size: 10px;"></i>
                    <span style="color: var(--color-primary); font-weight: 600;">แก้ไขประวัติการกินยา</span>
                </div>
            </div>
        </header>

        <!-- Body Content -->
        <div class="content-body">
            <!-- Header Card -->
            <div class="page-header-card">
                <div>
                    <h2><i class="fa-solid fa-pen-to-square me-2"></i>แก้ไขประวัติการทานยา (Edit Medication Log)</h2>
                    <p>รหัสบันทึก: <strong><?= htmlspecialchars($id) ?></strong></p>
                </div>
                <div>
                    <a href="<?= !empty($current_user_id) ? '../user-details.php?id=' . urlencode($current_user_id) . '&tab=logs' : '../user.php' ?>" class="btn-back-header">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>ย้อนกลับไปประวัติผู้ป่วย</span>
                    </a>
                </div>
            </div>

            <!-- Form Card -->
            <div class="form-card">
                <form method="POST">
                    <!-- ข้อมูลรายการ -->
                    <div class="form-section-title">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>ข้อมูลการรับประทานยา</span>
                    </div>

                    <div class="row g-3 mb-4">
                        <!-- Log ID (Read-only) -->
                        <div class="col-12 col-md-4">
                            <label class="form-label">
                                รหัสบันทึก (Log ID)
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($row['log_id']) ?>" 
                                   readonly>
                            <small class="text-muted" style="font-size: 11.5px;">รหัสบันทึกเป็น Primary Key ไม่สามารถแก้ไขได้</small>
                        </div>

                        <!-- Schedule Select (Locked) -->
                        <div class="col-12 col-md-8">
                            <label class="form-label">
                                ตารางยาที่รับประทาน (Schedule)
                            </label>
                            <input type="text"
                                class="form-control"
                                value="<?php
                                    $schedule_text = '';

                                    if ($schedules_query && mysqli_num_rows($schedules_query) > 0) {
                                        while ($sch = mysqli_fetch_assoc($schedules_query)) {
                                            if ($row['schedule_id'] == $sch['schedule_id']) {
                                                $schedule_text = '[ผู้ป่วย: ' . $sch['user_name'] . '] - ตาราง: '
                                                    . $sch['schedule_id'] . ' | ยา: '
                                                    . $sch['trade_name'] . ' (มื้อ'
                                                    . $sch['time_to_take'] . ' '
                                                    . $sch['dosage_amount'] . ' เม็ด)';
                                                break;
                                            }
                                        }
                                    }

                                    echo htmlspecialchars($schedule_text);
                                ?>"
                                readonly>

                            <input type="hidden"
                                name="schedule_id"
                                value="<?= htmlspecialchars($row['schedule_id']) ?>">
                        </div>
                    </div>

                    <!-- Action Time & Status -->
                    <div class="form-section-title">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span>วัน-เวลาและสถานะการทานยา</span>
                    </div>

                    <div class="row g-3 mb-4">
                        <!-- Action Time -->
                        <div class="col-12 col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0">
                                    วัน-เวลาที่ทานยา (Action Time) <span class="required">*</span>
                                </label>
                                <button type="button" class="btn-now-helper" onclick="setCurrentDateTime()">
                                    <i class="fa-solid fa-clock me-1"></i> เวลาปัจจุบัน
                                </button>
                            </div>
                            <input type="datetime-local" 
                                   name="action_time" 
                                   id="action_time" 
                                   class="form-control" 
                                   value="<?= date('Y-m-d\TH:i', strtotime($row['action_time'])) ?>" 
                                   onclick="this.showPicker && this.showPicker()"
                                   required>
                        </div>

                        <!-- Status Selection -->
                        <div class="col-12 col-md-6">
                            <label class="form-label">
                                สถานะการทานยา (Status) <span class="required">*</span>
                            </label>
                            <div class="status-pill-grid">
                                <div class="status-pill-option">
                                    <input type="radio" name="status" id="status_taken" value="กินแล้ว" <?= ($row['status'] == 'กินแล้ว') ? 'checked' : '' ?>>
                                    <label for="status_taken" class="status-pill-label">
                                        <i class="fa-solid fa-circle-check status-pill-taken"></i>
                                        <span>กินแล้ว</span>
                                    </label>
                                </div>

                                <div class="status-pill-option">
                                    <input type="radio" name="status" id="status_missed" value="ลืมกินยา" <?= ($row['status'] == 'ลืมกินยา') ? 'checked' : '' ?>>
                                    <label for="status_missed" class="status-pill-label">
                                        <i class="fa-solid fa-triangle-exclamation status-pill-missed"></i>
                                        <span>ลืมกินยา</span>
                                    </label>
                                </div>

                                <div class="status-pill-option">
                                    <input type="radio" name="status" id="status_pending" value="ยังไม่ได้กิน" <?= ($row['status'] == 'ยังไม่ได้กิน') ? 'checked' : '' ?>>
                                    <label for="status_pending" class="status-pill-label">
                                        <i class="fa-solid fa-hourglass-start status-pill-pending"></i>
                                        <span>ยังไม่ได้กิน</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Side Effects -->
                    <div class="form-section-title">
                        <i class="fa-solid fa-heart-pulse"></i>
                        <span>อาการข้างเคียงที่ตรวจพบ (Side Effect)</span>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">
                            ระบุอาการข้างเคียง
                        </label>
                        <input type="text" 
                               name="side_effect" 
                               id="side_effect" 
                               class="form-control" 
                               value="<?= htmlspecialchars($row['side_effect']) ?>" 
                               placeholder="เช่น ไม่มีอาการ, คลื่นไส้, เวียนศีรษะ, ง่วงนอน...">
                        <div class="chip-container">
                            <span class="text-muted" style="font-size: 11.5px; align-self: center;">คลิกเลือกอาการ:</span>
                            <span class="chip-btn" onclick="setSideEffect('ไม่มีอาการ')">🌿 ไม่มีอาการ</span>
                            <span class="chip-btn" onclick="setSideEffect('คลื่นไส้ / อาเจียน')">🤢 คลื่นไส้ / อาเจียน</span>
                            <span class="chip-btn" onclick="setSideEffect('เวียนศีรษะ')">💫 เวียนศีรษะ</span>
                            <span class="chip-btn" onclick="setSideEffect('ง่วงนอน / อ่อนเพลีย')">😴 ง่วงนอน / ซึม</span>
                            <span class="chip-btn" onclick="setSideEffect('ผื่นคัน / ลมพิษ')">🔴 ผื่นคัน / ลมพิษ</span>
                            <span class="chip-btn" onclick="setSideEffect('ปวดท้อง / จุกเสียด')">🩺 ปวดท้อง</span>
                            <span class="chip-btn" onclick="setSideEffect('ใจสั่น / ชีพจรเต้นเร็ว')">💔 ใจสั่น</span>
                            <span class="chip-btn" onclick="setSideEffect('คอแห้ง / ปากแห้ง')">💧 คอแห้ง</span>
                        </div>
                    </div>

                    <!-- Form Action Buttons -->
                    <div class="d-flex align-items-center gap-3 pt-3 border-top">
                        <button type="submit" name="submit" class="btn-submit-custom">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>บันทึกการแก้ไข</span>
                        </button>
                        <a href="<?= !empty($current_user_id) ? '../user-details.php?id=' . urlencode($current_user_id) . '&tab=logs' : '../user.php' ?>" class="btn-cancel-custom">
                            <i class="fa-solid fa-xmark"></i>
                            <span>ยกเลิก</span>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Helper Scripts -->
    <script>
        // Set side effect
        function setSideEffect(val) {
            document.getElementById('side_effect').value = val;
        }

        // Set current date time
        function setCurrentDateTime() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('action_time').value = `${year}-${month}-${day}T${hours}:${minutes}`;
        }

        // Toggle mobile sidebar
        const toggleBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }
    </script>
</body>
</html>
