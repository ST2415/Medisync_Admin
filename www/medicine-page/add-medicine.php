<?php
$pageTitle = "Add Medicine";
include "../db_conn.php";

$errorMsg = "";

// คำนวณรหัส TPU ถัดไปโดยประมาณ (สำหรับเป็น placeholder หรือค่าเริ่มต้น)
$nextTpu = 100001;
$resMax = mysqli_query($conn, "SELECT MAX(tpu_code) AS max_code FROM medicines");
if ($resMax && $rowMax = mysqli_fetch_assoc($resMax)) {
    if (!empty($rowMax['max_code'])) {
        $nextTpu = (int)$rowMax['max_code'] + 1;
    }
}

if (isset($_POST['submit'])) {
    $tpu_code = trim($_POST['tpu_code'] ?? '');
    if ($tpu_code === '' || !is_numeric($tpu_code)) {
        $tpu_code = $nextTpu;
    } else {
        $tpu_code = (int)$tpu_code;
    }

    $trade_name = trim($_POST['trade_name'] ?? '');
    $active_ingredient = trim($_POST['active_ingredient'] ?? '');
    $strength = trim($_POST['strength'] ?? '');
    $dosageform = trim($_POST['dosageform'] ?? '');
    $indications = trim($_POST['indications'] ?? '');
    $side_effects = trim($_POST['side_effects'] ?? '');
    $precautions = trim($_POST['precautions'] ?? '');

    $image_url = trim($_POST['image_url'] ?? '');
    $remove_image = isset($_POST['remove_image']) && $_POST['remove_image'] === '1';

    if ($remove_image) {
        $image_url = '';
    }

    // จัดการอัปโหลดไฟล์รูปภาพจากเครื่อง
    if (isset($_FILES['medicine_image']) && $_FILES['medicine_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['medicine_image']['tmp_name'];
        $fileName = $_FILES['medicine_image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadDir = '../uploads/medicines/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            $newFileName = 'med_' . $tpu_code . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $image_url = $destPath;
            } else {
                $errorMsg = "ไม่สามารถบันทึกไฟล์รูปภาพที่อัปโหลดได้";
            }
        } else {
            $errorMsg = "รูปแบบไฟล์รูปภาพไม่ถูกต้อง (รองรับเฉพาะ JPG, PNG, WEBP, GIF)";
        }
    }

    // ตรวจสอบความถูกต้องเบื้องต้น
    if ($errorMsg === '' && ($trade_name === '' || $active_ingredient === '' || $dosageform === '')) {
        $errorMsg = "กรุณากรอกข้อมูลสำคัญให้ครบถ้วน (ชื่อทางการค้า, ตัวยาสำคัญ, รูปแบบยา)";
    } elseif ($errorMsg === '') {
        // ตรวจสอบว่ามีรหัส TPU นี้อยู่แล้วหรือไม่
        $checkStmt = $conn->prepare("SELECT tpu_code FROM medicines WHERE tpu_code = ? LIMIT 1");
        $checkStmt->bind_param("i", $tpu_code);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();

        if ($checkRes && $checkRes->num_rows > 0) {
            $errorMsg = "รหัส TPU Code {$tpu_code} มีอยู่ในระบบแล้ว กรุณาระบุรหัสอื่น";
            $checkStmt->close();
        } else {
            $checkStmt->close();
            // บันทึกข้อมูล
            $insertStmt = $conn->prepare("
                INSERT INTO medicines (
                    tpu_code, trade_name, active_ingredient, strength,
                    dosageform, indications, side_effects, precautions, image_url
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->bind_param(
                "issssssss",
                $tpu_code,
                $trade_name,
                $active_ingredient,
                $strength,
                $dosageform,
                $indications,
                $side_effects,
                $precautions,
                $image_url
            );

            if ($insertStmt->execute()) {
                $insertStmt->close();
                header("Location: medicine.php?msg=added");
                exit();
            } else {
                $errorMsg = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $conn->error;
                $insertStmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มรายการยาใหม่ | Medisync</title>

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
            max-width: 980px;
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
            background: linear-gradient(90deg, var(--color-primary) 0%, var(--color-secondary) 50%, var(--color-mint) 100%);
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

        /* Preset Box */
        .preset-box {
            background: linear-gradient(135deg, rgba(144, 194, 183, 0.15) 0%, rgba(187, 215, 218, 0.28) 100%);
            border: 1px dashed var(--color-teal);
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 26px;
        }

        .preset-box-title {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .preset-buttons-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .btn-preset-chip {
            background: #FFFFFF;
            border: 1px solid rgba(74, 128, 126, 0.3);
            color: var(--color-primary);
            font-size: 12px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-preset-chip:hover {
            background: var(--color-primary);
            color: var(--color-white);
            transform: translateY(-2px);
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

        /* Suggestions */
        .suggestions-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }

        .chip-suggest {
            background: rgba(187, 215, 218, 0.35);
            border: 1px solid rgba(74, 128, 126, 0.2);
            color: var(--color-primary);
            font-size: 11.5px;
            font-weight: 500;
            padding: 3px 10px;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.16s ease;
        }

        .chip-suggest:hover {
            background: var(--color-secondary);
            color: var(--color-white);
        }

        /* ===== IMAGE UPLOAD & PREVIEW SECTION ===== */
        .image-manager-card {
            background: #FFFFFF;
            border: 1px solid rgba(74, 128, 126, 0.22);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 16px rgba(41, 69, 73, 0.04);
        }

        .image-preview-card {
            border: 2px dashed rgba(74, 128, 126, 0.35);
            border-radius: 14px;
            padding: 20px;
            background: #FAFCFC;
            text-align: center;
            min-height: 180px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .image-preview-card:hover,
        .image-preview-card.drag-over {
            border-color: var(--color-secondary);
            background: rgba(187, 215, 218, 0.18);
        }

        .preview-img-display {
            max-height: 180px;
            max-width: 100%;
            object-fit: contain;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(41, 69, 73, 0.12);
            display: none;
        }

        .preview-placeholder {
            color: var(--text-muted);
            font-size: 13px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            user-select: none;
        }

        .img-badge-info {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            background: rgba(78, 127, 144, 0.12);
            color: var(--color-primary);
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 500;
            margin-top: 12px;
        }

        .btn-img-action {
            font-size: 12.5px;
            padding: 7px 16px;
            border-radius: 10px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.18s ease;
            border: none;
        }

        .btn-img-browse {
            background: var(--color-secondary);
            color: var(--color-white);
        }

        .btn-img-browse:hover {
            background: var(--color-primary);
        }

        .btn-img-link {
            background: rgba(187, 215, 218, 0.35);
            color: var(--color-primary);
            border: 1px solid rgba(74, 128, 126, 0.3);
        }

        .btn-img-link:hover,
        .btn-img-link.active {
            background: var(--color-secondary);
            border-color: var(--color-secondary);
            color: var(--color-white);
        }

        .btn-img-remove {
            background: rgba(220, 53, 69, 0.12);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.25);
        }

        .btn-img-remove:hover {
            background: #dc3545;
            color: #ffffff;
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
            background: var(--color-primary);
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
            box-shadow: 0 4px 14px rgba(41, 69, 73, 0.25);
            transition: all 0.22s ease;
        }

        .btn-submit-save:hover {
            background: var(--color-secondary);
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

        /* Mobile */
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
            <a href="medicine.php" class="active">
                <i class="fa-solid fa-pills"></i>
                <span>รายการยา (Medicines)</span>
            </a>
        </li>
        <li>
            <a href="../appointment-page/appointment.php">
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
                <a href="medicine.php">รายการยา</a>
                <i class="fa-solid fa-chevron-right" style="font-size: 10px;"></i>
                <span>เพิ่มยาใหม่</span>
            </div>
            <h5 class="fw-bold mb-0" style="color: var(--color-primary);">
                <i class="fa-solid fa-pills me-2" style="color: var(--color-teal);"></i>
                แบบฟอร์มเพิ่มรายการยา
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
                        <i class="fa-solid fa-plus-circle" style="color: var(--color-teal);"></i>
                        บันทึกรายการยาใหม่เข้าสู่ระบบ
                    </h4>
                    <p>กรอกข้อมูลตัวยาสำคัญ ความแรง รูปแบบ สรรพคุณ และคำแนะนำการใช้ยา</p>
                </div>
                <a href="medicine.php" class="btn-header-back">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>กลับไปหน้ารายการยา</span>
                </a>
            </div>

            <!-- Error Alert -->
            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius: 12px;">
                    <i class="fa-solid fa-circle-exclamation fs-5"></i>
                    <div><?= htmlspecialchars($errorMsg) ?></div>
                </div>
            <?php endif; ?>

            <!-- Preset / Shortcut Chips -->
            <div class="preset-box">
                <div class="preset-box-title">
                    <i class="fa-solid fa-wand-magic-sparkles text-primary"></i>
                    <span>ปุ่มลัดกรอกข้อมูลตัวอย่าง (Presets สำหรับทดสอบ)</span>
                </div>
                <div class="preset-buttons-wrap">
                    <button type="button" class="btn-preset-chip"
                            onclick="applyPreset('TYLENOL 500', 'paracetamol', '500 mg', 'tablet', 'บรรเทาอาการปวดและลดไข้', 'ไม่พบหากใช้ในขนาดปกติ', 'ระวังพิษต่อตับ ไม่ควรทานเกิน 4 กรัมต่อวัน', 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=500')">
                        <i class="fa-solid fa-pills text-success"></i>
                        <span>พาราเซตามอล (Tylenol)</span>
                    </button>

                    <button type="button" class="btn-preset-chip"
                            onclick="applyPreset('NORVASC', 'amlodipine', '5 mg', 'tablet', 'รักษาโรคความดันโลหิตสูงและโรคหลอดเลือดหัวใจ', 'ข้อเท้าบวม เวียนศีรษะ', 'ห้ามใช้ในสตรีมีครรภ์และผู้มีความดันต่ำ', '')">
                        <i class="fa-solid fa-heart-pulse text-danger"></i>
                        <span>ยาลดความดัน (Amlodipine)</span>
                    </button>

                    <button type="button" class="btn-preset-chip"
                            onclick="applyPreset('LIPITOR', 'atorvastatin', '20 mg', 'film-coated tablet', 'ยาลดระดับไขมันคอเลสเตอรอลในเลือด', 'ปวดกล้ามเนื้อ แน่นท้อง', 'หลีกเลี่ยงการดื่มแอลกอฮอล์ และทานสม่ำเสมอ', '')">
                        <i class="fa-solid fa-droplet text-warning"></i>
                        <span>ยาลดไขมัน (Atorvastatin)</span>
                    </button>

                    <button type="button" class="btn-preset-chip"
                            onclick="applyPreset('AMOXIL', 'amoxicillin', '500 mg', 'capsule, hard', 'รักษาการติดเชื้อแบคทีเรียในระบบทางเดินหายใจ', 'คลื่นไส้ ท้องเสีย ผื่นคัน', 'ต้องทานติดต่อกันจนหมดตามแพทย์สั่ง', '')">
                        <i class="fa-solid fa-capsules text-info"></i>
                        <span>ยาปฏิชีวนะ (Amoxicillin)</span>
                    </button>
                </div>
            </div>

            <!-- Form -->
            <form method="POST" action="add-medicine.php" enctype="multipart/form-data">
                <div class="row g-4">

                    <!-- TPU Code -->
                    <div class="col-12 col-md-4">
                        <label class="form-group-label" for="tpu_code">
                            <i class="fa-solid fa-hashtag text-secondary"></i>
                            รหัส TPU (TPU Code)
                        </label>
                        <input type="number"
                               id="tpu_code"
                               name="tpu_code"
                               class="custom-input"
                               placeholder="เช่น <?= $nextTpu ?>"
                               value="<?= $nextTpu ?>"
                               required>
                        <small class="text-muted" style="font-size: 11px;">
                            * รหัสอ้างอิงมาตรฐานยา (ตัวเลข ไม่ซ้ำกัน)
                        </small>
                    </div>

                    <!-- Trade Name -->
                    <div class="col-12 col-md-8">
                        <label class="form-group-label" for="trade_name">
                            <i class="fa-solid fa-tag text-secondary"></i>
                            ชื่อทางการค้าของยา (Trade Name)
                        </label>
                        <input type="text"
                               id="trade_name"
                               name="trade_name"
                               class="custom-input"
                               placeholder="เช่น ADVIL, COPLAVIX, PANADOL"
                               required>
                    </div>

                    <!-- Active Ingredient -->
                    <div class="col-12 col-md-7">
                        <label class="form-group-label" for="active_ingredient">
                            <i class="fa-solid fa-flask text-secondary"></i>
                            ตัวยาสำคัญ (Active Ingredient)
                        </label>
                        <input type="text"
                               id="active_ingredient"
                               name="active_ingredient"
                               class="custom-input"
                               placeholder="เช่น ibuprofen, paracetamol, amoxicillin"
                               required>
                        <div class="suggestions-wrap">
                            <span class="chip-suggest" onclick="setInputValue('active_ingredient', 'paracetamol')">paracetamol</span>
                            <span class="chip-suggest" onclick="setInputValue('active_ingredient', 'ibuprofen')">ibuprofen</span>
                            <span class="chip-suggest" onclick="setInputValue('active_ingredient', 'amoxicillin')">amoxicillin</span>
                            <span class="chip-suggest" onclick="setInputValue('active_ingredient', 'amlodipine')">amlodipine</span>
                            <span class="chip-suggest" onclick="setInputValue('active_ingredient', 'atorvastatin')">atorvastatin</span>
                            <span class="chip-suggest" onclick="setInputValue('active_ingredient', 'aspirin')">aspirin</span>
                            <span class="chip-suggest" onclick="setInputValue('active_ingredient', 'cetirizine hydrochloride')">cetirizine</span>
                        </div>
                    </div>

                    <!-- Strength -->
                    <div class="col-12 col-md-5">
                        <label class="form-group-label" for="strength">
                            <i class="fa-solid fa-bolt text-secondary"></i>
                            ความแรง / ขนาดยา (Strength)
                        </label>
                        <input type="text"
                               id="strength"
                               name="strength"
                               class="custom-input"
                               placeholder="เช่น 500 mg, 200 mg, 10 mg/5 mL"
                               required>
                        <div class="suggestions-wrap">
                            <span class="chip-suggest" onclick="setInputValue('strength', '500 mg')">500 mg</span>
                            <span class="chip-suggest" onclick="setInputValue('strength', '250 mg')">250 mg</span>
                            <span class="chip-suggest" onclick="setInputValue('strength', '200 mg')">200 mg</span>
                            <span class="chip-suggest" onclick="setInputValue('strength', '10 mg')">10 mg</span>
                            <span class="chip-suggest" onclick="setInputValue('strength', '5 mg')">5 mg</span>
                            <span class="chip-suggest" onclick="setInputValue('strength', '100 mg')">100 mg</span>
                        </div>
                    </div>

                    <!-- Dosage Form -->
                    <div class="col-12 col-md-6">
                        <label class="form-group-label" for="dosageform">
                            <i class="fa-solid fa-shapes text-secondary"></i>
                            รูปแบบยา (Dosage Form)
                        </label>
                        <input type="text"
                               id="dosageform"
                               name="dosageform"
                               class="custom-input"
                               placeholder="เช่น tablet, capsule, hard, syrup"
                               list="dosageFormList"
                               required>
                        <datalist id="dosageFormList">
                            <option value="tablet">
                            <option value="film-coated tablet">
                            <option value="chewable tablet">
                            <option value="coated tablet">
                            <option value="capsule, hard">
                            <option value="capsule, soft">
                            <option value="oral solution">
                            <option value="oral suspension">
                            <option value="solution for infusion">
                            <option value="syrup">
                            <option value="ointment">
                        </datalist>
                        <div class="suggestions-wrap">
                            <span class="chip-suggest" onclick="setInputValue('dosageform', 'tablet')">tablet</span>
                            <span class="chip-suggest" onclick="setInputValue('dosageform', 'film-coated tablet')">film-coated tablet</span>
                            <span class="chip-suggest" onclick="setInputValue('dosageform', 'capsule, hard')">capsule, hard</span>
                            <span class="chip-suggest" onclick="setInputValue('dosageform', 'capsule, soft')">capsule, soft</span>
                            <span class="chip-suggest" onclick="setInputValue('dosageform', 'oral suspension')">oral suspension</span>
                            <span class="chip-suggest" onclick="setInputValue('dosageform', 'syrup')">syrup</span>
                        </div>
                    </div>

                    <!-- Medicine Image Management Box -->
                    <div class="col-12">
                        <div class="image-manager-card">
                            <label class="form-group-label mb-1" style="font-size: 14px;">
                                <i class="fa-solid fa-image text-secondary me-1"></i>
                                รูปภาพเวชภัณฑ์ / รูปถ่ายยา (Medicine Image)
                            </label>
                            <div class="text-muted mb-3" style="font-size: 12px;">
                                จัดการรูปภาพยา สามารถเลือกรูปจากเครื่อง แนบลิงก์ หรือลบรูปภาพได้
                            </div>

                            <!-- Single Row with Exactly 1 of each button -->
                            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                                <input type="file"
                                       id="medicine_image"
                                       name="medicine_image"
                                       accept="image/png, image/jpeg, image/jpg, image/webp, image/gif"
                                       style="display: none;"
                                       onchange="handleFileSelect(event)">

                                <!-- 1. ปุ่มเลือกรูปภาพจากเครื่อง (1 ปุ่ม) -->
                                <button type="button" class="btn-img-action btn-img-browse" onclick="triggerFileInput()">
                                    <i class="fa-solid fa-folder-open"></i>
                                    <span>เลือกรูปจากเครื่อง</span>
                                </button>

                                <!-- 2. ปุ่มแนบลิงก์ (1 ปุ่ม) -->
                                <button type="button" class="btn-img-action btn-img-link" id="btnToggleUrl" onclick="toggleUrlInput()">
                                    <i class="fa-solid fa-link"></i>
                                    <span>แนบลิงก์รูปยา</span>
                                </button>

                                <!-- 3. ปุ่มลบรูป (1 ปุ่ม) -->
                                <button type="button" class="btn-img-action btn-img-remove" id="btnRemoveImg" onclick="removeCurrentImage()" style="display: none;">
                                    <i class="fa-solid fa-trash-can"></i>
                                    <span>ลบรูป</span>
                                </button>

                                <span id="selectedFileName" class="text-muted ms-2" style="font-size: 12px;"></span>
                            </div>

                            <!-- URL Input (shown when clicking "แนบลิงก์รูปยา") -->
                            <div id="urlInputContainer" class="mb-3" style="display: none;">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0" style="border-color: rgba(74, 128, 126, 0.3); border-radius: 12px 0 0 12px;">
                                        <i class="fa-solid fa-link text-secondary"></i>
                                    </span>
                                    <input type="url"
                                           id="image_url"
                                           name="image_url"
                                           class="form-control custom-input border-start-0"
                                           style="border-radius: 0 12px 12px 0;"
                                           placeholder="วางลิงก์รูปภาพ เช่น https://example.com/medicine.jpg"
                                           oninput="handleUrlInput(this.value)">
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                    * ภาพตัวอย่างจะแสดงทันทีเมื่อวางลิงก์ URL ที่ถูกต้อง
                                </small>
                            </div>

                            <!-- Hidden remove image flag -->
                            <input type="hidden" id="remove_image" name="remove_image" value="0">

                            <!-- Interactive Image Preview Box (กล่องเก็บภาพ) -->
                            <div class="image-preview-card" id="imagePreviewCard" onclick="triggerFileInput()" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)" title="คลิกเพื่อเลือกรูปภาพจากเครื่อง หรือลากไฟล์ภาพมาวาง">
                                <img id="previewImg"
                                     src=""
                                     alt="Medicine Preview"
                                     class="preview-img-display">

                                <div class="preview-placeholder" id="previewPlaceholder">
                                    <i class="fa-solid fa-cloud-arrow-up fs-2 opacity-50 text-secondary mb-1"></i>
                                    <strong>คลิกที่นี่เพื่อเลือกรูปภาพจากเครื่อง</strong>
                                    <span>หรือลากไฟล์ภาพมาวางในกล่องนี้ หรือใช้ปุ่มแนบลิงก์ด้านบน</span>
                                    <small class="opacity-75">(รองรับไฟล์ JPG, PNG, WEBP, GIF)</small>
                                </div>

                                <div id="previewStatusBadge" class="img-badge-info" style="display: none;">
                                    <i class="fa-solid fa-circle-check text-success"></i>
                                    <span id="badgeText"></span>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Indications -->
                    <div class="col-12">
                        <label class="form-group-label" for="indications">
                            <i class="fa-solid fa-heart-pulse text-secondary"></i>
                            สรรพคุณ / ข้อบ่งใช้ (Indications)
                        </label>
                        <textarea id="indications"
                                  name="indications"
                                  class="custom-input"
                                  rows="2"
                                  placeholder="ระบุข้อบ่งใช้ เช่น ยาบรรเทาอาการปวด ลดไข้ รักษาความดันโลหิตสูง"></textarea>
                        <div class="suggestions-wrap">
                            <span class="chip-suggest" onclick="setInputValue('indications', 'ยาแก้ปวด ลดการอักเสบ')">ยาแก้ปวด ลดการอักเสบ</span>
                            <span class="chip-suggest" onclick="setInputValue('indications', 'บรรเทาอาการปวดและลดไข้')">บรรเทาอาการปวดและลดไข้</span>
                            <span class="chip-suggest" onclick="setInputValue('indications', 'รักษาความดันโลหิตสูง')">รักษาความดันโลหิตสูง</span>
                            <span class="chip-suggest" onclick="setInputValue('indications', 'ยาลดไขมันในเลือด')">ยาลดไขมันในเลือด</span>
                            <span class="chip-suggest" onclick="setInputValue('indications', 'ยาลดกรด บรรเทาอาการกรดไหลย้อน')">ยาลดกรด บรรเทากรดไหลย้อน</span>
                            <span class="chip-suggest" onclick="setInputValue('indications', 'ยาต้านเกล็ดเลือด ป้องกันลิ่มเลือดอุดตัน')">ยาต้านเกล็ดเลือด</span>
                        </div>
                    </div>

                    <!-- Side Effects -->
                    <div class="col-12 col-md-6">
                        <label class="form-group-label" for="side_effects">
                            <i class="fa-solid fa-triangle-exclamation text-warning"></i>
                            ผลข้างเคียง (Side Effects)
                        </label>
                        <textarea id="side_effects"
                                  name="side_effects"
                                  class="custom-input"
                                  rows="2"
                                  placeholder="เช่น ปวดท้อง คลื่นไส้ เวียนศีรษะ ท้องเสีย"></textarea>
                        <div class="suggestions-wrap">
                            <span class="chip-suggest" onclick="setInputValue('side_effects', 'ไม่มีอาการ')">ไม่มีอาการ</span>
                            <span class="chip-suggest" onclick="setInputValue('side_effects', 'ปวดท้อง แสบร้อนกลางอก')">ปวดท้อง</span>
                            <span class="chip-suggest" onclick="setInputValue('side_effects', 'คลื่นไส้ อาเจียน')">คลื่นไส้</span>
                            <span class="chip-suggest" onclick="setInputValue('side_effects', 'ง่วงซึม เวียนศีรษะ')">ง่วงซึม</span>
                            <span class="chip-suggest" onclick="setInputValue('side_effects', 'ขาบวม ข้อเท้าบวม')">ขาบวม</span>
                            <span class="chip-suggest" onclick="setInputValue('side_effects', 'เลือดออกง่าย')">เลือดออกง่าย</span>
                        </div>
                    </div>

                    <!-- Precautions -->
                    <div class="col-12 col-md-6">
                        <label class="form-group-label" for="precautions">
                            <i class="fa-solid fa-shield-halved text-danger"></i>
                            ข้อควรระวัง (Precautions)
                        </label>
                        <textarea id="precautions"
                                  name="precautions"
                                  class="custom-input"
                                  rows="2"
                                  placeholder="เช่น ทานหลังอาหารทันที ระวังในผู้ป่วยโรคไต"></textarea>
                        <div class="suggestions-wrap">
                            <span class="chip-suggest" onclick="setInputValue('precautions', 'ทานหลังอาหารทันที ระวังในผู้ป่วยโรคกระเพาะ')">ทานหลังอาหารทันที</span>
                            <span class="chip-suggest" onclick="setInputValue('precautions', 'หลีกเลี่ยงการดื่มเครื่องดื่มแอลกอฮอล์')">งดแอลกอฮอล์</span>
                            <span class="chip-suggest" onclick="setInputValue('precautions', 'ห้ามใช้ในสตรีมีครรภ์')">ห้ามใช้ในสตรีมีครรภ์</span>
                            <span class="chip-suggest" onclick="setInputValue('precautions', 'เคี้ยวให้ละเอียดก่อนกลืน')">เคี้ยวให้ละเอียด</span>
                            <span class="chip-suggest" onclick="setInputValue('precautions', 'ปรึกษาแพทย์และเภสัชกรก่อนใช้')">ปรึกษาแพทย์ก่อนใช้</span>
                        </div>
                    </div>

                </div>

                <div class="form-actions">
                    <a href="medicine.php" class="btn-cancel-link">
                        <i class="fa-solid fa-xmark"></i>
                        <span>ยกเลิก</span>
                    </a>

                    <button type="submit" name="submit" class="btn-submit-save">
                        <i class="fa-solid fa-plus-circle"></i>
                        <span>บันทึกข้อมูลยา</span>
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
    function setInputValue(inputId, val) {
        const input = document.getElementById(inputId);
        if (input) {
            input.value = val;
            input.focus();
        }
    }

    function triggerFileInput() {
        document.getElementById('medicine_image').click();
    }

    function toggleUrlInput() {
        const urlWrap = document.getElementById('urlInputContainer');
        const btnLink = document.getElementById('btnToggleUrl');
        const urlInput = document.getElementById('image_url');
        if (urlWrap.style.display === 'none' || urlWrap.style.display === '') {
            urlWrap.style.display = 'block';
            btnLink.classList.add('active');
            urlInput.focus();
        } else {
            urlWrap.style.display = 'none';
            btnLink.classList.remove('active');
        }
    }

    function handleFileSelect(event) {
        const file = event.target.files[0];
        if (!file) return;

        if (!file.type.match('image.*')) {
            alert('กรุณาเลือกไฟล์ที่เป็นรูปภาพเท่านั้น (JPG, PNG, WEBP, GIF)');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            setPreviewImage(e.target.result, 'รูปจากเครื่อง: ' + file.name);
            document.getElementById('remove_image').value = '0';
            document.getElementById('image_url').value = '';
            document.getElementById('selectedFileName').innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-check"></i> ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)</span>';
            document.getElementById('btnRemoveImg').style.display = 'inline-flex';
        };
        reader.readAsDataURL(file);
    }

    function handleUrlInput(url) {
        document.getElementById('medicine_image').value = '';
        document.getElementById('selectedFileName').innerHTML = '';
        document.getElementById('remove_image').value = '0';

        if (url && url.trim() !== '') {
            setPreviewImage(url.trim(), 'ลิงก์รูปภาพภายนอก');
            document.getElementById('btnRemoveImg').style.display = 'inline-flex';
        } else {
            clearPreviewUI();
        }
    }

    function setPreviewImage(src, badgeLabel) {
        const previewImg = document.getElementById('previewImg');
        const placeholder = document.getElementById('previewPlaceholder');
        const badge = document.getElementById('previewStatusBadge');
        const badgeText = document.getElementById('badgeText');

        previewImg.src = src;
        previewImg.style.display = 'block';
        placeholder.style.display = 'none';
        badge.style.display = 'inline-flex';
        badgeText.innerText = badgeLabel;

        previewImg.onerror = function() {
            previewImg.style.display = 'none';
            placeholder.style.display = 'flex';
            placeholder.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-danger fs-3"></i><span>ไม่สามารถโหลดรูปภาพได้ กรุณาตรวจสอบไฟล์หรือ URL</span>';
            badge.style.display = 'none';
        };
        previewImg.onload = function() {
            previewImg.style.display = 'block';
            placeholder.style.display = 'none';
            badge.style.display = 'inline-flex';
        };
    }

    function clearPreviewUI() {
        const previewImg = document.getElementById('previewImg');
        const placeholder = document.getElementById('previewPlaceholder');
        const badge = document.getElementById('previewStatusBadge');

        previewImg.src = '';
        previewImg.style.display = 'none';
        placeholder.style.display = 'flex';
        placeholder.innerHTML = '<i class="fa-solid fa-cloud-arrow-up fs-2 opacity-50 text-secondary mb-1"></i><strong>คลิกที่นี่เพื่อเลือกรูปภาพจากเครื่อง</strong><span>หรือลากไฟล์ภาพมาวาง หรือใช้ปุ่มด้านบน</span><small class="opacity-75">(JPG, PNG, WEBP, GIF)</small>';
        badge.style.display = 'none';
        document.getElementById('btnRemoveImg').style.display = 'none';
    }

    function removeCurrentImage() {
        document.getElementById('remove_image').value = '1';
        document.getElementById('medicine_image').value = '';
        document.getElementById('image_url').value = '';
        document.getElementById('selectedFileName').innerHTML = '<span class="text-danger"><i class="fa-solid fa-trash-can me-1"></i> ลบรูปภาพแล้ว</span>';
        clearPreviewUI();
    }

    // Drag and Drop handlers
    function handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('imagePreviewCard').classList.add('drag-over');
    }

    function handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('imagePreviewCard').classList.remove('drag-over');
    }

    function handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('imagePreviewCard').classList.remove('drag-over');

        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            const file = e.dataTransfer.files[0];
            const fileInput = document.getElementById('medicine_image');
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            fileInput.files = dataTransfer.files;
            handleFileSelect({ target: { files: [file] } });
        }
    }

    function applyPreset(trade, active, strength, form, indications, sideEffects, precautions, img) {
        document.getElementById('trade_name').value = trade;
        document.getElementById('active_ingredient').value = active;
        document.getElementById('strength').value = strength;
        document.getElementById('dosageform').value = form;
        document.getElementById('indications').value = indications;
        document.getElementById('side_effects').value = sideEffects;
        document.getElementById('precautions').value = precautions;
        
        document.getElementById('image_url').value = img;
        document.getElementById('medicine_image').value = '';
        document.getElementById('remove_image').value = '0';
        document.getElementById('selectedFileName').innerHTML = '';

        if (img && img.trim() !== '') {
            document.getElementById('urlInputContainer').style.display = 'block';
            document.getElementById('btnToggleUrl').classList.add('active');
            setPreviewImage(img, 'รูปภาพจาก Preset');
            document.getElementById('btnRemoveImg').style.display = 'inline-flex';
        } else {
            clearPreviewUI();
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

