<?php
include "../../db_conn.php";
$id = $_GET['id'] ?? $_GET['schedule_id'];

$sql = "SELECT * FROM `schedules` WHERE schedule_id = '$id' LIMIT 1";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

if(isset($_POST['submit'])){
    $user_id = $_POST['user_id'] ?? $row['user_id'];
    $tpu_code = $_POST['tpu_code'];
    $time_to_take = $_POST['time_to_take'];
    $dosage_amount = $_POST['dosage_amount'];

    $sql = "UPDATE `schedules` SET `user_id`='$user_id',`tpu_code`='$tpu_code',`time_to_take`='$time_to_take',`dosage_amount`='$dosage_amount' WHERE schedule_id='$id'";

    $result = mysqli_query($conn, $sql);
    if($result) {
        header("Location: ../user-details.php?id=$user_id&tab=schedules&msg=Data Updated successfully");
        exit();
    }
    else{
        echo "Failed: " . mysqli_error($conn);
    }
}

$users_query = mysqli_query($conn, "SELECT * FROM `users`");
$medicines_query = mysqli_query($conn, "SELECT * FROM `medicines`");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขตารางเวลาการทานยา | Medisync</title>

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
            gap: 12px;
            padding: 8px 12px 24px 12px;
            border-bottom: 1px solid rgba(187, 215, 218, 0.18);
            margin-bottom: 24px;
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

        .form-control[readonly] {
            background-color: #eef5f5;
            cursor: not-allowed;
            color: var(--text-muted);
            font-weight: 600;
        }

        /* Time Pill Selection */
        .time-pill-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 10px;
            margin-top: 6px;
        }

        .time-pill-option {
            position: relative;
        }

        .time-pill-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .time-pill-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 12px 14px;
            border: 1.5px solid rgba(74, 128, 126, 0.28);
            border-radius: 12px;
            background-color: #fafdfd;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            min-height: 112px;
        }

        .time-pill-label i {
            font-size: 18px;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-secondary);
            transition: all 0.2s ease;
        }

        .time-pill-label .time-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--color-primary);
        }

        .time-pill-label .time-hint {
            font-size: 11px;
            color: var(--text-muted);
        }

        .time-pill-option input[type="radio"]:checked + .time-pill-label {
            border-color: var(--color-teal);
            background: linear-gradient(135deg, rgba(74, 128, 126, 0.15) 0%, rgba(144, 194, 183, 0.22) 100%);
            box-shadow: 0 4px 12px rgba(74, 128, 126, 0.15);
        }

        .time-pill-option input[type="radio"]:checked + .time-pill-label i {
            color: var(--color-teal);
            transform: scale(1.15);
        }

        .time-pill-option input[type="radio"]:checked + .time-pill-label .time-name {
            color: var(--color-primary);
            font-weight: 700;
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

        .btn-toggle-sidebar {
            display: none;
            background: transparent;
            border: none;
            font-size: 20px;
            color: var(--color-primary);
            cursor: pointer;
        }

        /* Patient Select - Hide Dropdown Arrow */
        .patient-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: none !important;
            padding-right: 14px;
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
        <div class="brand-container">
            <div class="brand-icon">
                <i class="fa-solid fa-staff-snake"></i>
            </div>
            <div class="brand-text">
                <h1>Medisync</h1>
                <p>Clinic Administration</p>
            </div>
        </div>

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
                    <i class="fa-solid fa-chevron-right" style="font-size: 10px;"></i>
                    <a href="../user-details.php?id=<?= urlencode($row['user_id']) ?>&tab=schedules">ประวัติผู้ป่วย</a>
                    <i class="fa-solid fa-chevron-right" style="font-size: 10px;"></i>
                    <span style="color: var(--color-primary); font-weight: 600;">แก้ไขตารางยา</span>
                </div>
            </div>
        </header>

        <!-- Body Content -->
        <div class="content-body">
            <!-- Header Card -->
            <div class="page-header-card">
                <div>
                    <h2><i class="fa-solid fa-pen-to-square me-2"></i>แก้ไขตารางเวลาการทานยา (Edit Schedule)</h2>
                    <p>รหัสตารางยา: <strong><?= htmlspecialchars($id) ?></strong></p>
                </div>
                <div>
                    <a href="../user-details.php?id=<?= urlencode($row['user_id']) ?>&tab=schedules" class="btn-back-header">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>ย้อนกลับไปประวัติผู้ป่วย</span>
                    </a>
                </div>
            </div>

            <!-- Form Card -->
            <div class="form-card">
                <form method="POST">
                    <!-- ข้อมูลทั่วไป -->
                    <div class="form-section-title">
                        <i class="fa-solid fa-clipboard-list"></i>
                        <span>ข้อมูลตารางการทานยา</span>
                    </div>

                    <div class="row g-3 mb-4">
                        <!-- Schedule ID (Readonly) -->
                        <div class="col-12 col-md-4">
                            <label class="form-label">
                                รหัสตาราง (Schedule ID)
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($row['schedule_id']) ?>" 
                                   readonly>
                            <small class="text-muted" style="font-size: 11.5px;">รหัสตารางเป็น Primary Key ไม่สามารถแก้ไขได้</small>
                        </div>

                        <!-- User / Patient Select (Locked) -->
                        <div class="col-12 col-md-8">
                            <label class="form-label">
                                ผู้ป่วยที่จัดยา (Patient)
                            </label>

                            <input type="hidden" name="user_id"
                                value="<?= htmlspecialchars($row['user_id']) ?>">

                            <select class="form-select patient-select" disabled
                                    style="background-color: #eef5f5; cursor: not-allowed; color: var(--color-primary); font-weight: 500;">
                                <?php if ($users_query && mysqli_num_rows($users_query) > 0): ?>
                                    <?php while ($user = mysqli_fetch_assoc($users_query)): ?>
                                        <option value="<?= $user['user_id'] ?>"
                                            <?= ($row['user_id'] == $user['user_id']) ? 'selected' : '' ?>>
                                            รหัส #<?= $user['user_id'] ?> : <?= htmlspecialchars($user['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>

                            <small class="text-muted" style="font-size: 11.5px;">
                                ข้อมูลผู้ป่วยไม่สามารถเปลี่ยนแปลงได้
                            </small>
                        </div>
                    </div>

                    <!-- Medicine & Dose -->
                    <div class="form-section-title">
                        <i class="fa-solid fa-capsules"></i>
                        <span>รายการยาและขนาดที่รับประทาน</span>
                    </div>

                    <div class="row g-3 mb-4">
                        <!-- Medicine Select -->
                        <div class="col-12 col-md-8">
                            <label class="form-label">
                                เลือกยาในคลัง (Medicine / TPU) <span class="required">*</span>
                            </label>
                            <select name="tpu_code" id="tpu_code" class="form-select" required>
                                <option value="">-- ค้นหาและเลือกรายการยา --</option>
                                <?php if ($medicines_query && mysqli_num_rows($medicines_query) > 0): ?>
                                    <?php while ($med = mysqli_fetch_assoc($medicines_query)): ?>
                                        <option value="<?= $med['tpu_code'] ?>" <?= ($row['tpu_code'] == $med['tpu_code']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($med['trade_name']) ?> 
                                            <?= !empty($med['strength']) ? '(' . htmlspecialchars($med['strength']) . ')' : '' ?>
                                            <?= !empty($med['dosageform']) ? '[' . htmlspecialchars($med['dosageform']) . ']' : '' ?>
                                            - TPU: <?= $med['tpu_code'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                            <div class="chip-container">
                                <span class="text-muted" style="font-size: 11.5px; align-self: center;">เปลี่ยนเป็นยา:</span>
                                <span class="chip-btn" onclick="selectMedicineByKeyword('ALGYCON')">ALGYCON</span>
                                <span class="chip-btn" onclick="selectMedicineByKeyword('COPLAVIX')">COPLAVIX</span>
                                <span class="chip-btn" onclick="selectMedicineByKeyword('Paracetamol')">Paracetamol</span>
                                <span class="chip-btn" onclick="selectMedicineByKeyword('Amoxicillin')">Amoxicillin</span>
                                <span class="chip-btn" onclick="selectMedicineByKeyword('Metformin')">Metformin</span>
                            </div>
                        </div>

                        <!-- Dosage Amount -->
                        <div class="col-12 col-md-4">
                            <label class="form-label">
                                จำนวนที่ทานต่อครั้ง (Dosage Amount) <span class="required">*</span>
                            </label>
                            <input type="number" 
                                   name="dosage_amount" 
                                   id="dosage_amount" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($row['dosage_amount']) ?>" 
                                   min="1" 
                                   max="20" 
                                   required>
                            <div class="chip-container">
                                <span class="chip-btn" onclick="setDosage(1)">1 เม็ด</span>
                                <span class="chip-btn" onclick="setDosage(2)">2 เม็ด</span>
                                <span class="chip-btn" onclick="setDosage(3)">3 เม็ด</span>
                                <span class="chip-btn" onclick="setDosage(4)">4 เม็ด</span>
                            </div>
                        </div>
                    </div>

                    <!-- Time to take -->
                    <div class="form-section-title">
                        <i class="fa-solid fa-clock"></i>
                        <span>ช่วงเวลาที่ต้องรับประทาน (Time to take) <span class="required">*</span></span>
                    </div>

                    <div class="mb-4">
                        <div class="time-pill-grid">
                            <div class="time-pill-option">
                                <input type="radio" name="time_to_take" id="time_morning" value="เช้า" <?= ($row['time_to_take'] == 'เช้า') ? 'checked' : '' ?>>
                                <label for="time_morning" class="time-pill-label">
                                    <!-- <i class="fa-solid fa-sun"></i> -->
                                    <span class="time-name">เช้า</span>
                                    <span class="time-hint">07:00 - 08:30 น.</span>
                                </label>
                            </div>

                            <div class="time-pill-option">
                                <input type="radio" name="time_to_take" id="time_afternoon" value="กลางวัน" <?= ($row['time_to_take'] == 'กลางวัน') ? 'checked' : '' ?>>
                                <label for="time_afternoon" class="time-pill-label">
                                    <!-- <i class="fa-solid fa-cloud-sun"></i> -->
                                    <span class="time-name">กลางวัน</span>
                                    <span class="time-hint">11:30 - 13:00 น.</span>
                                </label>
                            </div>

                            <div class="time-pill-option">
                                <input type="radio" name="time_to_take" id="time_evening" value="เย็น" <?= ($row['time_to_take'] == 'เย็น') ? 'checked' : '' ?>>
                                <label for="time_evening" class="time-pill-label">
                                    <!-- <i class="fa-solid fa-sunset"></i> -->
                                    <span class="time-name">เย็น</span>
                                    <span class="time-hint">17:00 - 18:30 น.</span>
                                </label>
                            </div>

                            <div class="time-pill-option">
                                <input type="radio" name="time_to_take" id="time_bedtime" value="ก่อนนอน" <?= ($row['time_to_take'] == 'ก่อนนอน') ? 'checked' : '' ?>>
                                <label for="time_bedtime" class="time-pill-label">
                                    <!-- <i class="fa-solid fa-moon"></i> -->
                                    <span class="time-name">ก่อนนอน</span>
                                    <span class="time-hint">20:30 - 22:00 น.</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Form Action Buttons -->
                    <div class="d-flex align-items-center gap-3 pt-3 border-top">
                        <button type="submit" name="submit" class="btn-submit-custom">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>บันทึกการแก้ไข</span>
                        </button>
                        <a href="../user-details.php?id=<?= urlencode($row['user_id']) ?>&tab=schedules" class="btn-cancel-custom">
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
        // Dosage setter
        function setDosage(val) {
            document.getElementById('dosage_amount').value = val;
        }

        // Select medicine by keyword
        function selectMedicineByKeyword(keyword) {
            const select = document.getElementById('tpu_code');
            const lowerKw = keyword.toLowerCase();
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].text.toLowerCase().includes(lowerKw)) {
                    select.selectedIndex = i;
                    break;
                }
            }
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
