<?php
    include "../db_conn.php";
    $id = $_GET['id'];

    if(isset($_POST['submit'])){
        $name = $_POST['name'];
        $gender = $_POST['gender'];
        $date_of_birth = $_POST['date_of_birth'];
        $symptoms = $_POST['symptoms'];
        $causes = $_POST['causes'];
        $disease = $_POST['disease'];
        $medicine = $_POST['medicine'];

        $sql = "UPDATE `users` SET `name`='$name',`gender`='$gender',`date_of_birth`='$date_of_birth',`symptoms`='$symptoms',`causes`='$causes',`disease`='$disease',`medicine`='$medicine' WHERE user_id=$id";

        $result = mysqli_query($conn, $sql);
        if($result) {
            header("Location: user.php?msg=Data Updated successfully");
            exit();
        }
        else{
            echo "Failed: " . mysqli_error($conn);
        }
    }

    $sql = "SELECT * FROM `users` WHERE user_id = $id LIMIT 1";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลผู้ใช้ | Medisync</title>

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

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px 20px;
            border-bottom: 1px solid rgba(187, 215, 218, 0.15);
            margin-bottom: 20px;
        }

        .brand-logo-img {
            max-width: 175px;
            height: auto;
            max-height: 52px;
            object-fit: contain;
            display: block;
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
        }

        .sidebar-menu a i {
            width: 22px;
            text-align: center;
            font-size: 16px;
            color: var(--color-ice);
        }

        .sidebar-menu a:hover {
            background: rgba(78, 127, 144, 0.4);
            color: var(--color-white);
            transform: translateX(3px);
        }

        .sidebar-menu a.active {
            background: linear-gradient(90deg, #4E7F90 0%, #3d6a79 100%);
            color: var(--color-white);
            font-weight: 600;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Topbar */
        .topbar {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            min-height: 76px;
            padding: 14px 34px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 1px solid rgba(74, 128, 126, 0.18);
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .page-heading h5 {
            font-weight: 700;
            margin: 0;
            font-size: 19px;
            color: var(--color-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .breadcrumb-nav {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
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
            max-width: 960px;
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

        .user-id-badge {
            font-size: 12px;
            font-weight: 600;
            color: var(--color-secondary);
            background: rgba(78, 127, 144, 0.12);
            padding: 3px 10px;
            border-radius: 6px;
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
        }

        .btn-view-profile {
            background: rgba(144, 194, 183, 0.3);
            color: #175249;
            border: 1px solid rgba(144, 194, 183, 0.6);
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
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

        /* Inputs */
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

        /* Gender Pills */
        .gender-options-group {
            display: flex;
            gap: 12px;
        }

        .gender-radio-label {
            flex: 1;
            cursor: pointer;
            margin: 0;
        }

        .gender-radio-label input {
            display: none;
        }

        .gender-pill-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 12px;
            border: 1px solid rgba(74, 128, 126, 0.3);
            background: #FAFCFC;
            font-weight: 600;
            font-size: 13px;
            color: var(--color-primary);
            transition: all 0.2s ease;
        }

        .gender-radio-label input:checked + .gender-pill-box {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
            color: var(--color-white);
            border-color: var(--color-primary);
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
        }

        .btn-submit-save:hover {
            background: var(--color-secondary);
        }

        .btn-cancel-link {
            background: rgba(187, 215, 218, 0.35);
            color: var(--color-primary);
            border: 1px solid rgba(74, 128, 126, 0.3);
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 14px;
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
                z-index: 1040;
            }
            .main-content {
                margin-left: 0;
            }
            .mobile-menu-btn {
                display: inline-flex;
            }
        }
    </style>
</head>

<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <a href="../index.php" class="sidebar-brand">
        <img src="../assets/logo.svg" alt="Medisync Logo" class="brand-logo-img">
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
            <a href="user.php" class="active">
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
            <a href="../appointment-page/appointment.php">
                <i class="fa-solid fa-calendar-check"></i>
                <span>การนัดหมาย (Appointments)</span>
            </a>
        </li>
        <li>
            <a href="../medication-log-page/medication-log.php">
                <i class="fa-solid fa-notes-medical"></i>
                <span>ประวัติทานยา (Medication Logs)</span>
            </a>
        </li>
        <li>
            <a href="../schedule-page/schedule.php">
                <i class="fa-solid fa-clock"></i>
                <span>ตารางเวลาเตือน (Schedules)</span>
            </a>
        </li>
    </ul>
</aside>

<!-- Main Content Area -->
<main class="main-content">

    <!-- Topbar -->
    <header class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="mobile-menu-btn" id="mobileMenuBtn" type="button" aria-label="Toggle navigation">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="page-heading">
                <div class="breadcrumb-nav">
                    <a href="../index.php">หน้าหลัก</a>
                    <span>/</span>
                    <a href="user.php">รายชื่อผู้ใช้</a>
                    <span>/</span>
                    <span>แก้ไขข้อมูลผู้ใช้</span>
                </div>
                <h5>
                    <i class="fa-solid fa-user-pen" style="color: var(--color-teal);"></i>
                    แก้ไขข้อมูลประวัติผู้ป่วย
                </h5>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <div class="content-wrapper">

        <div class="form-card">

            <div class="form-header">
                <div>
                    <h3 style="font-size: 21px; font-weight: 700; color: var(--color-primary); margin: 0;">
                        <i class="fa-solid fa-pen-to-square" style="color: var(--color-secondary);"></i>
                        แก้ไขประวัติ: <?php echo htmlspecialchars($row['name'] ?? ''); ?>
                        <span class="user-id-badge">ID: #<?php echo $id; ?></span>
                    </h3>
                    <p style="color: var(--text-muted); margin: 4px 0 0; font-size: 13px;">แก้ไขข้อมูล หรือคลิกเลือกตัวเลือกแนะนำด้านล่าง</p>
                </div>

                <div class="d-flex gap-2">
                    <a href="user-details.php?id=<?php echo $id; ?>" class="btn-view-profile">
                        <i class="fa-solid fa-address-card"></i>
                        <span>ดูแฟ้มประวัติ</span>
                    </a>

                    <a href="user.php" class="btn-header-back">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>กลับหน้ารายชื่อ</span>
                    </a>
                </div>
            </div>

            <!-- Quick Clinical Presets -->
            <div class="preset-box">
                <div class="preset-box-title">
                    <i class="fa-solid fa-wand-magic-sparkles" style="color: var(--color-teal);"></i>
                    <span>ปรับเปลี่ยนตามชุดอาการ & โรคที่พบบ่อย (คลิกเพื่อเติมข้อมูลอัตโนมัติ):</span>
                </div>

                <div class="preset-buttons-wrap">
                    <button type="button" class="btn-preset-chip"
                            onclick="applyPreset('มีไข้, เจ็บคอ, ไอ', 'การติดเชื้อไวรัส', 'ไข้หวัดธรรมดา', 'Paracetamol')">
                        <i class="fa-solid fa-head-side-cough text-warning"></i>
                        <span>ไข้หวัดทั่วไป</span>
                    </button>

                    <button type="button" class="btn-preset-chip"
                            onclick="applyPreset('มีไข้สูง, ปวดเมื่อยกล้ามเนื้อ', 'การติดเชื้อไวรัส', 'ไข้หวัดใหญ่', 'Ibuprofen')">
                        <i class="fa-solid fa-thermometer text-danger"></i>
                        <span>ไข้หวัดใหญ่</span>
                    </button>

                    <button type="button" class="btn-preset-chip"
                            onclick="applyPreset('ปวดศีรษะ, คลื่นไส้', 'ความเครียด', 'โรคไมเกรน', 'Sumatriptan')">
                        <i class="fa-solid fa-brain text-info"></i>
                        <span>โรคไมเกรน</span>
                    </button>

                    <button type="button" class="btn-preset-chip"
                            onclick="applyPreset('เวียนศีรษะ', 'ความดันโลหิตสูง', 'โรคความดันโลหิตสูง', 'Amlodipine')">
                        <i class="fa-solid fa-heart-pulse text-danger"></i>
                        <span>ความดันโลหิตสูง</span>
                    </button>

                    <button type="button" class="btn-preset-chip"
                            onclick="applyPreset('คลื่นไส้, ปวดท้อง, ท้องร่วง', 'อาหารเป็นพิษ', 'โรคกระเพาะและลำไส้อักเสบ', 'Oral Rehydration')">
                        <i class="fa-solid fa-viruses text-success"></i>
                        <span>อาหารเป็นพิษ</span>
                    </button>

                    <button type="button" class="btn-preset-chip"
                            onclick="applyPreset('ผื่นแดง, คัน', 'โรคภูมิแพ้', 'ปฏิกิริยาภูมิแพ้', 'Antihistamine')">
                        <i class="fa-solid fa-hand-dots text-warning"></i>
                        <span>โรคภูมิแพ้</span>
                    </button>

                    <button type="button" class="btn-preset-chip"
                            onclick="applyPreset('หายใจไม่อิ่ม', 'มลพิษ-มลภาวะ', 'โรคหอบหืด', 'AlbuterolInhaler')">
                        <i class="fa-solid fa-lungs text-primary"></i>
                        <span>โรคหอบหืด</span>
                    </button>
                </div>
            </div>

            <!-- Form -->
            <form method="POST">

                <div class="row g-4">

                    <!-- Name -->
                    <div class="col-12 col-md-8">
                        <label class="form-group-label" for="name">
                            <i class="fa-solid fa-user me-1" style="color: var(--color-teal);"></i>
                            ชื่อผู้ใช้ / ชื่อผู้ป่วย
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               class="custom-input"
                               value="<?php echo htmlspecialchars($row['name'] ?? ''); ?>"
                               required>
                    </div>

                    <!-- Gender -->
                    <div class="col-12 col-md-4">
                        <label class="form-group-label">
                            <i class="fa-solid fa-venus-mars me-1" style="color: var(--color-teal);"></i>
                            เพศ
                        </label>
                        <div class="gender-options-group">
                            <label class="gender-radio-label">
                                <input type="radio" name="gender" value="ชาย" <?php echo (($row['gender'] ?? '') == 'ชาย') ? 'checked' : ''; ?>>
                                <div class="gender-pill-box">
                                    <i class="fa-solid fa-mars"></i>
                                    <span>ชาย</span>
                                </div>
                            </label>

                            <label class="gender-radio-label">
                                <input type="radio" name="gender" value="หญิง" <?php echo (($row['gender'] ?? '') == 'หญิง') ? 'checked' : ''; ?>>
                                <div class="gender-pill-box">
                                    <i class="fa-solid fa-venus"></i>
                                    <span>หญิง</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Date of birth -->
                    <div class="col-12 col-md-6">
                        <label class="form-group-label" for="date_of_birth">
                            <i class="fa-regular fa-calendar-days me-1" style="color: var(--color-teal);"></i>
                            วันเกิด
                        </label>
                        <input type="date"
                               id="date_of_birth"
                               name="date_of_birth"
                               class="custom-input"
                               value="<?php echo htmlspecialchars($row['date_of_birth'] ?? ''); ?>"
                               required>
                    </div>

                    <!-- Symptoms -->
                    <div class="col-12 col-md-6">
                        <label class="form-group-label" for="symptoms">
                            <i class="fa-solid fa-stethoscope me-1" style="color: var(--color-teal);"></i>
                            อาการ (Symptoms)
                        </label>
                        <input type="text"
                               id="symptoms"
                               name="symptoms"
                               class="custom-input"
                               value="<?php echo htmlspecialchars($row['symptoms'] ?? ''); ?>"
                               list="symptomsList">
                        <datalist id="symptomsList">
                            <option value="มีไข้">
                            <option value="ปวดศีรษะ">
                            <option value="ไอ">
                            <option value="เจ็บคอ">
                            <option value="คลื่นไส้">
                            <option value="เวียนศีรษะ">
                            <option value="ปวดท้อง">
                            <option value="ผื่นแดง">
                            <option value="หายใจไม่อิ่ม">
                            <option value="ปวดข้อ">
                        </datalist>

                        <div class="suggestions-wrap">
                            <span class="chip-suggest" onclick="setInputValue('symptoms', 'มีไข้')">มีไข้</span>
                            <span class="chip-suggest" onclick="setInputValue('symptoms', 'ปวดศีรษะ')">ปวดศีรษะ</span>
                            <span class="chip-suggest" onclick="setInputValue('symptoms', 'ไอ')">ไอ</span>
                            <span class="chip-suggest" onclick="setInputValue('symptoms', 'เจ็บคอ')">เจ็บคอ</span>
                            <span class="chip-suggest" onclick="setInputValue('symptoms', 'คลื่นไส้')">คลื่นไส้</span>
                            <span class="chip-suggest" onclick="setInputValue('symptoms', 'เวียนศีรษะ')">เวียนศีรษะ</span>
                            <span class="chip-suggest" onclick="setInputValue('symptoms', 'ผื่นแดง')">ผื่นแดง</span>
                            <span class="chip-suggest" onclick="setInputValue('symptoms', 'หายใจไม่อิ่ม')">หายใจไม่อิ่ม</span>
                        </div>
                    </div>

                    <!-- Causes -->
                    <div class="col-12 col-md-6">
                        <label class="form-group-label" for="causes">
                            <i class="fa-solid fa-circle-question me-1" style="color: var(--color-secondary);"></i>
                            สาเหตุ (Causes)
                        </label>
                        <input type="text"
                               id="causes"
                               name="causes"
                               class="custom-input"
                               value="<?php echo htmlspecialchars($row['causes'] ?? ''); ?>"
                               list="causesList">
                        <datalist id="causesList">
                            <option value="การติดเชื้อไวรัส">
                            <option value="การติดเชื้อแบคทีเรีย">
                            <option value="ความเครียด">
                            <option value="อาหารเป็นพิษ">
                            <option value="โรคภูมิแพ้">
                            <option value="ความดันโลหิตสูง">
                            <option value="มลพิษ-มลภาวะ">
                            <option value="พักผ่อนไม่เพียงพอ">
                        </datalist>

                        <div class="suggestions-wrap">
                            <span class="chip-suggest" onclick="setInputValue('causes', 'การติดเชื้อไวรัส')">การติดเชื้อไวรัส</span>
                            <span class="chip-suggest" onclick="setInputValue('causes', 'การติดเชื้อแบคทีเรีย')">การติดเชื้อแบคทีเรีย</span>
                            <span class="chip-suggest" onclick="setInputValue('causes', 'ความเครียด')">ความเครียด</span>
                            <span class="chip-suggest" onclick="setInputValue('causes', 'อาหารเป็นพิษ')">อาหารเป็นพิษ</span>
                            <span class="chip-suggest" onclick="setInputValue('causes', 'โรคภูมิแพ้')">โรคภูมิแพ้</span>
                            <span class="chip-suggest" onclick="setInputValue('causes', 'ความดันโลหิตสูง')">ความดันโลหิตสูง</span>
                        </div>
                    </div>

                    <!-- Disease -->
                    <div class="col-12 col-md-6">
                        <label class="form-group-label" for="disease">
                            <i class="fa-solid fa-heart-pulse me-1" style="color: #c82333;"></i>
                            โรคที่วินิจฉัย (Disease)
                        </label>
                        <input type="text"
                               id="disease"
                               name="disease"
                               class="custom-input"
                               value="<?php echo htmlspecialchars($row['disease'] ?? ''); ?>"
                               list="diseasesList">
                        <datalist id="diseasesList">
                            <option value="ไข้หวัดธรรมดา">
                            <option value="ไข้หวัดใหญ่">
                            <option value="โรคไมเกรน">
                            <option value="โรคกระเพาะและลำไส้อักเสบ">
                            <option value="โรคหอบหืด">
                            <option value="โรคความดันโลหิตสูง">
                            <option value="ปฏิกิริยาภูมิแพ้">
                            <option value="โรคคออักเสบจากเชื้อสเตรป">
                        </datalist>

                        <div class="suggestions-wrap">
                            <span class="chip-suggest" onclick="setInputValue('disease', 'ไข้หวัดธรรมดา')">ไข้หวัดธรรมดา</span>
                            <span class="chip-suggest" onclick="setInputValue('disease', 'ไข้หวัดใหญ่')">ไข้หวัดใหญ่</span>
                            <span class="chip-suggest" onclick="setInputValue('disease', 'โรคไมเกรน')">โรคไมเกรน</span>
                            <span class="chip-suggest" onclick="setInputValue('disease', 'โรคกระเพาะและลำไส้อักเสบ')">โรคกระเพาะและลำไส้อักเสบ</span>
                            <span class="chip-suggest" onclick="setInputValue('disease', 'โรคหอบหืด')">โรคหอบหืด</span>
                            <span class="chip-suggest" onclick="setInputValue('disease', 'โรคความดันโลหิตสูง')">โรคความดันโลหิตสูง</span>
                        </div>
                    </div>

                    <!-- Medicine -->
                    <div class="col-12">
                        <label class="form-group-label" for="medicine">
                            <i class="fa-solid fa-capsules me-1" style="color: var(--color-teal);"></i>
                            ยาที่แนะนำ (Medicine)
                        </label>
                        <input type="text"
                               id="medicine"
                               name="medicine"
                               class="custom-input"
                               value="<?php echo htmlspecialchars($row['medicine'] ?? ''); ?>"
                               list="medicinesList">
                        <datalist id="medicinesList">
                            <option value="Paracetamol">
                            <option value="Ibuprofen">
                            <option value="Amoxicillin">
                            <option value="Amlodipine">
                            <option value="Antacids">
                            <option value="Antihistamine">
                            <option value="Sumatriptan">
                            <option value="AlbuterolInhaler">
                            <option value="Oral Rehydration">
                        </datalist>

                        <div class="suggestions-wrap">
                            <span class="chip-suggest" onclick="setInputValue('medicine', 'Paracetamol')"><i class="fa-solid fa-pills me-1"></i>Paracetamol</span>
                            <span class="chip-suggest" onclick="setInputValue('medicine', 'Ibuprofen')"><i class="fa-solid fa-pills me-1"></i>Ibuprofen</span>
                            <span class="chip-suggest" onclick="setInputValue('medicine', 'Amoxicillin')"><i class="fa-solid fa-pills me-1"></i>Amoxicillin</span>
                            <span class="chip-suggest" onclick="setInputValue('medicine', 'Amlodipine')"><i class="fa-solid fa-pills me-1"></i>Amlodipine</span>
                            <span class="chip-suggest" onclick="setInputValue('medicine', 'Antacids')"><i class="fa-solid fa-pills me-1"></i>Antacids</span>
                            <span class="chip-suggest" onclick="setInputValue('medicine', 'Antihistamine')"><i class="fa-solid fa-pills me-1"></i>Antihistamine</span>
                            <span class="chip-suggest" onclick="setInputValue('medicine', 'Sumatriptan')"><i class="fa-solid fa-pills me-1"></i>Sumatriptan</span>
                            <span class="chip-suggest" onclick="setInputValue('medicine', 'AlbuterolInhaler')"><i class="fa-solid fa-pills me-1"></i>AlbuterolInhaler</span>
                            <span class="chip-suggest" onclick="setInputValue('medicine', 'Oral Rehydration')"><i class="fa-solid fa-pills me-1"></i>Oral Rehydration</span>
                        </div>
                    </div>

                </div>

                <div class="form-actions">
                    <a href="user.php" class="btn-cancel-link">
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
    function setInputValue(inputId, val) {
        const input = document.getElementById(inputId);
        if (input) {
            input.value = val;
            input.focus();
        }
    }

    function applyPreset(symptoms, causes, disease, medicine) {
        document.getElementById('symptoms').value = symptoms;
        document.getElementById('causes').value = causes;
        document.getElementById('disease').value = disease;
        document.getElementById('medicine').value = medicine;
    }

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
