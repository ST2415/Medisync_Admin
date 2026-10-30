<?php
$pageTitle = "User Details";
require_once '../db_conn.php';

// ฟังก์ชัน Escape HTML เพื่อความปลอดภัย
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// ฟังก์ชันคำนวณอายุจากวันเกิด
function calculateAge($dob) {
    if (!$dob || $dob === '0000-00-00') {
        return '-';
    }
    try {
        $birthDate = new DateTime($dob);
        $today = new DateTime('today');
        return $birthDate->diff($today)->y . ' ปี';
    } catch (Exception $ex) {
        return '-';
    }
}

// รับค่า ID ของผู้ใช้
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$activeTab = isset($_GET['tab']) && in_array($_GET['tab'], ['schedules', 'logs'], true) ? $_GET['tab'] : 'schedules';

// ตรวจสอบและดึงข้อมูลผู้ใช้
$user = null;
if ($userId > 0) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $user = $res->fetch_assoc();
    }
    $stmt->close();
}

// ตัวกรองสำหรับแท็บ Schedules
$schedSearch = trim($_GET['sched_search'] ?? '');
$schedTime = trim($_GET['sched_time'] ?? '');

// ตัวกรองสำหรับแท็บ Medication Logs
$logSearch = trim($_GET['log_search'] ?? '');
$logStatus = trim($_GET['log_status'] ?? '');
$logTime = trim($_GET['log_time'] ?? '');
$logDateFrom = trim($_GET['log_date_from'] ?? '');
$logDateTo = trim($_GET['log_date_to'] ?? '');

$schedules = [];
$medicationLogs = [];
$totalSchedulesCount = 0;
$totalLogsCount = 0;
$countTaken = 0;
$countMissed = 0;
$countNotTaken = 0;

if ($user) {
    // 1. ดึงสถิติภาพรวมของผู้ใช้คนนี้
    // สถิติจำนวน Schedules ทั้งหมดของผู้ใช้นี้
    $statStmt = $conn->prepare("SELECT COUNT(*) AS total FROM schedules WHERE user_id = ?");
    $statStmt->bind_param("i", $userId);
    $statStmt->execute();
    $totalSchedulesCount = (int)$statStmt->get_result()->fetch_assoc()['total'];
    $statStmt->close();

    // สถิติจำนวน Logs ทั้งหมดและแยกตามสถานะ
    $logStatSql = "
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN ml.status = 'กินแล้ว' THEN 1 ELSE 0 END) AS taken,
            SUM(CASE WHEN ml.status = 'ลืมกินยา' THEN 1 ELSE 0 END) AS missed,
            SUM(CASE WHEN ml.status = 'ยังไม่ได้กิน' THEN 1 ELSE 0 END) AS not_taken
        FROM medication_log ml
        INNER JOIN schedules s ON ml.schedule_id = s.schedule_id
        WHERE s.user_id = ?
    ";
    $logStatStmt = $conn->prepare($logStatSql);
    $logStatStmt->bind_param("i", $userId);
    $logStatStmt->execute();
    $statRow = $logStatStmt->get_result()->fetch_assoc();
    if ($statRow) {
        $totalLogsCount = (int)($statRow['total'] ?? 0);
        $countTaken = (int)($statRow['taken'] ?? 0);
        $countMissed = (int)($statRow['missed'] ?? 0);
        $countNotTaken = (int)($statRow['not_taken'] ?? 0);
    }
    $logStatStmt->close();

    // 2. ดึงข้อมูล Schedules (พร้อมตัวกรอง)
    $schedSql = "
        SELECT 
            s.schedule_id,
            s.user_id,
            s.tpu_code,
            s.time_to_take,
            s.dosage_amount,
            m.trade_name,
            m.active_ingredient,
            m.strength,
            m.dosageform,
            m.indications,
            m.side_effects,
            m.precautions,
            m.image_url
        FROM schedules s
        LEFT JOIN medicines m ON s.tpu_code = m.tpu_code
        WHERE s.user_id = ?
    ";
    $schedParams = [$userId];
    $schedTypes = "i";

    if ($schedSearch !== '') {
        $schedSql .= " AND (m.trade_name LIKE ? OR m.active_ingredient LIKE ? OR CAST(s.tpu_code AS CHAR) LIKE ? OR s.schedule_id LIKE ?)";
        $kw = "%{$schedSearch}%";
        $schedParams[] = $kw;
        $schedParams[] = $kw;
        $schedParams[] = $kw;
        $schedParams[] = $kw;
        $schedTypes .= "ssss";
    }

    if ($schedTime !== '' && $schedTime !== 'all') {
        $schedSql .= " AND s.time_to_take = ?";
        $schedParams[] = $schedTime;
        $schedTypes .= "s";
    }

    $schedSql .= " ORDER BY FIELD(s.time_to_take, 'เช้า', 'กลางวัน', 'เย็น', 'ก่อนนอน'), s.schedule_id ASC";
    $schedStmt = $conn->prepare($schedSql);
    if (!empty($schedParams)) {
        $schedStmt->bind_param($schedTypes, ...$schedParams);
    }
    $schedStmt->execute();
    $schedRes = $schedStmt->get_result();
    while ($r = $schedRes->fetch_assoc()) {
        $schedules[] = $r;
    }
    $schedStmt->close();

    // 3. ดึงข้อมูล Medication Logs (พร้อมตัวกรอง)
    $logSql = "
        SELECT 
            ml.log_id,
            ml.schedule_id,
            ml.action_time,
            ml.status,
            ml.side_effect,
            s.time_to_take,
            s.dosage_amount,
            m.trade_name,
            m.active_ingredient,
            m.strength,
            m.dosageform,
            m.image_url
        FROM medication_log ml
        INNER JOIN schedules s ON ml.schedule_id = s.schedule_id
        LEFT JOIN medicines m ON s.tpu_code = m.tpu_code
        WHERE s.user_id = ?
    ";
    $logParams = [$userId];
    $logTypes = "i";

    if ($logSearch !== '') {
        $logSql .= " AND (m.trade_name LIKE ? OR m.active_ingredient LIKE ? OR ml.side_effect LIKE ? OR ml.log_id LIKE ?)";
        $lkw = "%{$logSearch}%";
        $logParams[] = $lkw;
        $logParams[] = $lkw;
        $logParams[] = $lkw;
        $logParams[] = $lkw;
        $logTypes .= "ssss";
    }

    if ($logStatus !== '' && $logStatus !== 'all') {
        $logSql .= " AND ml.status = ?";
        $logParams[] = $logStatus;
        $logTypes .= "s";
    }

    if ($logTime !== '' && $logTime !== 'all') {
        $logSql .= " AND s.time_to_take = ?";
        $logParams[] = $logTime;
        $logTypes .= "s";
    }

    if ($logDateFrom !== '') {
        $logSql .= " AND DATE(ml.action_time) >= ?";
        $logParams[] = $logDateFrom;
        $logTypes .= "s";
    }

    if ($logDateTo !== '') {
        $logSql .= " AND DATE(ml.action_time) <= ?";
        $logParams[] = $logDateTo;
        $logTypes .= "s";
    }

    $logSql .= " ORDER BY ml.action_time DESC";
    $logStmt = $conn->prepare($logSql);
    if (!empty($logParams)) {
        $logStmt->bind_param($logTypes, ...$logParams);
    }
    $logStmt->execute();
    $logRes = $logStmt->get_result();
    while ($lr = $logRes->fetch_assoc()) {
        $medicationLogs[] = $lr;
    }
    $logStmt->close();
}

// อัตราการทานยาตามกำหนด (Adherence Rate)
$adherenceRate = ($totalLogsCount > 0) ? round(($countTaken / $totalLogsCount) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $user ? e($user['name']) . ' - รายละเอียดผู้ป่วย' : 'ไม่พบข้อมูลผู้ป่วย' ?> | Medisync</title>

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
            /* ชุดสีตามที่ผู้ใช้กำหนด */
            --color-primary: #294549;       /* Deep Slate Pine */
            --color-secondary: #4E7F90;     /* Steel Teal */
            --color-teal: #4A807E;          /* Sage Teal */
            --color-mint: #90C2B7;          /* Fresh Mint */
            --color-ice: #BBD7DA;           /* Ice Pastel Cyan */
            --color-white: #FFFFFF;
            --color-black: #000000;

            /* สีพื้นหลังและตัวอักษร */
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
            -webkit-font-smoothing: antialiased;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* ===== SIDEBAR ===== */
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

        .brand-fallback {
            display: flex;
            align-items: center;
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

        .sidebar-footer-card {
            background: rgba(78, 127, 144, 0.25);
            border: 1px solid rgba(187, 215, 218, 0.2);
            border-radius: 14px;
            padding: 14px 16px;
            margin-top: 20px;
        }

        .system-status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: var(--color-mint);
            font-weight: 500;
            margin-bottom: 6px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: var(--color-mint);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--color-mint);
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.85); }
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
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
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
            box-shadow: 0 2px 12px rgba(41, 69, 73, 0.04);
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

        .page-heading p {
            margin: 3px 0 0;
            color: var(--text-muted);
            font-size: 12px;
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

        .breadcrumb-nav a:hover {
            color: var(--color-primary);
            text-decoration: underline;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 14px 6px 8px;
            background: #FFFFFF;
            border: 1px solid var(--card-border);
            border-radius: 40px;
            box-shadow: 0 2px 8px rgba(41, 69, 73, 0.05);
        }

        .admin-avatar {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-teal) 100%);
            color: var(--color-white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 2px 6px rgba(41, 69, 73, 0.2);
        }

        .admin-info strong {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--color-primary);
            line-height: 1.2;
        }

        .admin-info small {
            color: var(--text-muted);
            font-size: 11px;
        }

        /* ===== CONTENT WRAPPER ===== */
        .content-wrapper {
            padding: 26px 34px 40px;
            flex-grow: 1;
        }

        /* ===== PATIENT PROFILE HERO CARD ===== */
        .patient-hero-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 28px 30px;
            margin-bottom: 24px;
            box-shadow: 0 8px 26px rgba(41, 69, 73, 0.07);
            position: relative;
            overflow: hidden;
        }

        .patient-hero-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--color-primary) 0%, var(--color-secondary) 50%, var(--color-mint) 100%);
        }

        .patient-hero-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 22px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(74, 128, 126, 0.14);
        }

        .patient-identity {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .patient-avatar-lg {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            box-shadow: 0 4px 14px rgba(41, 69, 73, 0.12);
            flex-shrink: 0;
        }

        .patient-avatar-male {
            background: linear-gradient(135deg, rgba(78, 127, 144, 0.25) 0%, rgba(41, 69, 73, 0.15) 100%);
            color: var(--color-primary);
            border: 2px solid rgba(78, 127, 144, 0.4);
        }

        .patient-avatar-female {
            background: linear-gradient(135deg, rgba(144, 194, 183, 0.35) 0%, rgba(74, 128, 126, 0.2) 100%);
            color: #175249;
            border: 2px solid rgba(144, 194, 183, 0.5);
        }

        .patient-names h3 {
            font-size: 22px;
            font-weight: 700;
            color: var(--color-primary);
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .patient-id-badge {
            font-size: 12px;
            font-weight: 600;
            color: var(--color-secondary);
            background: rgba(78, 127, 144, 0.12);
            padding: 3px 10px;
            border-radius: 6px;
        }

        .patient-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .chip-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .chip-gender-male {
            background: rgba(78, 127, 144, 0.14);
            color: #294549;
        }

        .chip-gender-female {
            background: rgba(144, 194, 183, 0.3);
            color: #175249;
        }

        .chip-neutral {
            background: rgba(187, 215, 218, 0.35);
            color: var(--color-primary);
        }

        .hero-action-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-back {
            background: rgba(78, 127, 144, 0.12);
            color: var(--color-primary);
            border: 1px solid rgba(78, 127, 144, 0.3);
            font-weight: 600;
            padding: 9px 16px;
            border-radius: 12px;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-back:hover {
            background: var(--color-primary);
            color: var(--color-white);
            transform: translateX(-2px);
        }

        .btn-edit-user {
            background: var(--color-primary);
            color: var(--color-white);
            font-weight: 600;
            padding: 9px 18px;
            border-radius: 12px;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(41, 69, 73, 0.2);
        }

        .btn-edit-user:hover {
            background: var(--color-secondary);
            color: var(--color-white);
            transform: translateY(-2px);
        }

        /* Clinical Info Grid */
        .clinical-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }

        .clinical-box {
            background: #FAFCFC;
            border: 1px solid rgba(74, 128, 126, 0.16);
            border-radius: 14px;
            padding: 14px 16px;
            transition: all 0.2s ease;
        }

        .clinical-box:hover {
            background: #FFFFFF;
            border-color: var(--color-teal);
            box-shadow: 0 4px 12px rgba(41, 69, 73, 0.05);
        }

        .clinical-box-title {
            font-size: 11.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-muted);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .clinical-box-value {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--color-primary);
            line-height: 1.4;
        }

        .clinical-pill-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, rgba(144, 194, 183, 0.3) 0%, rgba(187, 215, 218, 0.4) 100%);
            color: var(--color-primary);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 600;
            border: 1px solid rgba(144, 194, 183, 0.6);
        }

        /* ===== STAT COUNTERS BAR ===== */
        .stat-mini-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 16px 20px;
            height: 100%;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 4px 16px rgba(41, 69, 73, 0.05);
            transition: transform 0.2s ease;
        }

        .stat-mini-card:hover {
            transform: translateY(-3px);
        }

        .stat-mini-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .stat-mini-info h6 {
            font-size: 12px;
            color: var(--text-muted);
            margin: 0 0 3px 0;
        }

        .stat-mini-info strong {
            font-size: 22px;
            font-weight: 700;
            color: var(--color-primary);
            line-height: 1;
        }

        /* ===== TAB NAVIGATION ===== */
        .tabs-container {
            margin-top: 24px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 6px 22px rgba(41, 69, 73, 0.06);
        }

        .custom-tab-nav {
            display: flex;
            gap: 10px;
            border-bottom: 2px solid rgba(74, 128, 126, 0.15);
            padding-bottom: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .tab-btn-pill {
            padding: 11px 22px;
            border-radius: 14px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--color-primary);
            background: rgba(187, 215, 218, 0.25);
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            transition: all 0.22s ease;
            cursor: pointer;
        }

        .tab-btn-pill:hover {
            background: rgba(78, 127, 144, 0.18);
            color: var(--color-primary);
        }

        .tab-btn-pill.active {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
            color: var(--color-white);
            box-shadow: 0 4px 14px rgba(41, 69, 73, 0.25);
        }

        .tab-btn-pill.active i {
            color: var(--color-mint);
        }

        .tab-count-badge {
            background: rgba(255, 255, 255, 0.25);
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .tab-btn-pill:not(.active) .tab-count-badge {
            background: rgba(78, 127, 144, 0.15);
            color: var(--color-primary);
        }

        /* ===== FILTER FORMS ===== */
        .filter-toolbar {
            background: #F8FAFA;
            border: 1px solid rgba(74, 128, 126, 0.18);
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 22px;
        }

        .filter-row {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filter-col {
            flex-grow: 1;
            min-width: 170px;
        }

        .filter-col-sm {
            flex-grow: 0;
            min-width: 140px;
        }

        .filter-label {
            display: block;
            font-size: 11.5px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 5px;
        }

        .filter-input {
            width: 100%;
            border: 1px solid rgba(74, 128, 126, 0.3);
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 12.5px;
            font-family: inherit;
            color: var(--color-primary);
            background: #FFFFFF;
            transition: all 0.2s ease;
        }

        .filter-input:focus {
            outline: none;
            border-color: var(--color-secondary);
            box-shadow: 0 0 0 3px rgba(78, 127, 144, 0.16);
        }

        .btn-filter-apply {
            background: var(--color-primary);
            color: var(--color-white);
            border: none;
            border-radius: 10px;
            padding: 9px 18px;
            font-size: 12.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-filter-apply:hover {
            background: var(--color-secondary);
        }

        .btn-filter-reset {
            background: rgba(187, 215, 218, 0.35);
            color: var(--color-primary);
            border: 1px solid rgba(74, 128, 126, 0.25);
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 12.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-filter-reset:hover {
            background: var(--color-primary);
            color: var(--color-white);
        }

        /* ===== SCHEDULES CARDS GRID ===== */
        .schedule-card {
            background: #FFFFFF;
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 20px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 4px 16px rgba(41, 69, 73, 0.05);
            transition: all 0.22s ease;
            position: relative;
            overflow: hidden;
        }

        .schedule-card:hover {
            transform: translateY(-4px);
            border-color: var(--color-teal);
            box-shadow: 0 10px 24px rgba(41, 69, 73, 0.1);
        }

        .schedule-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
        }

        .time-pill-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .time-morning {
            background: #FFF3CD;
            color: #856404;
            border: 1px solid #FFEEBA;
        }

        .time-afternoon {
            background: #D1ECF1;
            color: #0C5460;
            border: 1px solid #BEE5EB;
        }

        .time-evening {
            background: rgba(78, 127, 144, 0.2);
            color: #294549;
            border: 1px solid rgba(78, 127, 144, 0.35);
        }

        .time-bedtime {
            background: rgba(41, 69, 73, 0.15);
            color: #173034;
            border: 1px solid rgba(41, 69, 73, 0.25);
        }

        .dosage-badge {
            background: rgba(144, 194, 183, 0.28);
            color: #154d44;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 11.5px;
        }

        .med-trade-name {
            font-size: 17px;
            font-weight: 700;
            color: var(--color-primary);
            margin: 0 0 4px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .med-active-ingredient {
            font-size: 12.5px;
            color: var(--color-secondary);
            font-weight: 500;
            margin-bottom: 12px;
            display: block;
        }

        .med-details-list {
            list-style: none;
            padding: 0;
            margin: 0 0 14px 0;
            font-size: 12px;
        }

        .med-details-list li {
            margin-bottom: 6px;
            display: flex;
            gap: 8px;
            line-height: 1.4;
        }

        .med-details-list li strong {
            color: var(--text-muted);
            min-width: 65px;
            flex-shrink: 0;
        }

        .med-details-list li span {
            color: var(--color-primary);
        }

        .schedule-card-footer {
            border-top: 1px solid rgba(74, 128, 126, 0.12);
            padding-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: var(--text-muted);
        }

        /* ===== LOGS TABLE ===== */
        .custom-table {
            width: 100%;
            margin-bottom: 0;
            vertical-align: middle;
            font-size: 12.5px;
            border-collapse: separate;
            border-spacing: 0;
        }

        .custom-table thead th {
            background: #eef5f5;
            color: var(--color-primary);
            font-weight: 600;
            padding: 13px 14px;
            border-top: none;
            border-bottom: 1px solid rgba(74, 128, 126, 0.2);
            white-space: nowrap;
        }

        .custom-table thead th:first-child {
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }

        .custom-table thead th:last-child {
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        .custom-table tbody tr {
            transition: background 0.16s ease;
        }

        .custom-table tbody tr:hover {
            background: rgba(187, 215, 218, 0.25);
        }

        .custom-table tbody td {
            padding: 13px 14px;
            border-bottom: 1px solid rgba(74, 128, 126, 0.1);
            color: var(--color-primary);
        }

        /* Log Status Badges */
        .log-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .log-status-taken {
            background: rgba(144, 194, 183, 0.35);
            color: #144e45;
            border: 1px solid rgba(144, 194, 183, 0.6);
        }

        .log-status-missed {
            background: rgba(220, 53, 69, 0.12);
            color: #bd2130;
            border: 1px solid rgba(220, 53, 69, 0.25);
        }

        .log-status-pending {
            background: rgba(78, 127, 144, 0.15);
            color: #294549;
            border: 1px solid rgba(78, 127, 144, 0.3);
        }

        /* Empty State */
        .empty-box {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-muted);
        }

        .empty-box i {
            font-size: 44px;
            color: var(--color-secondary);
            opacity: 0.5;
            margin-bottom: 14px;
        }

        .empty-box h6 {
            font-weight: 700;
            color: var(--color-primary);
            font-size: 16px;
            margin-bottom: 6px;
        }

        /* ===== FOOTER ===== */
        .footer {
            text-align: center;
            padding: 24px;
            color: var(--text-muted);
            font-size: 12px;
            border-top: 1px solid rgba(74, 128, 126, 0.15);
            background: rgba(255, 255, 255, 0.4);
            margin-top: auto;
        }

        /* Mobile Overlay */
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

            .topbar {
                padding: 12px 18px;
            }

            .content-wrapper {
                padding: 20px 18px;
            }
        }
    </style>
</head>

<body>

<!-- Mobile Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">

    <!-- Brand -->
    <a href="../index.php" class="sidebar-brand">
        <img src="../assets/logo.svg"
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

    <!-- Sidebar Menu -->
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
    </ul>

    <!-- Sidebar Status Footer -->
    <div class="sidebar-footer-card">
        <div class="system-status-indicator">
            <span class="status-dot"></span>
            <span>ระบบออนไลน์พร้อมใช้งาน</span>
        </div>
        <div style="font-size: 11px; color: var(--color-ice); line-height: 1.5;">
            Medisync Patient Overview<br>
            <span style="opacity: 0.8;">แฟ้มประวัติสุขภาพผู้ป่วย</span>
        </div>
    </div>

</aside>

<!-- Main Content Area -->
<main class="main-content">

    <!-- Topbar -->
    <header class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button
                class="mobile-menu-btn"
                id="mobileMenuBtn"
                type="button"
                aria-label="Toggle navigation"
            >
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="page-heading">
                <div class="breadcrumb-nav">
                    <a href="../index.php">หน้าหลัก</a>
                    <span>/</span>
                    <a href="user.php">รายชื่อผู้ใช้</a>
                    <span>/</span>
                    <span>รายละเอียดผู้ป่วย</span>
                </div>
                <h5>
                    <i class="fa-solid fa-id-card" style="color: var(--color-teal);"></i>
                    รายละเอียดผู้ป่วย & แผนการรักษา
                </h5>
            </div>
        </div>

        <div class="admin-profile">
            <div class="admin-avatar">
                <i class="fa-solid fa-user-doctor"></i>
            </div>
            <div class="admin-info">
                <strong>ผู้ดูแลระบบ</strong>
                <small>System Admin</small>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <div class="content-wrapper">

        <?php if (!$user): ?>
            <!-- กรณีไม่พบข้อมูลผู้ใช้ -->
            <div class="patient-hero-card text-center py-5">
                <i class="fa-solid fa-triangle-exclamation text-warning fs-1 mb-3"></i>
                <h4 style="color: var(--color-primary); font-weight: 700;">ไม่พบข้อมูลผู้ใช้ที่ระบุ</h4>
                <p class="text-muted mb-4">ไม่พบรหัสผู้ใช้ในระบบ หรือรหัสอาจไม่ถูกต้อง</p>
                <a href="user.php" class="btn-back">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>กลับไปหน้ารายชื่อผู้ใช้</span>
                </a>
            </div>
        <?php else: ?>
            <?php
            $isMale = ($user['gender'] === 'ชาย');
            $avatarClass = $isMale ? 'patient-avatar-male' : 'patient-avatar-female';
            $genderChip = $isMale ? 'chip-gender-male' : 'chip-gender-female';
            $genderIcon = $isMale ? 'fa-mars' : 'fa-venus';
            $age = calculateAge($user['date_of_birth']);
            ?>

            <!-- Patient Profile Hero Card -->
            <section class="patient-hero-card">
                <div class="patient-hero-top">
                    <!-- Identity Left -->
                    <div class="patient-identity">
                        <div class="patient-avatar-lg <?= $avatarClass ?>">
                            <i class="fa-solid <?= $isMale ? 'fa-user' : 'fa-user-nurse' ?>"></i>
                        </div>
                        <div class="patient-names">
                            <h3>
                                <?= e($user['name']) ?>
                                <span class="patient-id-badge">ID: #<?= e($user['user_id']) ?></span>
                            </h3>
                            <div class="patient-chips">
                                <span class="chip-badge <?= $genderChip ?>">
                                    <i class="fa-solid <?= $genderIcon ?>"></i>
                                    <?= e($user['gender']) ?>
                                </span>
                                <span class="chip-badge chip-neutral">
                                    <i class="fa-regular fa-calendar me-1"></i>
                                    วันเกิด: <?= e($user['date_of_birth']) ?>
                                </span>
                                <span class="chip-badge chip-neutral">
                                    <i class="fa-solid fa-hourglass-half me-1"></i>
                                    อายุ: <?= e($age) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Right -->
                    <div class="hero-action-group">
                        <a href="user.php" class="btn-back">
                            <i class="fa-solid fa-arrow-left"></i>
                            <span>กลับหน้ารายชื่อ</span>
                        </a>
                        <a href="edit-user.php?id=<?= urlencode($user['user_id']) ?>" class="btn-edit-user">
                            <i class="fa-solid fa-pen-to-square"></i>
                            <span>แก้ไขประวัติ</span>
                        </a>
                    </div>
                </div>

                <!-- Clinical & Diagnostic Data Grid -->
                <div class="clinical-info-grid">
                    <div class="clinical-box">
                        <div class="clinical-box-title">
                            <i class="fa-solid fa-stethoscope" style="color: var(--color-teal);"></i>
                            <span>อาการแสดง (Symptoms)</span>
                        </div>
                        <div class="clinical-box-value">
                            <?= !empty($user['symptoms']) ? e($user['symptoms']) : '<span class="text-muted">-</span>' ?>
                        </div>
                    </div>

                    <div class="clinical-box">
                        <div class="clinical-box-title">
                            <i class="fa-solid fa-circle-question" style="color: var(--color-secondary);"></i>
                            <span>สาเหตุ (Causes)</span>
                        </div>
                        <div class="clinical-box-value">
                            <?= !empty($user['causes']) ? e($user['causes']) : '<span class="text-muted">-</span>' ?>
                        </div>
                    </div>

                    <div class="clinical-box">
                        <div class="clinical-box-title">
                            <i class="fa-solid fa-heart-pulse" style="color: #c82333;"></i>
                            <span>โรคที่วินิจฉัย (Disease)</span>
                        </div>
                        <div class="clinical-box-value" style="color: #c82333;">
                            <?= !empty($user['disease']) ? e($user['disease']) : '<span class="text-muted">-</span>' ?>
                        </div>
                    </div>

                    <div class="clinical-box">
                        <div class="clinical-box-title">
                            <i class="fa-solid fa-capsules" style="color: var(--color-teal);"></i>
                            <span>ยาที่แนะนำ (Medicine)</span>
                        </div>
                        <div class="clinical-box-value">
                            <?php if (!empty($user['medicine'])): ?>
                                <span class="clinical-pill-tag">
                                    <i class="fa-solid fa-prescription-bottle-medical"></i>
                                    <?= e($user['medicine']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stat Counters Bar for This User -->
            <section class="row g-3 mb-2">
                <!-- Schedules Count -->
                <div class="col-6 col-md-3">
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon" style="background: rgba(78, 127, 144, 0.14); color: #4E7F90;">
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h6>ตารางยาที่ต้องกิน</h6>
                            <strong><?= number_format($totalSchedulesCount) ?></strong> รายการ
                        </div>
                    </div>
                </div>

                <!-- Total Logs -->
                <div class="col-6 col-md-3">
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon" style="background: rgba(41, 69, 73, 0.12); color: #294549;">
                            <i class="fa-solid fa-clipboard-check"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h6>ประวัติบันทึกทั้งหมด</h6>
                            <strong><?= number_format($totalLogsCount) ?></strong> ครั้ง
                        </div>
                    </div>
                </div>

                <!-- Taken Count -->
                <div class="col-6 col-md-3">
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon" style="background: rgba(144, 194, 183, 0.35); color: #175249;">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h6>กินยาแล้ว</h6>
                            <strong><?= number_format($countTaken) ?></strong> ครั้ง
                        </div>
                    </div>
                </div>

                <!-- Adherence Rate -->
                <div class="col-6 col-md-3">
                    <div class="stat-mini-card">
                        <div class="stat-mini-icon" style="background: rgba(74, 128, 126, 0.15); color: #4A807E;">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div class="stat-mini-info">
                            <h6>ความสม่ำเสมอ</h6>
                            <strong><?= $adherenceRate ?>%</strong>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Tabs Section: Schedules & Medication Log -->
            <section class="tabs-container">
                <!-- Tab Buttons Navigation -->
                <div class="custom-tab-nav">
                    <a href="?id=<?= urlencode($userId) ?>&tab=schedules"
                       class="tab-btn-pill <?= $activeTab === 'schedules' ? 'active' : '' ?>">
                        <i class="fa-solid fa-pills"></i>
                        <span>รายการยาที่ต้องกิน (Schedules)</span>
                        <span class="tab-count-badge"><?= count($schedules) ?></span>
                    </a>

                    <a href="?id=<?= urlencode($userId) ?>&tab=logs"
                       class="tab-btn-pill <?= $activeTab === 'logs' ? 'active' : '' ?>">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>ประวัติการกินยาทั้งหมด (Medication Log)</span>
                        <span class="tab-count-badge"><?= count($medicationLogs) ?></span>
                    </a>
                </div>

                <!-- TAB 1: SCHEDULES CONTENT -->
                <?php if ($activeTab === 'schedules'): ?>
                    <div>
                        <!-- Filter Toolbar for Schedules -->
                        <div class="filter-toolbar">
                            <form method="GET" action="user-details.php" class="filter-row">
                                <input type="hidden" name="id" value="<?= e($userId) ?>">
                                <input type="hidden" name="tab" value="schedules">

                                <!-- Search by keyword -->
                                <div class="filter-col">
                                    <label class="filter-label">
                                        <i class="fa-solid fa-magnifying-glass me-1"></i> ค้นหาชื่อยา / สารสำคัญ
                                    </label>
                                    <input type="text"
                                           name="sched_search"
                                           class="filter-input"
                                           placeholder="เช่น ALGYCON, COPLAVIX..."
                                           value="<?= e($schedSearch) ?>">
                                </div>

                                <!-- Filter by Time to take -->
                                <div class="filter-col-sm">
                                    <label class="filter-label">
                                        <i class="fa-solid fa-clock me-1"></i> ช่วงเวลากินยา
                                    </label>
                                    <select name="sched_time" class="filter-input">
                                        <option value="all" <?= $schedTime === 'all' || $schedTime === '' ? 'selected' : '' ?>>ทุกช่วงเวลา</option>
                                        <option value="เช้า" <?= $schedTime === 'เช้า' ? 'selected' : '' ?>>🌅 เช้า</option>
                                        <option value="กลางวัน" <?= $schedTime === 'กลางวัน' ? 'selected' : '' ?>>☀️ กลางวัน</option>
                                        <option value="เย็น" <?= $schedTime === 'เย็น' ? 'selected' : '' ?>>🌇 เย็น</option>
                                        <option value="ก่อนนอน" <?= $schedTime === 'ก่อนนอน' ? 'selected' : '' ?>>🌙 ก่อนนอน</option>
                                    </select>
                                </div>

                                <!-- Filter Buttons -->
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn-filter-apply">
                                        <i class="fa-solid fa-filter"></i>
                                        <span>กรองข้อมูล</span>
                                    </button>
                                    <?php if ($schedSearch !== '' || ($schedTime !== '' && $schedTime !== 'all')): ?>
                                        <a href="?id=<?= urlencode($userId) ?>&tab=schedules" class="btn-filter-reset">
                                            <i class="fa-solid fa-rotate-left"></i>
                                            <span>ล้าง</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>

                        <!-- Schedules Cards Grid -->
                        <?php if (count($schedules) > 0): ?>
                            <div class="row g-3">
                                <?php foreach ($schedules as $sched): ?>
                                    <?php
                                    $timeBadgeClass = 'time-morning';
                                    $timeIcon = 'fa-sun';
                                    if ($sched['time_to_take'] === 'กลางวัน') {
                                        $timeBadgeClass = 'time-afternoon';
                                        $timeIcon = 'fa-cloud-sun';
                                    } elseif ($sched['time_to_take'] === 'เย็น') {
                                        $timeBadgeClass = 'time-evening';
                                        $timeIcon = 'fa-sun-haze';
                                    } elseif ($sched['time_to_take'] === 'ก่อนนอน') {
                                        $timeBadgeClass = 'time-bedtime';
                                        $timeIcon = 'fa-moon';
                                    }
                                    ?>
                                    <div class="col-12 col-md-6 col-xl-4">
                                        <div class="schedule-card">
                                            <div>
                                                <!-- Card Header: Time and Dosage -->
                                                <div class="schedule-card-header">
                                                    <span class="time-pill-badge <?= $timeBadgeClass ?>">
                                                        <i class="fa-solid <?= $timeIcon ?>"></i>
                                                        มื้อ: <?= e($sched['time_to_take']) ?>
                                                    </span>
                                                    <span class="dosage-badge">
                                                        <i class="fa-solid fa-pills me-1"></i>
                                                        <?= e($sched['dosage_amount']) ?> เม็ด/โดส
                                                    </span>
                                                </div>

                                                <!-- Trade Name and Active Ingredient -->
                                                <div class="med-trade-name">
                                                    <i class="fa-solid fa-prescription-bottle" style="color: var(--color-teal);"></i>
                                                    <?= !empty($sched['trade_name']) ? e($sched['trade_name']) : 'รหัสยา #' . e($sched['tpu_code']) ?>
                                                </div>
                                                <span class="med-active-ingredient">
                                                    <?= !empty($sched['active_ingredient']) ? e($sched['active_ingredient']) : 'Active Ingredient N/A' ?>
                                                </span>

                                                <!-- Detail Specs -->
                                                <ul class="med-details-list">
                                                    <li>
                                                        <strong>ความแรง:</strong>
                                                        <span><?= !empty($sched['strength']) ? e($sched['strength']) : '-' ?></span>
                                                    </li>
                                                    <li>
                                                        <strong>รูปแบบ:</strong>
                                                        <span><?= !empty($sched['dosageform']) ? e($sched['dosageform']) : '-' ?></span>
                                                    </li>
                                                    <li>
                                                        <strong>สรรพคุณ:</strong>
                                                        <span><?= !empty($sched['indications']) ? e($sched['indications']) : '-' ?></span>
                                                    </li>
                                                    <?php if (!empty($sched['precautions'])): ?>
                                                        <li>
                                                            <strong style="color: #856404;">คำเตือน:</strong>
                                                            <span style="color: #856404;"><?= e($sched['precautions']) ?></span>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>

                                            <!-- Footer Info -->
                                            <div class="schedule-card-footer">
                                                <span>รหัสตาราง: <strong><?= e($sched['schedule_id']) ?></strong></span>
                                                <span>TPU: #<?= e($sched['tpu_code']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-box">
                                <i class="fa-solid fa-calendar-xmark d-block"></i>
                                <h6>ไม่พบรายการยาตามเงื่อนไขที่กำหนด</h6>
                                <p>ผู้ป่วยรายนี้ยังไม่มีรายการยาในตารางเวลา หรือไม่มีรายการที่ตรงกับคำค้นหา</p>
                            </div>
                        <?php endif; ?>
                    </div>

                <!-- TAB 2: MEDICATION LOGS CONTENT -->
                <?php else: ?>
                    <div>
                        <!-- Filter Toolbar for Medication Logs -->
                        <div class="filter-toolbar">
                            <form method="GET" action="user-details.php" class="filter-row">
                                <input type="hidden" name="id" value="<?= e($userId) ?>">
                                <input type="hidden" name="tab" value="logs">

                                <!-- Search keyword -->
                                <div class="filter-col">
                                    <label class="filter-label">
                                        <i class="fa-solid fa-magnifying-glass me-1"></i> ค้นหาชื่อยา / ผลข้างเคียง
                                    </label>
                                    <input type="text"
                                           name="log_search"
                                           class="filter-input"
                                           placeholder="เช่น ปวดหัว, อาเจียน, ชื่อยา..."
                                           value="<?= e($logSearch) ?>">
                                </div>

                                <!-- Filter by Status -->
                                <div class="filter-col-sm">
                                    <label class="filter-label">
                                        <i class="fa-solid fa-check-double me-1"></i> สถานะการกินยา
                                    </label>
                                    <select name="log_status" class="filter-input">
                                        <option value="all" <?= $logStatus === 'all' || $logStatus === '' ? 'selected' : '' ?>>ทั้งหมด</option>
                                        <option value="กินแล้ว" <?= $logStatus === 'กินแล้ว' ? 'selected' : '' ?>>✅ กินแล้ว</option>
                                        <option value="ลืมกินยา" <?= $logStatus === 'ลืมกินยา' ? 'selected' : '' ?>>⚠️ ลืมกินยา</option>
                                        <option value="ยังไม่ได้กิน" <?= $logStatus === 'ยังไม่ได้กิน' ? 'selected' : '' ?>>⏳ ยังไม่ได้กิน</option>
                                    </select>
                                </div>

                                <!-- Filter by Time Slot -->
                                <div class="filter-col-sm">
                                    <label class="filter-label">
                                        <i class="fa-solid fa-clock me-1"></i> มื้อยาที่กำหนด
                                    </label>
                                    <select name="log_time" class="filter-input">
                                        <option value="all" <?= $logTime === 'all' || $logTime === '' ? 'selected' : '' ?>>ทุกมื้อ</option>
                                        <option value="เช้า" <?= $logTime === 'เช้า' ? 'selected' : '' ?>>เช้า</option>
                                        <option value="กลางวัน" <?= $logTime === 'กลางวัน' ? 'selected' : '' ?>>กลางวัน</option>
                                        <option value="เย็น" <?= $logTime === 'เย็น' ? 'selected' : '' ?>>เย็น</option>
                                        <option value="ก่อนนอน" <?= $logTime === 'ก่อนนอน' ? 'selected' : '' ?>>ก่อนนอน</option>
                                    </select>
                                </div>

                                <!-- Date From -->
                                <div class="filter-col-sm">
                                    <label class="filter-label">
                                        <i class="fa-regular fa-calendar me-1"></i> ตั้งแต่วันที่
                                    </label>
                                    <input type="date"
                                           name="log_date_from"
                                           class="filter-input"
                                           value="<?= e($logDateFrom) ?>">
                                </div>

                                <!-- Date To -->
                                <div class="filter-col-sm">
                                    <label class="filter-label">
                                        <i class="fa-regular fa-calendar me-1"></i> ถึงวันที่
                                    </label>
                                    <input type="date"
                                           name="log_date_to"
                                           class="filter-input"
                                           value="<?= e($logDateTo) ?>">
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn-filter-apply">
                                        <i class="fa-solid fa-filter"></i>
                                        <span>กรอง</span>
                                    </button>
                                    <?php if ($logSearch !== '' || ($logStatus !== '' && $logStatus !== 'all') || ($logTime !== '' && $logTime !== 'all') || $logDateFrom !== '' || $logDateTo !== ''): ?>
                                        <a href="?id=<?= urlencode($userId) ?>&tab=logs" class="btn-filter-reset">
                                            <i class="fa-solid fa-rotate-left"></i>
                                            <span>ล้าง</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>

                        <!-- Logs Table -->
                        <?php if (count($medicationLogs) > 0): ?>
                            <div class="table-responsive">
                                <table class="custom-table">
                                    <thead>
                                        <tr>
                                            <th>รหัส Log</th>
                                            <th>วัน-เวลาที่บันทึก</th>
                                            <th>ยาที่ทาน</th>
                                            <th>มื้อ/ขนาด</th>
                                            <th>สถานะการทานยา</th>
                                            <th>อาการข้างเคียงที่พบ</th>
                                            <th>รหัสตาราง</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($medicationLogs as $log): ?>
                                            <?php
                                            $stClass = 'log-status-pending';
                                            $stIcon = 'fa-hourglass-start';
                                            if ($log['status'] === 'กินแล้ว') {
                                                $stClass = 'log-status-taken';
                                                $stIcon = 'fa-circle-check';
                                            } elseif ($log['status'] === 'ลืมกินยา') {
                                                $stClass = 'log-status-missed';
                                                $stIcon = 'fa-triangle-exclamation';
                                            }
                                            ?>
                                            <tr>
                                                <!-- Log ID -->
                                                <td>
                                                    <span style="font-weight: 600; color: var(--color-secondary);">
                                                        <?= e($log['log_id']) ?>
                                                    </span>
                                                </td>

                                                <!-- Action Time -->
                                                <td>
                                                    <i class="fa-regular fa-calendar-days me-1" style="color: var(--color-teal);"></i>
                                                    <?= date('d/m/Y H:i', strtotime($log['action_time'])) ?> น.
                                                </td>

                                                <!-- Medicine Name -->
                                                <td>
                                                    <strong style="color: var(--color-primary);">
                                                        <?= !empty($log['trade_name']) ? e($log['trade_name']) : 'รหัสยา #' . e($log['schedule_id']) ?>
                                                    </strong>
                                                    <?php if (!empty($log['strength'])): ?>
                                                        <small class="d-block text-muted">
                                                            <?= e($log['strength']) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- Dose & Time slot -->
                                                <td>
                                                    <span class="badge" style="background: rgba(78, 127, 144, 0.12); color: var(--color-primary); font-weight: 600;">
                                                        มื้อ <?= e($log['time_to_take']) ?> (<?= e($log['dosage_amount']) ?> เม็ด)
                                                    </span>
                                                </td>

                                                <!-- Status -->
                                                <td>
                                                    <span class="log-status-badge <?= $stClass ?>">
                                                        <i class="fa-solid <?= $stIcon ?>"></i>
                                                        <?= e($log['status']) ?>
                                                    </span>
                                                </td>

                                                <!-- Side Effect -->
                                                <td>
                                                    <?php if ($log['side_effect'] !== 'ไม่มีอาการ' && !empty($log['side_effect'])): ?>
                                                        <span style="color: #bd2130; font-weight: 500;">
                                                            <i class="fa-solid fa-circle-exclamation me-1"></i>
                                                            <?= e($log['side_effect']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">
                                                            <i class="fa-regular fa-circle-check me-1 text-success"></i>
                                                            ไม่มีอาการ
                                                        </span>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- Schedule ID -->
                                                <td>
                                                    <span class="text-muted" style="font-size: 11px;">
                                                        <?= e($log['schedule_id']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-box">
                                <i class="fa-solid fa-clipboard-question d-block"></i>
                                <h6>ไม่พบประวัติการรับประทานยาตามเงื่อนไข</h6>
                                <p>ยังไม่มีบันทึกประวัติการทานยา หรือไม่มีข้อมูลตรงกับตัวกรองที่เลือก</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

    </div>

    <!-- Footer -->
    <footer class="footer">
        &copy; <?= date('Y') ?> <strong>Medisync</strong>. Personal Medicine Management System.
        สงวนลิขสิทธิ์ระบบบริหารจัดการยาและผู้ป่วย
    </footer>

</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const menuBtn = document.getElementById('mobileMenuBtn');

    function toggleSidebar(show) {
        sidebar.classList.toggle('show', show);
        overlay.classList.toggle('show', show);
    }

    if (menuBtn) {
        menuBtn.addEventListener('click', function () {
            toggleSidebar(!sidebar.classList.contains('show'));
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            toggleSidebar(false);
        });
    }

    document.querySelectorAll('.sidebar-menu a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth < 992) {
                toggleSidebar(false);
            }
        });
    });
</script>

</body>
</html>

