<?php
$pageTitle = "Medicines";
require_once '../db_conn.php';

// ฟังก์ชัน Escape HTML เพื่อความปลอดภัย
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// รับค่าคำค้นหาและตัวกรอง
$search = trim($_GET['search'] ?? '');
$filterForm = trim($_GET['dosageform'] ?? '');

// ฟังก์ชันนับจำนวนข้อมูลสำหรับ Summary Cards
function countMedicines($conn) {
    $sql = "SELECT COUNT(*) AS total FROM `medicines`";
    $result = $conn->query($sql);
    return $result ? (int)$result->fetch_assoc()['total'] : 0;
}

function countDosageForms($conn) {
    $sql = "SELECT COUNT(DISTINCT `dosageform`) AS total FROM `medicines` WHERE `dosageform` != ''";
    $result = $conn->query($sql);
    return $result ? (int)$result->fetch_assoc()['total'] : 0;
}

function countPrescribedMedicines($conn) {
    $sql = "SELECT COUNT(DISTINCT `tpu_code`) AS total FROM `schedules`";
    $result = $conn->query($sql);
    return $result ? (int)$result->fetch_assoc()['total'] : 0;
}

$totalMedicines = countMedicines($conn);
$totalDosageForms = countDosageForms($conn);
$totalPrescribed = countPrescribedMedicines($conn);

// ดึงรายการ Dosage Forms ทั้งหมดสำหรับ Dropdown Filter
$dosageFormsList = [];
$formQuery = $conn->query("SELECT DISTINCT `dosageform` FROM `medicines` WHERE `dosageform` IS NOT NULL AND `dosageform` != '' ORDER BY `dosageform` ASC");
if ($formQuery) {
    while ($row = $formQuery->fetch_assoc()) {
        $dosageFormsList[] = $row['dosageform'];
    }
}

// สร้าง SQL Query ดึงรายการยา
$sql = "
    SELECT 
        m.tpu_code,
        m.trade_name,
        m.active_ingredient,
        m.strength,
        m.dosageform,
        m.indications,
        m.side_effects,
        m.precautions,
        m.image_url,
        (SELECT COUNT(*) FROM schedules s WHERE s.tpu_code = m.tpu_code) AS schedule_usage_count
    FROM medicines m
    WHERE 1=1
";

$params = [];
$types = "";

if ($search !== '') {
    $sql .= " AND (
        CAST(m.tpu_code AS CHAR) LIKE ?
        OR m.trade_name LIKE ?
        OR m.active_ingredient LIKE ?
        OR m.strength LIKE ?
        OR m.dosageform LIKE ?
        OR m.indications LIKE ?
        OR m.side_effects LIKE ?
        OR m.precautions LIKE ?
    )";
    $keyword = "%{$search}%";
    for ($i = 0; $i < 8; $i++) {
        $params[] = $keyword;
        $types .= "s";
    }
}

if ($filterForm !== '') {
    $sql .= " AND m.dosageform = ?";
    $params[] = $filterForm;
    $types .= "s";
}

$sql .= " ORDER BY m.trade_name ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$medicines = [];
while ($row = $result->fetch_assoc()) {
    $medicines[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การจัดการรายการยา | Medisync Admin</title>

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
            /* สีตาม Theme Medisync */
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

        /* ===== PAGE BANNER ===== */
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

        .btn-add-medicine {
            background: var(--color-primary);
            color: var(--color-white);
            font-weight: 600;
            padding: 11px 22px;
            border-radius: 12px;
            font-size: 13.5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.22s ease;
            box-shadow: 0 4px 14px rgba(41, 69, 73, 0.2);
        }

        .btn-add-medicine:hover {
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
            font-size: 32px;
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

        /* ===== SEARCH & FILTER ===== */
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
            min-width: 180px;
            max-width: 280px;
            flex: 1 1 auto;
        }

        .search-input-wrapper {
            position: relative;
            flex-grow: 1;
        }

        .search-input-wrapper i {
            position: absolute;
            left: 13px;
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
            min-width: 140px;
            max-width: 190px;
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

        .tpu-badge {
            color: var(--color-primary);
            font-weight: 700;
            background: rgba(78, 127, 144, 0.14);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            display: inline-block;
            font-family: monospace;
            border: 1px solid rgba(78, 127, 144, 0.25);
        }

        /* Medicine Image Thumb */
        .med-thumb-wrap {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: rgba(187, 215, 218, 0.3);
            border: 1px solid rgba(74, 128, 126, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(41, 69, 73, 0.08);
            position: relative;
        }

        .med-thumb-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.2s ease;
        }

        .med-thumb-wrap:hover .med-thumb-img {
            transform: scale(1.12);
        }

        .med-thumb-fallback {
            color: var(--color-teal);
            font-size: 20px;
        }

        .med-title-block {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .med-trade-name {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--color-primary);
            line-height: 1.25;
            margin-bottom: 2px;
        }

        .med-sub-ingredient {
            font-size: 11.5px;
            color: var(--text-muted);
            line-height: 1.3;
        }

        .dosage-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(144, 194, 183, 0.25);
            color: #175249;
            padding: 4px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid rgba(144, 194, 183, 0.5);
            white-space: nowrap;
        }

        .strength-badge {
            display: inline-block;
            background: rgba(78, 127, 144, 0.12);
            color: var(--color-secondary);
            padding: 2px 7px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 220px;
            line-height: 1.4;
            font-size: 12px;
        }

        .schedule-count-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10.5px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 10px;
            background: rgba(78, 127, 144, 0.12);
            color: var(--color-secondary);
            margin-top: 3px;
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

        /* Floating White Toast Notification */
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
                max-width: 260px;
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

            .btn-add-medicine {
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

    <!-- Sidebar Status Footer -->
    <div class="sidebar-footer-card">
        <div class="system-status-indicator">
            <span class="status-dot"></span>
            <span>ระบบออนไลน์พร้อมใช้งาน</span>
        </div>
        <div style="font-size: 11px; color: var(--color-ice); line-height: 1.5;">
            Medisync Admin Panel<br>
            <span style="opacity: 0.8;">คลังรายการยา & เภสัชกรรม</span>
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
                    <i class="fa-solid fa-capsules" style="color: var(--color-teal);"></i>
                    ระบบจัดการรายการยา
                </h5>
                <p>ข้อมูลยา สรรพคุณ รูปแบบยา ขนาดยา และข้อควรระวังการใช้งาน</p>
            </div>
        </div>

        <div class="admin-profile">
            <div class="admin-avatar">
                <i class="fa-solid fa-prescription-bottle-medical"></i>
            </div>
            <div class="admin-info">
                <strong>ผู้ดูแลระบบ</strong>
                <small>Pharmacy Admin</small>
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

            if ($msgType === 'added' || $msgType === 'New record created successfully') {
                $toastText = 'เพิ่มรายการยาใหม่เข้าสู่คลังเรียบร้อยแล้ว';
            } elseif ($msgType === 'updated' || $msgType === 'Data Updated successfully') {
                $toastTheme = 'toast-update';
                $toastIcon = 'fa-pen-to-square';
                $toastTitle = 'อัปเดตข้อมูลสำเร็จ';
                $toastText = 'บันทึกการแก้ไขข้อมูลยาเรียบร้อยแล้ว';
            } elseif ($msgType === 'deleted' || $msgType === 'Record Delete Successfully') {
                $toastTheme = 'toast-delete';
                $toastIcon = 'fa-trash-can';
                $toastTitle = 'ลบข้อมูลสำเร็จ';
                $toastText = 'ลบรายการยาออกจากระบบเรียบร้อยแล้ว';
            } elseif ($msgType === 'referenced' || $msgType === 'cannot_delete_referenced') {
                $toastTheme = 'toast-error';
                $toastIcon = 'fa-triangle-exclamation';
                $toastTitle = 'ไม่สามารถลบรายการยานี้ได้';
                $medName = isset($_GET['name']) ? e($_GET['name']) : 'ยานี้';
                $toastText = "เนื่องจาก {$medName} มีประวัติการผูกอยู่ในตารางเวลาการทานยาของผู้ป่วย (Schedules)";
            } elseif ($msgType === 'duplicate') {
                $toastTheme = 'toast-error';
                $toastIcon = 'fa-circle-xmark';
                $toastTitle = 'เกิดข้อผิดพลาด';
                $toastText = 'รหัส TPU นี้มีอยู่ในระบบแล้ว กรุณาใช้รหัสอื่น';
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
                    setTimeout(dismissToastAlert, 5000);
                </script>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Page Header Banner -->
        <section class="page-banner">
            <div class="page-banner-title">
                <h2>
                    <i class="fa-solid fa-pills" style="color: var(--color-secondary);"></i>
                    คลังข้อมูลเวชภัณฑ์และรายการยา
                </h2>
                <p>บันทึก ค้นหา แก้ไข และจัดการรายการยาทั้งหมดในระบบ Medisync</p>
            </div>

            <a href="add-medicine.php" class="btn-add-medicine">
                <i class="fa-solid fa-plus-circle"></i>
                <span>เพิ่มยาใหม่</span>
            </a>
        </section>

        <!-- Summary Metric Cards -->
        <section class="row g-3 mb-4">

            <!-- Card 1: Total Medicines -->
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="stat-card" style="--stat-theme-color: #294549;">
                    <div class="stat-top">
                        <div class="stat-label-group">
                            <h6>รายการยาทั้งหมด</h6>
                            <small>Total Medicines</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(41, 69, 73, 0.12); color: #294549;">
                            <i class="fa-solid fa-prescription-bottle"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?= number_format($totalMedicines) ?>
                    </div>
                    <div class="stat-description">
                        <i class="fa-solid fa-check-double"></i>
                        <span>ลงทะเบียนในคลังยาแล้ว</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Distinct Dosage Forms -->
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="stat-card" style="--stat-theme-color: #4E7F90;">
                    <div class="stat-top">
                        <div class="stat-label-group">
                            <h6>รูปแบบยาที่หลากหลาย</h6>
                            <small>Dosage Forms</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(78, 127, 144, 0.14); color: #4E7F90;">
                            <i class="fa-solid fa-shapes"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?= number_format($totalDosageForms) ?>
                    </div>
                    <div class="stat-description">
                        <i class="fa-solid fa-tablets"></i>
                        <span>เม็ด แคปซูล ยาน้ำ ฯลฯ</span>
                    </div>
                </div>
            </div>

            <!-- Card 3: Prescribed in Schedules -->
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="stat-card" style="--stat-theme-color: #4A807E;">
                    <div class="stat-top">
                        <div class="stat-label-group">
                            <h6>ยาที่มีการจ่ายในตารางทาน</h6>
                            <small>Active in Schedules</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(74, 128, 126, 0.14); color: #4A807E;">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                    </div>
                    <div class="stat-value">
                        <?= number_format($totalPrescribed) ?>
                    </div>
                    <div class="stat-description">
                        <i class="fa-solid fa-user-clock"></i>
                        <span>มีผู้ป่วยรับประทานอยู่ในปัจจุบัน</span>
                    </div>
                </div>
            </div>

        </section>

        <!-- Main Medicine Table Panel -->
        <section class="panel">

            <div class="panel-header">
                <div class="panel-header-title">
                    <h5>
                        <i class="fa-solid fa-list-check" style="color: var(--color-secondary);"></i>
                        ตารางรายการยาในระบบ
                    </h5>
                    <p>แสดงรายละเอียดตัวยาสำคัญ ความแรง รูปแบบ สรรพคุณ และการจัดการ</p>
                </div>

                <!-- Search & Filters -->
                <form method="GET" action="medicine.php" class="filter-controls-wrap">
                    <div class="search-container">
                        <div class="search-input-wrapper">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input
                                type="text"
                                name="search"
                                placeholder="ค้นหา TPU, ชื่อยา, ตัวยาสำคัญ, สรรพคุณ..."
                                value="<?= e($search) ?>"
                            >
                        </div>
                    </div>

                    <!-- Dosage Form Filter -->
                    <select name="dosageform" class="filter-select" onchange="this.form.submit()">
                        <option value="">-- ทุกรูปแบบยา --</option>
                        <?php foreach ($dosageFormsList as $dform): ?>
                            <option value="<?= e($dform) ?>" <?= $filterForm === $dform ? 'selected' : '' ?>>
                                <?= e($dform) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span>ค้นหา</span>
                    </button>

                    <?php if ($search !== '' || $filterForm !== ''): ?>
                        <a href="medicine.php" class="btn-reset" title="ล้างการค้นหา">
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
                            <th>รหัส TPU</th>
                            <th>รูปยา / ข้อมูลยา</th>
                            <th>ตัวยาสำคัญ & ความแรง</th>
                            <th>รูปแบบยา</th>
                            <th>ข้อบ่งใช้ / สรรพคุณ</th>
                            <th>ผลข้างเคียง & ข้อควรระวัง</th>
                            <th class="text-center">การจัดการ</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($medicines) > 0): ?>
                            <?php foreach ($medicines as $med): ?>
                                <tr>
                                    <!-- TPU Code -->
                                    <td>
                                        <span class="tpu-badge">
                                            <?= e($med['tpu_code']) ?>
                                        </span>
                                        <?php if ((int)$med['schedule_usage_count'] > 0): ?>
                                            <div class="schedule-count-pill" title="ถูกใช้งานในตารางทานยา <?= e($med['schedule_usage_count']) ?> รายการ">
                                                <i class="fa-solid fa-bell"></i>
                                                <?= e($med['schedule_usage_count']) ?> คนไข้
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Medicine Image & Trade Name -->
                                    <td>
                                        <div class="med-title-block">
                                            <div class="med-thumb-wrap">
                                                <?php if (!empty($med['image_url'])): ?>
                                                    <img src="<?= e($med['image_url']) ?>"
                                                         alt="<?= e($med['trade_name']) ?>"
                                                         class="med-thumb-img"
                                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <div class="med-thumb-fallback" style="display: none;">
                                                        <i class="fa-solid fa-pills"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="med-thumb-fallback">
                                                        <i class="fa-solid fa-pills"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="med-trade-name"><?= e($med['trade_name']) ?></div>
                                                <div class="med-sub-ingredient text-truncate-2"><?= e($med['active_ingredient']) ?></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Active Ingredient & Strength -->
                                    <td>
                                        <div>
                                            <span class="strength-badge">
                                                <i class="fa-solid fa-bolt me-1"></i><?= e($med['strength']) ?>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Dosage Form -->
                                    <td>
                                        <span class="dosage-badge">
                                            <i class="fa-solid fa-cube"></i>
                                            <?= e($med['dosageform']) ?>
                                        </span>
                                    </td>

                                    <!-- Indications -->
                                    <td>
                                        <div class="text-truncate-2" title="<?= e($med['indications']) ?>">
                                            <?= e($med['indications']) ?>
                                        </div>
                                    </td>

                                    <!-- Side Effects & Precautions -->
                                    <td>
                                        <div class="text-truncate-2" title="ผลข้างเคียง: <?= e($med['side_effects']) ?> | ข้อควรระวัง: <?= e($med['precautions']) ?>">
                                            <small class="d-block text-muted"><strong>ผลข้างเคียง:</strong> <?= e($med['side_effects']) ?></small>
                                            <small class="d-block text-muted"><strong>ระวัง:</strong> <?= e($med['precautions']) ?></small>
                                        </div>
                                    </td>

                                    <!-- Actions -->
                                    <td>
                                        <div class="action-buttons justify-content-center">
                                            <!-- View Details Modal Trigger -->
                                            <button type="button"
                                                    class="btn-action btn-view"
                                                    onclick="viewMedicineDetails(<?= htmlspecialchars(json_encode($med), ENT_QUOTES, 'UTF-8') ?>)"
                                                    title="ดูรายละเอียดครบถ้วน">
                                                <i class="fa-solid fa-eye"></i>
                                                <span>ดู</span>
                                            </button>

                                            <!-- Edit Button -->
                                            <a href="edit-medicine.php?tpu_code=<?= urlencode($med['tpu_code']) ?>"
                                               class="btn-action btn-edit"
                                               title="แก้ไขข้อมูลยา">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                                <span>แก้ไข</span>
                                            </a>

                                            <!-- Delete Button -->
                                            <button type="button"
                                                    class="btn-action btn-delete"
                                                    onclick="confirmDeleteMedicine('<?= e($med['tpu_code']) ?>', '<?= e(addslashes($med['trade_name'])) ?>', <?= (int)$med['schedule_usage_count'] ?>)"
                                                    title="ลบรายการยา">
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
                                    <i class="fa-solid fa-box-open d-block"></i>
                                    <h6>ไม่พบข้อมูลยาที่ค้นหา</h6>
                                    <p class="mb-3">
                                        <?= ($search !== '' || $filterForm !== '') ? 'ไม่มีข้อมูลที่ตรงกับเงื่อนไขการค้นหา' : 'ยังไม่มีข้อมูลยาในระบบ' ?>
                                    </p>
                                    <?php if ($search !== '' || $filterForm !== ''): ?>
                                        <a href="medicine.php" class="btn btn-sm btn-outline-primary" style="border-radius: 20px;">
                                            <i class="fa-solid fa-rotate-left me-1"></i> ดูรายการยาทั้งหมด
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
                    แสดงข้อมูลทั้งหมด <strong><?= count($medicines) ?></strong> รายการ
                    <?php if ($search !== '' || $filterForm !== ''): ?>
                        (จากผลการค้นหา)
                    <?php endif; ?>
                </div>
                <div class="text-muted" style="font-size: 11.5px;">
                    <i class="fa-solid fa-circle-info me-1"></i> รหัส TPU เป็นรหัสอ้างอิงมาตรฐานยา
                </div>
            </div>

        </section>

    </div>

    <!-- Footer -->
    <footer class="footer">
        &copy; <?= date('Y') ?> <strong>Medisync</strong>. Personal Medicine Management System.
    </footer>

</main>

<!-- Modal: View Medicine Details -->
<div class="modal fade" id="medicineDetailsModal" tabindex="-1" aria-labelledby="medModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="medModalTitle">
                    <i class="fa-solid fa-pills" style="color: var(--color-teal);"></i>
                    <span id="modalTradeName">รายละเอียดข้อมูลยา</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <!-- Image Column -->
                    <div class="col-12 col-md-5 text-center">
                        <div style="background: rgba(187, 215, 218, 0.25); border: 1px solid rgba(74, 128, 126, 0.2); border-radius: 16px; padding: 18px; min-height: 220px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            <img id="modalImg" src="" alt="Medicine Photo" style="max-width: 100%; max-height: 240px; object-fit: contain; border-radius: 12px; display: none;">
                            <div id="modalImgPlaceholder" style="font-size: 64px; color: var(--color-secondary); opacity: 0.5;">
                                <i class="fa-solid fa-capsules"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="badge" style="background: var(--color-primary); font-size: 12px; padding: 6px 12px; border-radius: 8px;">
                                รหัส TPU: <span id="modalTpuCode"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Details Column -->
                    <div class="col-12 col-md-7">
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold">ชื่อทางการค้า (Trade Name)</label>
                            <h4 id="modalHeadingTrade" class="fw-bold" style="color: var(--color-primary); margin: 0;"></h4>
                        </div>

                        <div class="mb-3">
                            <label class="text-muted small fw-semibold">ตัวยาสำคัญ (Active Ingredient)</label>
                            <div id="modalActiveIngredient" class="fw-semibold text-secondary" style="font-size: 14px;"></div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="text-muted small fw-semibold">ความแรง (Strength)</label>
                                <div id="modalStrength" class="fw-bold text-dark"></div>
                            </div>
                            <div class="col-6">
                                <label class="text-muted small fw-semibold">รูปแบบยา (Dosage Form)</label>
                                <div>
                                    <span id="modalDosageForm" class="dosage-badge"></span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 p-3 rounded-3" style="background: rgba(187, 215, 218, 0.2);">
                            <label class="fw-bold text-dark small mb-1 d-flex align-items-center gap-1">
                                <i class="fa-solid fa-heart-pulse text-danger"></i> สรรพคุณ / ข้อบ่งใช้ (Indications)
                            </label>
                            <div id="modalIndications" style="font-size: 13px; color: var(--color-primary);"></div>
                        </div>

                        <div class="mb-3 p-3 rounded-3" style="background: rgba(255, 193, 7, 0.12); border-left: 4px solid #ffc107;">
                            <label class="fw-bold text-dark small mb-1 d-flex align-items-center gap-1">
                                <i class="fa-solid fa-triangle-exclamation text-warning"></i> ผลข้างเคียง (Side Effects)
                            </label>
                            <div id="modalSideEffects" style="font-size: 13px; color: #664d03;"></div>
                        </div>

                        <div class="p-3 rounded-3" style="background: rgba(220, 53, 69, 0.08); border-left: 4px solid #dc3545;">
                            <label class="fw-bold text-dark small mb-1 d-flex align-items-center gap-1">
                                <i class="fa-solid fa-shield-halved text-danger"></i> ข้อควรระวัง (Precautions)
                            </label>
                            <div id="modalPrecautions" style="font-size: 13px; color: #842029;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a id="modalEditBtn" href="#" class="btn btn-sm text-white" style="background: var(--color-secondary); border-radius: 10px; padding: 8px 18px;">
                    <i class="fa-solid fa-pen-to-square me-1"></i> แก้ไขยานี้
                </a>
                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal" style="border-radius: 10px; padding: 8px 18px;">
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
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> ยืนยันการลบรายการยา
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(220, 53, 69, 0.12); color: #dc3545; display: inline-flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 16px;">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">คุณแน่ใจหรือไม่ว่าต้องการลบยานี้?</h5>
                <p class="text-muted mb-3" style="font-size: 13.5px;">
                    ชื่อยา: <strong id="delMedName" class="text-dark"></strong><br>
                    รหัส TPU: <span id="delTpuCode" class="badge bg-secondary"></span>
                </p>

                <div id="delWarningSchedule" style="display: none;" class="alert alert-warning text-start" role="alert">
                    <div class="fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i> คำเตือนสำคัญ:</div>
                    <div style="font-size: 12.5px;">
                        ยานี้กำลังถูกใช้งานอยู่ในตารางเวลาทานยาของผู้ป่วย <span id="delUsageCount" class="fw-bold"></span> รายการ<br>
                        หากยืนยันลบ ระบบจะลบตารางเวลาและประวัติบันทึกการทานยาที่เกี่ยวข้องของยานี้ออกด้วย
                    </div>
                </div>

                <p class="text-danger small mb-0">การกระทำนี้ไม่สามารถย้อนกลับได้</p>
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
    // Mobile Sidebar Toggle
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
    function viewMedicineDetails(med) {
        document.getElementById('modalTradeName').innerText = med.trade_name;
        document.getElementById('modalHeadingTrade').innerText = med.trade_name;
        document.getElementById('modalTpuCode').innerText = med.tpu_code;
        document.getElementById('modalActiveIngredient').innerText = med.active_ingredient;
        document.getElementById('modalStrength').innerText = med.strength;
        document.getElementById('modalDosageForm').innerText = med.dosageform;
        document.getElementById('modalIndications').innerText = med.indications || '-';
        document.getElementById('modalSideEffects').innerText = med.side_effects || '-';
        document.getElementById('modalPrecautions').innerText = med.precautions || '-';

        const modalImg = document.getElementById('modalImg');
        const modalImgPlaceholder = document.getElementById('modalImgPlaceholder');

        if (med.image_url && med.image_url.trim() !== '') {
            modalImg.src = med.image_url;
            modalImg.style.display = 'block';
            modalImgPlaceholder.style.display = 'none';
            modalImg.onerror = function() {
                modalImg.style.display = 'none';
                modalImgPlaceholder.style.display = 'block';
            };
        } else {
            modalImg.style.display = 'none';
            modalImgPlaceholder.style.display = 'block';
        }

        document.getElementById('modalEditBtn').href = 'edit-medicine.php?tpu_code=' + encodeURIComponent(med.tpu_code);

        const modal = new bootstrap.Modal(document.getElementById('medicineDetailsModal'));
        modal.show();
    }

    // Modal Delete Confirm
    function confirmDeleteMedicine(tpuCode, tradeName, usageCount) {
        document.getElementById('delMedName').innerText = tradeName;
        document.getElementById('delTpuCode').innerText = tpuCode;

        const warningDiv = document.getElementById('delWarningSchedule');
        const confirmBtn = document.getElementById('delConfirmLink');

        if (usageCount > 0) {
            document.getElementById('delUsageCount').innerText = usageCount;
            warningDiv.style.display = 'block';
            confirmBtn.href = 'delete-medicine.php?tpu_code=' + encodeURIComponent(tpuCode) + '&force=1';
        } else {
            warningDiv.style.display = 'none';
            confirmBtn.href = 'delete-medicine.php?tpu_code=' + encodeURIComponent(tpuCode);
        }

        const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        modal.show();
    }
</script>

</body>
</html>

