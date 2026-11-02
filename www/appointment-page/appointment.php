<?php
$pageTitle = "Appointments";
require_once '../db_conn.php';

// ฟังก์ชัน Escape HTML เพื่อความปลอดภัย
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// รับค่าคำค้นหาและตัวกรอง
$search = trim($_GET['search'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');
$filterDate = trim($_GET['date_filter'] ?? '');

// สถิติสำหรับ Summary Cards
$totalAppointments = 0;
$totalConfirmed = 0;
$totalPending = 0;
$totalRescheduled = 0;

$statRes = $conn->query("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'ยืนยัน' THEN 1 ELSE 0 END) AS confirmed,
        SUM(CASE WHEN status = 'รอยืนยัน' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = 'เลื่อน' THEN 1 ELSE 0 END) AS rescheduled
    FROM appointments
");
if ($statRes && $rowS = $statRes->fetch_assoc()) {
    $totalAppointments = (int)$rowS['total'];
    $totalConfirmed = (int)$rowS['confirmed'];
    $totalPending = (int)$rowS['pending'];
    $totalRescheduled = (int)$rowS['rescheduled'];
}

// สร้าง SQL Query ดึงรายการนัดหมาย พร้อมข้อมูลผู้ป่วย
$sql = "
    SELECT 
        a.appointment_id,
        a.user_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        u.name AS user_name,
        u.gender,
        u.disease,
        u.medicine
    FROM appointments a
    LEFT JOIN users u ON a.user_id = u.user_id
    WHERE 1=1
";

$params = [];
$types = "";

if ($search !== '') {
    $sql .= " AND (
        a.appointment_id LIKE ?
        OR CAST(a.user_id AS CHAR) LIKE ?
        OR u.name LIKE ?
        OR u.disease LIKE ?
        OR a.status LIKE ?
    )";
    $keyword = "%{$search}%";
    for ($i = 0; $i < 5; $i++) {
        $params[] = $keyword;
        $types .= "s";
    }
}

if ($filterStatus !== '') {
    $sql .= " AND a.status = ?";
    $params[] = $filterStatus;
    $types .= "s";
}

if ($filterDate === 'today') {
    $today = date('Y-m-d');
    $sql .= " AND a.appointment_date = ?";
    $params[] = $today;
    $types .= "s";
} elseif ($filterDate === 'upcoming') {
    $today = date('Y-m-d');
    $sql .= " AND a.appointment_date >= ?";
    $params[] = $today;
    $types .= "s";
}

$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การจัดการตารางนัดหมาย | Medisync Admin</title>

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
            /* Palette สีหลัก Medisync */
            --color-primary: #294549;       /* Deep Slate Pine */
            --color-secondary: #4E7F90;     /* Steel Teal */
            --color-teal: #4A807E;          /* Sage Teal */
            --color-mint: #90C2B7;          /* Fresh Mint */
            --color-ice: #BBD7DA;           /* Ice Pastel Cyan */
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
            -webkit-font-smoothing: antialiased;
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
            gap: 8px;
            font-size: 12px;
            color: var(--color-white);
            font-weight: 500;
            margin-bottom: 6px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #2ed573;
            box-shadow: 0 0 10px #2ed573;
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

        .page-heading h5 {
            font-size: 18px;
            font-weight: 700;
            color: var(--color-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .page-heading p {
            color: var(--text-muted);
            font-size: 12px;
            margin: 2px 0 0 0;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #FFFFFF;
            padding: 6px 14px;
            border-radius: 30px;
            border: 1px solid rgba(74, 128, 126, 0.2);
            box-shadow: 0 2px 8px rgba(41, 69, 73, 0.05);
        }

        .admin-avatar {
            width: 34px;
            height: 34px;
            background: var(--color-primary);
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

        /* ===== PAGE HEADER BANNER (HERO STYLE) ===== */
        .page-banner {
            background: linear-gradient(135deg, #294549 0%, #375b61 50%, #4E7F90 100%);
            color: var(--color-white);
            border-radius: 20px;
            padding: 28px 32px;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(41, 69, 73, 0.16);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(187, 215, 218, 0.2);
        }

        .page-banner::after {
            content: '';
            position: absolute;
            right: -60px;
            bottom: -60px;
            width: 260px;
            height: 260px;
            background: radial-gradient(circle, rgba(144, 194, 183, 0.22) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .page-banner-title {
            position: relative;
            z-index: 1;
        }

        .page-banner-title h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--color-white);
            margin: 0 0 8px 0;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-banner-title h2 i {
            color: var(--color-mint) !important;
        }

        .page-banner-title p {
            font-size: 13.5px;
            color: #dbe8e9;
            margin: 0;
            max-width: 650px;
            line-height: 1.6;
        }

        .hero-action-buttons {
            display: flex;
            gap: 12px;
            flex-shrink: 0;
            z-index: 1;
            position: relative;
        }

        .btn-add-appointment {
            background: var(--color-mint);
            color: var(--color-primary);
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(144, 194, 183, 0.35);
            text-decoration: none;
            position: relative;
            z-index: 1;
            flex-shrink: 0;
            border: none;
        }

        .btn-add-appointment:hover {
            background: var(--color-white);
            color: var(--color-primary);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
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

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(41, 69, 73, 0.12);
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

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 14px;
        }

        .stat-label-group h6 {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--color-primary);
            margin: 0;
        }

        .stat-label-group small {
            font-size: 11.5px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .stat-value {
            font-size: 30px;
            font-weight: 700;
            color: var(--color-primary);
            line-height: 1.1;
            margin-bottom: 8px;
        }

        .stat-description {
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ===== PANEL ===== */
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

        .search-input-wrapper {
            position: relative;
            flex-grow: 1;
        }

        .search-input-wrapper i {
            position: absolute;
            left: 12px;
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

        .filter-select {
            border: 1px solid rgba(74, 128, 126, 0.3);
            border-radius: 12px;
            padding: 8px 28px 8px 12px;
            font-family: inherit;
            font-size: 12px;
            background-color: #FAFCFC;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%234A807E' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 11px center;
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

        .filter-select[name="status"] {
            min-width: 105px;
            max-width: 120px;
        }

        .filter-select[name="date_filter"] {
            min-width: 125px;
            max-width: 165px;
            text-overflow: ellipsis;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--color-secondary);
            background-color: #FFFFFF;
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
            background: rgba(187, 215, 218, 0.22);
        }

        .custom-table tbody td {
            padding: 14px 12px;
            border-bottom: 1px solid rgba(74, 128, 126, 0.1);
            color: var(--color-primary);
        }

        .appointment-id-badge {
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

        .disease-tag-sm {
            display: inline-block;
            background: rgba(74, 128, 126, 0.14);
            color: #1f4745;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: 600;
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-confirmed {
            background: rgba(144, 194, 183, 0.35);
            color: #175249;
            border: 1px solid rgba(144, 194, 183, 0.6);
        }

        .status-pending {
            background: #FFF3CD;
            color: #856404;
            border: 1px solid #FFEEBA;
        }

        .status-rescheduled {
            background: rgba(78, 127, 144, 0.18);
            color: #294549;
            border: 1px solid rgba(78, 127, 144, 0.35);
        }

        .status-cancelled {
            background: rgba(220, 53, 69, 0.12);
            color: #842029;
            border: 1px solid rgba(220, 53, 69, 0.25);
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

        .btn-view {
            background: rgba(144, 194, 183, 0.28);
            color: #175249;
            border: 1px solid rgba(144, 194, 183, 0.6);
            cursor: pointer;
        }

        .btn-view:hover {
            background: var(--color-mint);
            color: var(--color-primary);
            transform: translateY(-1px);
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
            cursor: pointer;
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

        /* Floating Toast */
        .floating-white-toast {
            position: fixed;
            top: 24px;
            right: 28px;
            z-index: 99999;
            min-width: 320px;
            max-width: 460px;
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

        .floating-white-toast.toast-error {
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

        .floating-white-toast.toast-delete .toast-icon-circle,
        .floating-white-toast.toast-error .toast-icon-circle {
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

        /* Modal Customization */
        .modal-content {
            border-radius: 20px;
            border: 1px solid var(--card-border);
            box-shadow: 0 16px 48px rgba(41, 69, 73, 0.2);
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, rgba(144, 194, 183, 0.2) 0%, rgba(187, 215, 218, 0.3) 100%);
            border-bottom: 1px solid rgba(74, 128, 126, 0.15);
            padding: 20px 24px;
        }

        .modal-title {
            font-weight: 700;
            color: var(--color-primary);
            font-size: 17px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            border-top: 1px solid rgba(74, 128, 126, 0.15);
            padding: 16px 24px;
        }

        /* Responsive */
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
                gap: 20px;
                padding: 22px 20px;
            }
            .hero-action-buttons,
            .btn-add-appointment {
                width: 100%;
                justify-content: center;
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

    <!-- Sidebar Menu -->
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

    <!-- Sidebar Status Footer -->
    <div class="sidebar-footer-card">
        <div class="system-status-indicator">
            <span class="status-dot"></span>
            <span>ระบบออนไลน์พร้อมใช้งาน</span>
        </div>
        <div style="font-size: 11px; color: var(--color-ice); line-height: 1.5;">
            Medisync Admin Panel<br>
            <span style="opacity: 0.8;">ตารางนัดหมาย & พบแพทย์</span>
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
                    <i class="fa-solid fa-calendar-check" style="color: var(--color-teal);"></i>
                    ระบบจัดการตารางนัดหมาย
                </h5>
                <p>บันทึก ติดตาม จัดการสถานะ และกำหนดการนัดพบแพทย์ของผู้ป่วย</p>
            </div>
        </div>

        <div class="admin-profile">
            <div class="admin-avatar">
                <i class="fa-solid fa-user-doctor"></i>
            </div>
            <div class="admin-info">
                <strong>ผู้ดูแลระบบ</strong>
                <small>Doctor / Clinic Admin</small>
            </div>
        </div>
    </header>

    <!-- Page Content Wrapper -->
    <div class="content-wrapper">

        <!-- Flash Toast Notification -->
        <?php if (isset($_GET['msg']) || isset($_GET['error'])): ?>
            <?php
            $isError = isset($_GET['error']);
            $msgType = $isError ? $_GET['error'] : $_GET['msg'];
            $toastTheme = '';
            $toastIcon = 'fa-circle-check';
            $toastTitle = 'ทำรายการสำเร็จ';
            $toastText = '';

            if ($msgType === 'added' || $msgType === 'New appointment created successfully') {
                $toastText = 'เพิ่มตารางการนัดหมายใหม่เรียบร้อยแล้ว';
            } elseif ($msgType === 'updated' || $msgType === 'Appointment updated successfully') {
                $toastTheme = 'toast-update';
                $toastIcon = 'fa-pen-to-square';
                $toastTitle = 'อัปเดตข้อมูลสำเร็จ';
                $toastText = 'บันทึกการแก้ไขข้อมูลการนัดหมายเรียบร้อยแล้ว';
            } elseif ($msgType === 'deleted' || $msgType === 'Appointment deleted successfully') {
                $toastTheme = 'toast-delete';
                $toastIcon = 'fa-trash-can';
                $toastTitle = 'ลบข้อมูลสำเร็จ';
                $toastText = 'ลบรายการนัดหมายออกจากระบบเรียบร้อยแล้ว';
            } elseif ($msgType === 'not_found') {
                $toastTheme = 'toast-error';
                $toastIcon = 'fa-circle-exclamation';
                $toastTitle = 'ไม่พบข้อมูล';
                $toastText = 'ไม่พบข้อมูลรหัสนัดหมายที่ระบุในระบบ';
            } elseif ($msgType === 'duplicate') {
                $toastTheme = 'toast-error';
                $toastIcon = 'fa-circle-xmark';
                $toastTitle = 'รหัสนัดหมายซ้ำ';
                $toastText = 'รหัส Appointment ID นี้มีอยู่ในระบบแล้ว กรุณาลองใหม่อีกครั้ง';
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
                    <i class="fa-solid fa-calendar-days" style="color: var(--color-mint);"></i>
                    ตารางและประวัติการนัดหมายผู้ป่วย
                </h2>
                <p>ตรวจสอบ ติดตามสถานะ ยืนยัน เลื่อนนัด หรือจัดการกำหนดการพบแพทย์</p>
            </div>

            <div class="hero-action-buttons">
                <a href="add-appointment.php" class="btn-add-appointment">
                    <i class="fa-solid fa-calendar-plus"></i>
                    <span>เพิ่มการนัดหมายใหม่</span>
                </a>
            </div>
        </section>

        <!-- Summary Metric Cards -->
        <section class="row g-3 mb-4">

            <!-- Card 1: Total Appointments -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card" style="--stat-theme-color: #294549;">
                    <div class="stat-top">
                        <div class="stat-label-group">
                            <h6>การนัดหมายทั้งหมด</h6>
                            <small>Total Appointments</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(41, 69, 73, 0.12); color: #294549;">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?= number_format($totalAppointments) ?>
                    </div>
                    <div class="stat-description">
                        <i class="fa-solid fa-list-ol"></i>
                        <span>รายการในระบบ</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Confirmed -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card" style="--stat-theme-color: #2ed573;">
                    <div class="stat-top">
                        <div class="stat-label-group">
                            <h6>ยืนยันแล้ว</h6>
                            <small>Confirmed</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(46, 213, 115, 0.15); color: #20bf6b;">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                    </div>
                    <div class="stat-value" style="color: #20bf6b;">
                        <?= number_format($totalConfirmed) ?>
                    </div>
                    <div class="stat-description">
                        <i class="fa-solid fa-user-check"></i>
                        <span>พร้อมเข้ารับการตรวจ</span>
                    </div>
                </div>
            </div>

            <!-- Card 3: Pending -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card" style="--stat-theme-color: #f39c12;">
                    <div class="stat-top">
                        <div class="stat-label-group">
                            <h6>รอยืนยัน</h6>
                            <small>Pending</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(243, 156, 18, 0.15); color: #d35400;">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value" style="color: #d35400;">
                        <?= number_format($totalPending) ?>
                    </div>
                    <div class="stat-description">
                        <i class="fa-solid fa-hourglass-half"></i>
                        <span>รอผู้ป่วยหรือแพทย์ยืนยัน</span>
                    </div>
                </div>
            </div>

            <!-- Card 4: Rescheduled -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card" style="--stat-theme-color: #4E7F90;">
                    <div class="stat-top">
                        <div class="stat-label-group">
                            <h6>เลื่อนนัดหมาย</h6>
                            <small>Rescheduled</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(78, 127, 144, 0.15); color: #4E7F90;">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                    </div>
                    <div class="stat-value" style="color: var(--color-secondary);">
                        <?= number_format($totalRescheduled) ?>
                    </div>
                    <div class="stat-description">
                        <i class="fa-solid fa-arrows-rotate"></i>
                        <span>เปลี่ยนแปลงกำหนดเวลา</span>
                    </div>
                </div>
            </div>

        </section>

        <!-- Main Appointment Table Panel -->
        <section class="panel">

            <div class="panel-header">
                <div class="panel-header-title">
                    <h5>
                        <i class="fa-solid fa-list-ul" style="color: var(--color-secondary);"></i>
                        ตารางรายชื่อนัดหมายผู้ป่วย
                    </h5>
                    <p>แสดงวัน เวลา ข้อมูลผู้ป่วย และสถานะการนัดหมายทั้งหมด</p>
                </div>

                <!-- Search & Filters -->
                <form method="GET" action="appointment.php" class="filter-controls-wrap">
                    <div class="search-container">
                        <div class="search-input-wrapper">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input
                                type="text"
                                name="search"
                                placeholder="ค้นหา รหัสนัดหมาย, ชื่อผู้ป่วย, โรค..."
                                value="<?= e($search) ?>"
                            >
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <select name="status" class="filter-select" onchange="this.form.submit()">
                        <option value="">-- ทุกสถานะ --</option>
                        <option value="ยืนยัน" <?= $filterStatus === 'ยืนยัน' ? 'selected' : '' ?>>ยืนยันแล้ว</option>
                        <option value="รอยืนยัน" <?= $filterStatus === 'รอยืนยัน' ? 'selected' : '' ?>>รอยืนยัน</option>
                        <option value="เลื่อน" <?= $filterStatus === 'เลื่อน' ? 'selected' : '' ?>>เลื่อนนัด</option>
                    </select>

                    <!-- Date Filter -->
                    <select name="date_filter" class="filter-select" onchange="this.form.submit()">
                        <option value="">-- ทุกช่วงเวลา --</option>
                        <option value="today" <?= $filterDate === 'today' ? 'selected' : '' ?>>นัดหมายวันนี้</option>
                        <option value="upcoming" <?= $filterDate === 'upcoming' ? 'selected' : '' ?>>นัดหมายตั้งแต่วันนี้เป็นต้นไป</option>
                    </select>

                    <button type="submit" class="btn-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span>ค้นหา</span>
                    </button>

                    <?php if ($search !== '' || $filterStatus !== '' || $filterDate !== ''): ?>
                        <a href="appointment.php" class="btn-reset" title="ล้างการค้นหา">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Responsive Table -->
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>รหัสนัดหมาย</th>
                            <th>ข้อมูลผู้ป่วย</th>
                            <th>โรค / อาการที่วินิจฉัย</th>
                            <th>วันที่นัดหมาย</th>
                            <th>เวลานัดหมาย</th>
                            <th>สถานะ</th>
                            <th class="text-center">การจัดการ</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($appointments) > 0): ?>
                            <?php foreach ($appointments as $apt): ?>
                                <?php
                                $status = trim($apt['status']);
                                $statusClass = 'status-pending';
                                $statusIcon = 'fa-clock';

                                if ($status === 'ยืนยัน' || $status === 'Confirmed') {
                                    $statusClass = 'status-confirmed';
                                    $statusIcon = 'fa-circle-check';
                                } elseif ($status === 'เลื่อน' || $status === 'Rescheduled') {
                                    $statusClass = 'status-rescheduled';
                                    $statusIcon = 'fa-clock-rotate-left';
                                } elseif ($status === 'ยกเลิก' || $status === 'Cancelled') {
                                    $statusClass = 'status-cancelled';
                                    $statusIcon = 'fa-circle-xmark';
                                }

                                $timeFormatted = substr($apt['appointment_time'], 0, 5) . ' น.';
                                $isMale = ($apt['gender'] === 'ชาย');
                                ?>
                                <tr>
                                    <!-- Appointment ID -->
                                    <td>
                                        <span class="appointment-id-badge">
                                            <?= e($apt['appointment_id']) ?>
                                        </span>
                                    </td>

                                    <!-- Patient Info -->
                                    <td>
                                        <div class="patient-info-block">
                                            <div class="patient-avatar-box">
                                                <i class="fa-solid <?= $isMale ? 'fa-mars' : 'fa-venus' ?>"></i>
                                            </div>
                                            <div>
                                                <span class="patient-name-text">
                                                    <?= e($apt['user_name'] ?? 'ไม่พบชื่อผู้ใช้ (ID: ' . $apt['user_id'] . ')') ?>
                                                </span>
                                                <div class="patient-meta-sub">
                                                    <span>User ID: #<?= e($apt['user_id']) ?></span>
                                                    <span>&bull;</span>
                                                    <span><?= e($apt['gender'] ?? '-') ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Disease -->
                                    <td>
                                        <?php if (!empty($apt['disease'])): ?>
                                            <span class="disease-tag-sm">
                                                <i class="fa-solid fa-stethoscope me-1"></i><?= e($apt['disease']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Appointment Date -->
                                    <td>
                                        <i class="fa-regular fa-calendar me-1" style="color: var(--color-teal);"></i>
                                        <span class="fw-semibold"><?= e($apt['appointment_date']) ?></span>
                                    </td>

                                    <!-- Appointment Time -->
                                    <td>
                                        <i class="fa-regular fa-clock me-1" style="color: var(--color-secondary);"></i>
                                        <span class="fw-semibold"><?= e($timeFormatted) ?></span>
                                    </td>

                                    <!-- Status Badge -->
                                    <td>
                                        <span class="status-badge <?= $statusClass ?>">
                                            <i class="fa-solid <?= $statusIcon ?>"></i>
                                            <?= e($apt['status']) ?>
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td>
                                        <div class="action-buttons justify-content-center">
                                            <!-- View Details Modal Trigger -->
                                            <button type="button"
                                                    class="btn-action btn-view"
                                                    onclick="viewAppointmentDetails(<?= htmlspecialchars(json_encode($apt), ENT_QUOTES, 'UTF-8') ?>)"
                                                    title="ดูรายละเอียดการนัดหมาย">
                                                <i class="fa-solid fa-eye"></i>
                                                <span>ดู</span>
                                            </button>

                                            <!-- Edit Button -->
                                            <a href="edit-appointment.php?appointment_id=<?= urlencode($apt['appointment_id']) ?>"
                                               class="btn-action btn-edit"
                                               title="แก้ไขข้อมูลการนัดหมาย">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                                <span>แก้ไข</span>
                                            </a>

                                            <!-- Delete Button -->
                                            <button type="button"
                                                    class="btn-action btn-delete"
                                                    onclick="confirmDeleteAppointment('<?= e($apt['appointment_id']) ?>', '<?= e(addslashes($apt['user_name'] ?? 'ผู้ใช้ ID: ' . $apt['user_id'])) ?>', '<?= e($apt['appointment_date']) ?>')"
                                                    title="ลบรายการนัดหมาย">
                                                <i class="fa-solid fa-trash-can"></i>
                                                <span>ลบ</span>
                                            </button>
                                        </div>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <i class="fa-solid fa-calendar-xmark d-block"></i>
                                    <h6>ไม่พบข้อมูลการนัดหมาย</h6>
                                    <p class="mb-3">
                                        <?= ($search !== '' || $filterStatus !== '' || $filterDate !== '') ? 'ไม่มีข้อมูลที่ตรงกับเงื่อนไขการค้นหา' : 'ยังไม่มีตารางนัดหมายในระบบ' ?>
                                    </p>
                                    <?php if ($search !== '' || $filterStatus !== '' || $filterDate !== ''): ?>
                                        <a href="appointment.php" class="btn btn-sm btn-outline-primary" style="border-radius: 20px;">
                                            <i class="fa-solid fa-rotate-left me-1"></i> ดูรายการนัดหมายทั้งหมด
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
                    แสดงข้อมูลทั้งหมด <strong><?= count($appointments) ?></strong> รายการ
                    <?php if ($search !== '' || $filterStatus !== '' || $filterDate !== ''): ?>
                        (จากผลการค้นหา)
                    <?php endif; ?>
                </div>
                <div class="text-muted" style="font-size: 11.5px;">
                    <i class="fa-solid fa-circle-info me-1"></i> ตารางนัดหมายเชื่อมโยงกับฐานข้อมูลผู้ใช้ (Users)
                </div>
            </div>

        </section>

    </div>

    <!-- Footer -->
    <footer class="footer">
        &copy; <?= date('Y') ?> <strong>Medisync</strong>. Personal Medicine Management System.
    </footer>

</main>

<!-- Modal: View Appointment Details -->
<div class="modal fade" id="appointmentDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-calendar-check" style="color: var(--color-teal);"></i>
                    <span>รายละเอียดการนัดหมาย</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center pb-3 border-bottom mb-3">
                    <span class="appointment-id-badge fs-6 mb-2" id="modalAptId"></span>
                    <h5 class="fw-bold mb-1" id="modalUserName" style="color: var(--color-primary);"></h5>
                    <div class="text-muted small">
                        รหัสผู้ป่วย: #<span id="modalUserId"></span> &bull; เพศ: <span id="modalGender"></span>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="text-muted small fw-semibold">วันที่นัดหมาย</label>
                        <div class="fw-bold text-dark" id="modalDate"></div>
                    </div>
                    <div class="col-6">
                        <label class="text-muted small fw-semibold">เวลานัดหมาย</label>
                        <div class="fw-bold text-dark" id="modalTime"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="text-muted small fw-semibold">สถานะการนัดหมาย</label>
                    <div id="modalStatusWrap"></div>
                </div>

                <div class="p-3 rounded-3 mb-3" style="background: rgba(187, 215, 218, 0.25);">
                    <label class="fw-bold text-dark small mb-1 d-flex align-items-center gap-1">
                        <i class="fa-solid fa-stethoscope text-primary"></i> โรค / การวินิจฉัยของผู้ป่วย
                    </label>
                    <div id="modalDisease" style="font-size: 13.5px; color: var(--color-primary);"></div>
                </div>

                <div class="p-3 rounded-3" style="background: rgba(144, 194, 183, 0.2);">
                    <label class="fw-bold text-dark small mb-1 d-flex align-items-center gap-1">
                        <i class="fa-solid fa-pills text-teal"></i> ยาที่แนะนำ / รับประทาน
                    </label>
                    <div id="modalMedicine" style="font-size: 13.5px; color: var(--color-primary);"></div>
                </div>
            </div>
            <div class="modal-footer">
                <a id="modalEditLink" href="#" class="btn btn-sm text-white" style="background: var(--color-secondary); border-radius: 10px; padding: 8px 18px;">
                    <i class="fa-solid fa-pen-to-square me-1"></i> แก้ไขการนัดหมาย
                </a>
                <a id="modalUserDetailLink" href="#" class="btn btn-sm text-white" style="background: var(--color-primary); border-radius: 10px; padding: 8px 18px;">
                    <i class="fa-solid fa-file-medical me-1"></i> ดูประวัติผู้ป่วย
                </a>
                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal" style="border-radius: 10px; padding: 8px 16px;">
                    ปิด
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Delete Confirmation -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: rgba(220, 53, 69, 0.08); border-bottom-color: rgba(220, 53, 69, 0.2);">
                <h5 class="modal-title text-danger">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> ยืนยันการลบการนัดหมาย
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(220, 53, 69, 0.12); color: #dc3545; display: inline-flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 16px;">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">คุณแน่ใจหรือไม่ว่าต้องการลบรายการนัดหมายนี้?</h5>
                <p class="text-muted mb-3" style="font-size: 13.5px;">
                    รหัสการนัดหมาย: <strong id="delAptId" class="text-dark"></strong><br>
                    ผู้ป่วย: <span id="delPatientName" class="fw-semibold"></span><br>
                    กำหนดวันที่: <span id="delAptDate" class="badge bg-secondary"></span>
                </p>
                <p class="text-danger small mb-0">การกระทำนี้จะลบรายการนัดหมายออกจากระบบทันที</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal" style="border-radius: 10px;">
                    ยกเลิก
                </button>
                <a id="delConfirmLink" href="#" class="btn btn-danger px-4" style="border-radius: 10px;">
                    <i class="fa-solid fa-trash-can me-1"></i> ยืนยันการลบ
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
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

    // Modal View Details
    function viewAppointmentDetails(apt) {
        document.getElementById('modalAptId').innerText = apt.appointment_id;
        document.getElementById('modalUserName').innerText = apt.user_name || 'ไม่ระบุชื่อ';
        document.getElementById('modalUserId').innerText = apt.user_id;
        document.getElementById('modalGender').innerText = apt.gender || '-';
        document.getElementById('modalDate').innerText = apt.appointment_date;
        document.getElementById('modalTime').innerText = apt.appointment_time.substring(0, 5) + ' น.';
        document.getElementById('modalDisease').innerText = apt.disease || 'ไม่ระบุ';
        document.getElementById('modalMedicine').innerText = apt.medicine || 'ไม่ระบุ';

        let statusClass = 'status-pending';
        let statusIcon = 'fa-clock';
        if (apt.status === 'ยืนยัน' || apt.status === 'Confirmed') {
            statusClass = 'status-confirmed';
            statusIcon = 'fa-circle-check';
        } else if (apt.status === 'เลื่อน' || apt.status === 'Rescheduled') {
            statusClass = 'status-rescheduled';
            statusIcon = 'fa-clock-rotate-left';
        } else if (apt.status === 'ยกเลิก' || apt.status === 'Cancelled') {
            statusClass = 'status-cancelled';
            statusIcon = 'fa-circle-xmark';
        }

        document.getElementById('modalStatusWrap').innerHTML = `
            <span class="status-badge ${statusClass}">
                <i class="fa-solid ${statusIcon}"></i> ${apt.status}
            </span>
        `;

        document.getElementById('modalEditLink').href = 'edit-appointment.php?appointment_id=' + encodeURIComponent(apt.appointment_id);
        document.getElementById('modalUserDetailLink').href = '../user-page/user-details.php?id=' + encodeURIComponent(apt.user_id);

        const modal = new bootstrap.Modal(document.getElementById('appointmentDetailsModal'));
        modal.show();
    }

    // Modal Delete Confirm
    function confirmDeleteAppointment(aptId, patientName, aptDate) {
        document.getElementById('delAptId').innerText = aptId;
        document.getElementById('delPatientName').innerText = patientName;
        document.getElementById('delAptDate').innerText = aptDate;
        document.getElementById('delConfirmLink').href = 'delete-appointment.php?appointment_id=' + encodeURIComponent(aptId);

        const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        modal.show();
    }
</script>

</body>
</html>

