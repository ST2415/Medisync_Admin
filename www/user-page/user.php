<?php
$pageTitle = "Users";
require_once '../db_conn.php';

// ฟังก์ชัน Escape HTML เพื่อความปลอดภัย
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// รับค่าคำค้นหาและตัวกรอง
$search = trim($_GET['search'] ?? '');
$filterGender = trim($_GET['gender'] ?? '');
$filterDisease = trim($_GET['disease'] ?? '');

// ดึงรายการโรคที่มีทั้งหมดสำหรับตัวเลือกกรอง
$diseasesList = [];
$diseaseRes = $conn->query("
    SELECT DISTINCT disease 
    FROM users 
    WHERE disease IS NOT NULL AND disease != '' 
    ORDER BY disease ASC
");
if ($diseaseRes) {
    while ($dRow = $diseaseRes->fetch_assoc()) {
        $diseasesList[] = $dRow['disease'];
    }
}

// ฟังก์ชันนับจำนวนข้อมูลสำหรับ Summary Cards
function countRecords($conn, $table) {
    $allowedTables = [
        'users',
        'schedules',
        'medication_log'
    ];

    if (!in_array($table, $allowedTables, true)) {
        return 0;
    }

    $sql = "SELECT COUNT(*) AS total FROM `$table`";
    $result = $conn->query($sql);

    return $result ? (int)$result->fetch_assoc()['total'] : 0;
}

$totalUsers = countRecords($conn, 'users');
$totalSchedules = countRecords($conn, 'schedules');
$totalLogs = countRecords($conn, 'medication_log');

// ดึงข้อมูล Users พร้อมนับจำนวน Schedules และ Medication Logs
$sql = "
    SELECT
        u.user_id,
        u.name,
        u.gender,
        u.date_of_birth,
        u.symptoms,
        u.causes,
        u.disease,
        u.medicine,

        (
            SELECT COUNT(*)
            FROM schedules s
            WHERE s.user_id = u.user_id
        ) AS total_schedules,

        (
            SELECT COUNT(*)
            FROM medication_log ml
            INNER JOIN schedules s ON ml.schedule_id = s.schedule_id
            WHERE s.user_id = u.user_id
        ) AS total_logs

    FROM users u
    WHERE 1=1
";

$params = [];
$types = "";

if ($search !== '') {
    $sql .= " AND (
        CAST(u.user_id AS CHAR) LIKE ?
        OR u.name LIKE ?
        OR u.gender LIKE ?
        OR u.symptoms LIKE ?
        OR u.causes LIKE ?
        OR u.disease LIKE ?
        OR u.medicine LIKE ?
    )";
    $keyword = "%{$search}%";
    for ($i = 0; $i < 7; $i++) {
        $params[] = $keyword;
        $types .= "s";
    }
}

if ($filterGender !== '') {
    $sql .= " AND u.gender = ?";
    $params[] = $filterGender;
    $types .= "s";
}

if ($filterDisease !== '') {
    $sql .= " AND u.disease = ?";
    $params[] = $filterDisease;
    $types .= "s";
}

$sql .= " ORDER BY u.user_id ASC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การจัดการข้อมูลผู้ใช้ | Medisync Admin</title>

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
            /* สีตามที่ผู้ใช้ระบุ */
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
            padding: 28px 34px 40px;
            flex-grow: 1;
        }

        /* ===== PAGE HEADER BANNER ===== */
        .page-banner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 26px;
        }

        .page-banner-title h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--color-primary);
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-banner-title p {
            color: var(--text-muted);
            margin: 0;
            font-size: 13.5px;
        }

        .btn-add-user {
            background: var(--color-primary);
            color: var(--color-white);
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 13.5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.22s ease;
            box-shadow: 0 4px 14px rgba(41, 69, 73, 0.2);
        }

        .btn-add-user:hover {
            background: var(--color-secondary);
            color: var(--color-white);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(41, 69, 73, 0.25);
        }

        /* ===== SUMMARY CARDS ===== */
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 22px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.24s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.24s ease;
            box-shadow: 0 6px 20px rgba(41, 69, 73, 0.06);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--stat-theme-color, var(--color-teal));
            opacity: 0.85;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(41, 69, 73, 0.12);
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .stat-label-group h6 {
            color: var(--color-primary);
            font-size: 14.5px;
            font-weight: 600;
            margin: 0;
        }

        .stat-label-group small {
            color: var(--text-muted);
            font-size: 11.5px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            flex-shrink: 0;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--color-primary);
            line-height: 1.15;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .stat-description {
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ===== MAIN DATA PANEL ===== */
        .panel {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 6px 20px rgba(41, 69, 73, 0.06);
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 22px;
            flex-wrap: wrap;
            padding-bottom: 18px;
            border-bottom: 1px solid rgba(74, 128, 126, 0.12);
        }

        .panel-header-title h5 {
            font-size: 17px;
            font-weight: 700;
            color: var(--color-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-header-title p {
            color: var(--text-muted);
            font-size: 12.5px;
            margin: 4px 0 0;
        }

        /* ===== SEARCH & FILTER CONTROLS ===== */
        .filter-controls-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
        }

        .search-container {
            display: flex;
            gap: 8px;
            width: auto;
            min-width: 170px;
            max-width: 240px;
            flex: 1 1 auto;
        }

        .filter-select {
            border: 1px solid rgba(74, 128, 126, 0.3);
            border-radius: 12px;
            padding: 8px 28px 8px 12px;
            font-family: inherit;
            font-size: 12px;
            background-color: #FAFCFC;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%234A807E' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 11px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            color: var(--color-primary);
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .filter-select[name="gender"] {
            min-width: 105px;
            max-width: 120px;
        }

        .filter-select[name="disease"] {
            min-width: 140px;
            max-width: 185px;
            text-overflow: ellipsis;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--color-secondary);
            background-color: #FFFFFF;
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

        .search-input-wrapper {
            position: relative;
            flex-grow: 1;
        }

        .search-input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-secondary);
            font-size: 13px;
        }

        .search-input-wrapper input {
            width: 100%;
            border: 1px solid rgba(74, 128, 126, 0.3);
            border-radius: 12px;
            padding: 8px 12px 8px 34px;
            font-family: inherit;
            font-size: 12px;
            background: #FAFCFC;
            color: var(--color-primary);
            transition: all 0.2s ease;
        }

        .search-input-wrapper input:focus {
            outline: none;
            border-color: var(--color-secondary);
            background: #FFFFFF;
            box-shadow: 0 0 0 3px rgba(78, 127, 144, 0.18);
        }

        .btn-search {
            background: var(--color-primary);
            color: var(--color-white);
            border: none;
            border-radius: 12px;
            padding: 8px 14px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .btn-search:hover {
            background: var(--color-secondary);
            transform: translateY(-1px);
        }

        .btn-reset {
            border: 1px solid rgba(74, 128, 126, 0.3);
            color: var(--color-primary);
            background: rgba(187, 215, 218, 0.25);
            border-radius: 12px;
            padding: 8px 11px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            white-space: nowrap;
            flex-shrink: 0;
            text-decoration: none;
        }

        .btn-reset:hover {
            background: var(--color-primary);
            color: var(--color-white);
        }

        /* ===== TABLE ===== */
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
            padding: 14px 12px;
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
            padding: 14px 12px;
            border-bottom: 1px solid rgba(74, 128, 126, 0.1);
            color: var(--color-primary);
        }

        .user-id-badge {
            color: var(--color-primary);
            font-weight: 700;
            background: rgba(78, 127, 144, 0.14);
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 11.5px;
            display: inline-block;
            font-family: monospace;
            border: 1px solid rgba(78, 127, 144, 0.25);
        }

        .user-name-link {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            color: inherit;
            transition: transform 0.18s ease;
        }

        .user-name-link:hover {
            transform: translateX(3px);
        }

        .user-name-link:hover .patient-name-text {
            color: var(--color-secondary);
            text-decoration: underline;
        }

        .patient-info-block {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .patient-avatar-box {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
            background: rgba(144, 194, 183, 0.3);
            color: var(--color-primary);
            border: 1px solid rgba(144, 194, 183, 0.6);
        }

        .patient-avatar-male {
            background: rgba(78, 127, 144, 0.2);
            color: #294549;
            border: 1px solid rgba(78, 127, 144, 0.4);
        }

        .patient-avatar-female {
            background: rgba(144, 194, 183, 0.3);
            color: #175249;
            border: 1px solid rgba(144, 194, 183, 0.6);
        }

        .patient-name-text {
            font-weight: 700;
            color: var(--color-primary);
            font-size: 13.5px;
            display: block;
        }

        .patient-meta-sub {
            font-size: 11px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 2px;
        }

        .btn-view-details {
            background: rgba(144, 194, 183, 0.28);
            color: #175249;
            border: 1px solid rgba(144, 194, 183, 0.6);
        }

        .btn-view-details:hover {
            background: var(--color-mint);
            color: var(--color-primary);
            transform: translateY(-1px);
        }

        .user-avatar-mini {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 600;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(41, 69, 73, 0.08);
        }

        .user-avatar-male {
            background: rgba(78, 127, 144, 0.2);
            color: #294549;
        }

        .user-avatar-female {
            background: rgba(144, 194, 183, 0.35);
            color: #205c53;
        }

        .user-name-text {
            font-weight: 600;
            color: var(--color-primary);
        }

        /* Gender Pills */
        .gender-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .gender-male {
            background: rgba(78, 127, 144, 0.15);
            color: #294549;
        }

        .gender-female {
            background: rgba(144, 194, 183, 0.25);
            color: #175249;
        }

        .disease-tag-sm {
            display: inline-block;
            background: rgba(74, 128, 126, 0.14);
            color: #1f4745;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: 600;
        }

        /* Symptom & Cause Tags */
        .symptom-tag {
            display: inline-block;
            background: rgba(187, 215, 218, 0.35);
            color: var(--color-primary);
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 500;
        }

        /* Disease Tag */
        .disease-tag {
            display: inline-block;
            background: rgba(74, 128, 126, 0.15);
            color: #1f4745;
            padding: 4px 9px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
            border-left: 3px solid var(--color-teal);
        }

        /* Medicine Tag */
        .medicine-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: linear-gradient(135deg, rgba(144, 194, 183, 0.25) 0%, rgba(187, 215, 218, 0.4) 100%);
            color: var(--color-primary);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 600;
            border: 1px solid rgba(144, 194, 183, 0.5);
            white-space: nowrap;
        }

        .medicine-pill i {
            color: var(--color-teal);
        }

        /* Metric Badges (Schedules & Logs) */
        .badge-counts {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .count-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        .schedule-pill {
            background: rgba(78, 127, 144, 0.12);
            color: var(--color-secondary);
        }

        .log-pill {
            background: rgba(74, 128, 126, 0.14);
            color: var(--color-teal);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 6px;
            align-items: center;
            white-space: nowrap;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11.5px;
            font-weight: 600;
            transition: all 0.18s ease;
        }

        .btn-edit {
            background: rgba(78, 127, 144, 0.12);
            color: var(--color-secondary);
            border: 1px solid rgba(78, 127, 144, 0.3);
        }

        .btn-edit:hover {
            background: var(--color-secondary);
            color: var(--color-white);
            transform: translateY(-1px);
        }

        .btn-delete {
            background: rgba(220, 53, 69, 0.08);
            color: #c82333;
            border: 1px solid rgba(220, 53, 69, 0.25);
        }

        .btn-delete:hover {
            background: #dc3545;
            color: var(--color-white);
            transform: translateY(-1px);
        }

        /* Empty State */
        .empty-state {
            padding: 55px 20px !important;
            text-align: center;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 44px;
            margin-bottom: 16px;
            color: var(--color-secondary);
            opacity: 0.6;
        }

        .empty-state h6 {
            font-weight: 700;
            color: var(--color-primary);
            font-size: 16px;
            margin-bottom: 6px;
        }

        .result-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-muted);
            font-size: 12.5px;
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px solid rgba(74, 128, 126, 0.12);
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

        /* ===== MOBILE TOGGLE & OVERLAY ===== */
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
            transition: background 0.2s ease;
        }

        .mobile-menu-btn:hover {
            background: var(--color-primary);
            color: var(--color-white);
        }

        .sidebar-overlay {
            display: none;
        }

        /* ===== RESPONSIVE ===== */
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

            .filter-controls-wrap {
                width: 100%;
                overflow-x: auto;
                padding-bottom: 4px;
                flex-wrap: nowrap;
                -webkit-overflow-scrolling: touch;
            }

            .search-container {
                min-width: 170px;
                max-width: 240px;
            }
        }

        @media (max-width: 575.98px) {
            .admin-info {
                display: none;
            }

            .stat-value {
                font-size: 26px;
            }

            .page-banner {
                flex-direction: column;
                align-items: flex-start;
            }

            .btn-add-user {
                width: 100%;
                justify-content: center;
            }
        }

        /* Floating White Toast Notification */
        .floating-white-toast {
            position: fixed;
            top: 24px;
            right: 28px;
            z-index: 99999;
            min-width: 320px;
            max-width: 440px;
            background: #ffffff !important;
            border-radius: 16px;
            box-shadow: 0 16px 40px rgba(41, 69, 73, 0.22), 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(74, 128, 126, 0.25);
            border-left: 6px solid #198754;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            animation: toastFloatSlide 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .floating-white-toast.toast-update {
            border-left-color: #4A807E;
        }

        .floating-white-toast.toast-delete {
            border-left-color: #dc3545;
        }

        .toast-icon-circle {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: rgba(25, 135, 84, 0.12);
            color: #198754;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .floating-white-toast.toast-update .toast-icon-circle {
            background: rgba(74, 128, 126, 0.15);
            color: #4A807E;
        }

        .floating-white-toast.toast-delete .toast-icon-circle {
            background: rgba(220, 53, 69, 0.12);
            color: #dc3545;
        }

        .toast-content-text {
            flex: 1;
        }

        .toast-headline {
            font-size: 14px;
            font-weight: 700;
            color: #294549;
            margin-bottom: 2px;
            line-height: 1.3;
        }

        .toast-subline {
            font-size: 12.5px;
            color: #537478;
            margin: 0;
            line-height: 1.4;
        }

        .toast-close-btn {
            background: transparent;
            border: none;
            color: #8da4a7;
            font-size: 16px;
            cursor: pointer;
            padding: 6px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .toast-close-btn:hover {
            color: #294549;
            background: rgba(74, 128, 126, 0.12);
        }

        @keyframes toastFloatSlide {
            from {
                opacity: 0;
                transform: translateY(-24px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
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
            Medisync Admin Panel<br>
            <span style="opacity: 0.8;">การจัดการข้อมูลผู้ป่วย & ทานยา</span>
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
                <h5>
                    <i class="fa-solid fa-hospital-user" style="color: var(--color-teal);"></i>
                    ระบบจัดการข้อมูลผู้ใช้
                </h5>
                <p>บันทึก ประวัติอาการ โรคที่วินิจฉัย และยาที่ได้รับ</p>
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

    <!-- Page Content Wrapper -->
    <div class="content-wrapper">

        <!-- Floating Flash Toast Notification (White Background & Floating Drop Shadow) -->
        <?php if (isset($_GET['msg'])): ?>
            <?php
            $msgType = $_GET['msg'];
            $toastTheme = '';
            $toastIcon = 'fa-circle-check';
            $toastTitle = 'ทำรายการสำเร็จ';
            $toastText = '';

            if ($msgType === 'added' || $msgType === 'New record created successfully') {
                $toastText = 'เพิ่มข้อมูลผู้ป่วยใหม่เข้าสู่ระบบเรียบร้อยแล้ว';
            } elseif ($msgType === 'updated' || $msgType === 'Data Updated successfully') {
                $toastTheme = 'toast-update';
                $toastIcon = 'fa-pen-to-square';
                $toastTitle = 'อัปเดตข้อมูลสำเร็จ';
                $toastText = 'บันทึกการแก้ไขข้อมูลผู้ป่วยเรียบร้อยแล้ว';
            } elseif ($msgType === 'deleted' || $msgType === 'Record Delete Successfully') {
                $toastTheme = 'toast-delete';
                $toastIcon = 'fa-trash-can';
                $toastTitle = 'ลบข้อมูลสำเร็จ';
                $toastText = 'ลบข้อมูลผู้ป่วยและประวัติที่เกี่ยวข้องเรียบร้อยแล้ว';
            } else {
                $toastText = e($msgType);
            }
            ?>
            <?php if ($toastText !== ''): ?>
                <div class="floating-white-toast <?= $toastTheme ?>" id="floatingToastAlert">
                    <div class="toast-icon-circle">
                        <i class="fa-solid <?= $toastIcon ?>"></i>
                    </div>
                    <div class="toast-content-text">
                        <div class="toast-headline"><?= $toastTitle ?></div>
                        <div class="toast-subline"><?= $toastText ?></div>
                    </div>
                    <button type="button" class="toast-close-btn" onclick="dismissToastAlert()" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <script>
                    function dismissToastAlert() {
                        const toast = document.getElementById('floatingToastAlert');
                        if (toast) {
                            toast.style.transition = 'all 0.3s ease';
                            toast.style.opacity = '0';
                            toast.style.transform = 'translateY(-20px) scale(0.95)';
                            setTimeout(() => toast.remove(), 300);
                        }
                    }
                    setTimeout(dismissToastAlert, 4500);
                </script>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Page Header Banner -->
        <section class="page-banner">
            <div class="page-banner-title">
                <h2>
                    <i class="fa-solid fa-address-book" style="color: var(--color-secondary);"></i>
                    รายชื่อผู้ใช้และประวัติสุขภาพ
                </h2>
                <p>ตรวจสอบ ข้อมูลการวินิจฉัย อาการ ยาที่แนะนำ และติดตามการทานยาของผู้ป่วยในระบบ</p>
            </div>

            <a href="add-user.php" class="btn-add-user">
                <i class="fa-solid fa-user-plus"></i>
                <span>เพิ่มผู้ใช้ใหม่</span>
            </a>
        </section>

        <!-- Summary Metric Cards -->
        <section class="row g-3 mb-4">

            <!-- Card 1: Users -->
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="stat-card" style="--stat-theme-color: #294549;">
                    <div class="stat-top">
                        <div class="stat-label-group">
                            <h6>ผู้ใช้ทั้งหมด</h6>
                            <small>Total Users</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(41, 69, 73, 0.12); color: #294549;">
                            <i class="fa-solid fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?= number_format($totalUsers) ?>
                    </div>
                    <div class="stat-description">
                        <i class="fa-solid fa-user-check"></i>
                        <span>ลงทะเบียนในระบบแล้ว</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Schedules -->
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="stat-card" style="--stat-theme-color: #4E7F90;">
                    <div class="stat-top">
                        <div class="stat-label-group">
                            <h6>ตารางเวลาการทานยา</h6>
                            <small>Total Schedules</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(78, 127, 144, 0.14); color: #4E7F90;">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?= number_format($totalSchedules) ?>
                    </div>
                    <div class="stat-description">
                        <i class="fa-solid fa-bell"></i>
                        <span>กำหนดเวลาแจ้งเตือนในระบบ</span>
                    </div>
                </div>
            </div>

            <!-- Card 3: Medication Logs -->
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="stat-card" style="--stat-theme-color: #4A807E;">
                    <div class="stat-top">
                        <div class="stat-label-group">
                            <h6>ประวัติการรับประทานยา</h6>
                            <small>Medication Logs</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(74, 128, 126, 0.14); color: #4A807E;">
                            <i class="fa-solid fa-notes-medical"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?= number_format($totalLogs) ?>
                    </div>
                    <div class="stat-description">
                        <i class="fa-solid fa-clipboard-check"></i>
                        <span>บันทึกการทานยาในระบบ</span>
                    </div>
                </div>
            </div>

        </section>

        <!-- Main User Table Panel -->
        <section class="panel">

            <div class="panel-header">
                <div class="panel-header-title">
                    <h5>
                        <i class="fa-solid fa-user-group" style="color: var(--color-secondary);"></i>
                        ตารางรายชื่อผู้ใช้ทั้งหมด
                    </h5>
                    <p>แสดงรายละเอียดข้อมูลผู้ป่วย โรคที่ตรวจพบ และรายการยาที่แนะนำ</p>
                </div>

                <!-- Search & Filters -->
                <form method="GET" action="user.php" class="filter-controls-wrap">
                    <div class="search-container">
                        <div class="search-input-wrapper">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input
                                type="text"
                                name="search"
                                placeholder="ค้นหา ID, ชื่อผู้ใช้, อาการ, โรค, ยา..."
                                value="<?= e($search) ?>"
                            >
                        </div>
                    </div>

                    <!-- Gender Filter -->
                    <select name="gender" class="filter-select" onchange="this.form.submit()">
                        <option value="">-- ทุกเพศ --</option>
                        <option value="ชาย" <?= $filterGender === 'ชาย' ? 'selected' : '' ?>>เพศชาย</option>
                        <option value="หญิง" <?= $filterGender === 'หญิง' ? 'selected' : '' ?>>เพศหญิง</option>
                    </select>

                    <!-- Disease Filter -->
                    <select name="disease" class="filter-select" onchange="this.form.submit()">
                        <option value="">-- ทุกโรคที่วินิจฉัย --</option>
                        <?php foreach ($diseasesList as $diseaseItem): ?>
                            <option value="<?= e($diseaseItem) ?>" <?= $filterDisease === $diseaseItem ? 'selected' : '' ?>>
                                <?= e($diseaseItem) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span>ค้นหา</span>
                    </button>

                    <?php if ($search !== '' || $filterGender !== '' || $filterDisease !== ''): ?>
                        <a href="user.php" class="btn-reset" title="ล้างตัวกรองและการค้นหา">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Responsive Table -->
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ข้อมูลผู้ใช้</th>
                            <th>วันเกิด</th>
                            <th>อาการ</th>
                            <th>สาเหตุ</th>
                            <th>โรคที่วินิจฉัย</th>
                            <th>ยาที่แนะนำ</th>
                            <th class="text-center">การจัดการ</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                                <?php
                                $isMale = ($user['gender'] === 'ชาย');
                                $avatarClass = $isMale ? 'patient-avatar-male' : 'patient-avatar-female';
                                $genderIcon = $isMale ? 'fa-mars' : 'fa-venus';
                                ?>
                                <tr>
                                    <!-- User ID -->
                                    <td>
                                        <span class="user-id-badge">
                                            <?= e($user['user_id']) ?>
                                        </span>
                                    </td>

                                    <!-- User Info (Profile Avatar + Name + Gender) -->
                                    <td>
                                        <a href="user-details.php?id=<?= urlencode($user['user_id']) ?>"
                                           class="user-name-link text-decoration-none"
                                           title="คลิกเพื่อดูรายละเอียดผู้ป่วย ตารางเวลา และประวัติการทานยา">
                                            <div class="patient-info-block">
                                                <div class="patient-avatar-box <?= $avatarClass ?>">
                                                    <i class="fa-solid <?= $genderIcon ?>"></i>
                                                </div>
                                                <div>
                                                    <span class="patient-name-text">
                                                        <?= e($user['name']) ?>
                                                        <i class="fa-solid fa-circle-chevron-right ms-1" style="font-size: 11px; color: var(--color-teal); opacity: 0.8;"></i>
                                                    </span>
                                                    <div class="patient-meta-sub">
                                                        <span><?= e($user['gender'] ?? '-') ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </td>

                                    <!-- Date of Birth -->
                                    <td>
                                        <i class="fa-regular fa-calendar me-1" style="color: var(--color-teal); opacity: 0.8;"></i>
                                        <?= e($user['date_of_birth']) ?>
                                    </td>

                                    <!-- Symptoms -->
                                    <td>
                                        <?= !empty($user['symptoms']) ? e($user['symptoms']) : '<span class="text-muted">-</span>' ?>
                                    </td>

                                    <!-- Causes -->
                                    <td>
                                        <?= !empty($user['causes']) ? e($user['causes']) : '<span class="text-muted">-</span>' ?>
                                    </td>

                                    <!-- Diagnosis / Disease -->
                                    <td>
                                        <?php if (!empty($user['disease'])): ?>
                                            <span class="disease-tag-sm">
                                                <i class="fa-solid fa-stethoscope me-1"></i><?= e($user['disease']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Medicine -->
                                    <td>
                                        <?php if (!empty($user['medicine'])): ?>
                                            <span class="medicine-pill">
                                                <i class="fa-solid fa-capsules"></i>
                                                <?= e($user['medicine']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Actions -->
                                    <td>
                                        <div class="action-buttons justify-content-center">
                                            <a href="user-details.php?id=<?= urlencode($user['user_id']) ?>"
                                               class="btn-action btn-view-details"
                                               title="ดูประวัติการกินยาและตารางเวลา">
                                                <i class="fa-solid fa-file-medical"></i>
                                                <span>ดู</span>
                                            </a>

                                            <a href="edit-user.php?id=<?= urlencode($user['user_id']) ?>"
                                               class="btn-action btn-edit"
                                               title="แก้ไขข้อมูล">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                                <span>แก้ไข</span>
                                            </a>

                                            <a href="delete-user.php?id=<?= urlencode($user['user_id']) ?>"
                                               class="btn-action btn-delete"
                                               onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลผู้ใช้ <?= e(addslashes($user['name'])) ?> ?')"
                                               title="ลบข้อมูล">
                                                <i class="fa-solid fa-trash-can"></i>
                                                <span>ลบ</span>
                                            </a>
                                        </div>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fa-solid fa-users-slash d-block"></i>
                                    <h6>ไม่พบข้อมูลผู้ใช้ที่ค้นหา</h6>
                                    <p class="mb-3">
                                        <?= ($search !== '' || $filterGender !== '' || $filterDisease !== '') ? 'ไม่มีข้อมูลที่ตรงกับคำค้นหาหรือตัวกรองที่เลือก' : 'ยังไม่มีข้อมูลผู้ใช้ในระบบ' ?>
                                    </p>
                                    <?php if ($search !== '' || $filterGender !== '' || $filterDisease !== ''): ?>
                                        <a href="user.php" class="btn btn-sm btn-outline-primary" style="border-radius: 20px;">
                                            <i class="fa-solid fa-rotate-left me-1"></i> ล้างตัวกรองและดูทั้งหมด
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Result Count Info -->
            <div class="result-info">
                <div>
                    แสดงข้อมูลทั้งหมด <strong><?= count($users) ?></strong> รายการ
                    <?php if ($search !== '' || $filterGender !== '' || $filterDisease !== ''): ?>
                        (ผลจากการกรองข้อมูล)
                    <?php endif; ?>
                </div>
                <div>
                    <span style="opacity: 0.7;">ฐานข้อมูล Medisync Users</span>
                </div>
            </div>

        </section>

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
