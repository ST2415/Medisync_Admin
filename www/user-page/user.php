<?php
$pageTitle = "Users";
require_once '../db_conn.php';

// ฟังก์ชัน Escape HTML เพื่อความปลอดภัย
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// รับค่าคำค้นหา
$search = trim($_GET['search'] ?? '');

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
";

if ($search !== '') {
    $sql .= "
        WHERE CAST(u.user_id AS CHAR) LIKE ?
            OR u.name LIKE ?
            OR u.gender LIKE ?
            OR u.symptoms LIKE ?
            OR u.causes LIKE ?
            OR u.disease LIKE ?
            OR u.medicine LIKE ?
    ";
}

$sql .= " ORDER BY u.user_id ASC";

$stmt = $conn->prepare($sql);

if ($search !== '') {
    $keyword = "%{$search}%";
    $stmt->bind_param(
        "sssssss",
        $keyword,
        $keyword,
        $keyword,
        $keyword,
        $keyword,
        $keyword,
        $keyword
    );
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
            font-size: 11.5px;
            color: var(--color-teal);
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(74, 128, 126, 0.1);
            padding: 4px 10px;
            border-radius: 20px;
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

        /* ===== SEARCH BOX ===== */
        .search-container {
            display: flex;
            gap: 8px;
            width: 100%;
            max-width: 440px;
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
            padding: 10px 14px 10px 36px;
            font-family: inherit;
            font-size: 12.5px;
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
            padding: 10px 18px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
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
            padding: 10px 14px;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
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
            color: var(--color-secondary);
            font-weight: 700;
            background: rgba(78, 127, 144, 0.1);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            display: inline-block;
        }

        .user-name-link {
            display: flex;
            align-items: center;
            gap: 10px;
            color: inherit;
            transition: transform 0.18s ease;
        }

        .user-name-link:hover {
            transform: translateX(4px);
        }

        .user-name-link:hover .user-name-text {
            color: var(--color-secondary);
            text-decoration: underline;
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

            .search-container {
                max-width: 100%;
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
                        <span>กำหนดเวลาแจ้งเตือน</span>
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
                        <span>รายการที่บันทึกแล้ว</span>
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

                <!-- Search Box -->
                <form method="GET" action="user.php" class="search-container">
                    <div class="search-input-wrapper">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input
                            type="text"
                            name="search"
                            placeholder="ค้นหา ID, ชื่อผู้ใช้, เพศ, อาการ, โรค, ยา..."
                            value="<?= e($search) ?>"
                        >
                    </div>

                    <button type="submit" class="btn-search">
                        <span>ค้นหา</span>
                    </button>

                    <?php if ($search !== ''): ?>
                        <a href="user.php" class="btn-reset" title="ล้างการค้นหา">
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
                            <th>ID</th>
                            <th>ชื่อผู้ใช้</th>
                            <th>เพศ</th>
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
                                $avatarClass = $isMale ? 'user-avatar-male' : 'user-avatar-female';
                                $genderClass = $isMale ? 'gender-male' : 'gender-female';
                                $genderIcon = $isMale ? 'fa-mars' : 'fa-venus';
                                ?>
                                <tr>
                                    <!-- User ID -->
                                    <td>
                                        <span class="user-id-badge">
                                            <?= e($user['user_id']) ?>
                                        </span>
                                    </td>

                                    <!-- User Name (กดตรงนี้เพื่อไปดูรายละเอียดผู้ป่วยใน user-details.php) -->
                                    <td>
                                        <a href="user-details.php?id=<?= urlencode($user['user_id']) ?>"
                                           class="user-name-link"
                                           title="คลิกเพื่อดูรายละเอียดผู้ป่วย ตารางเวลา และประวัติการทานยา">
                                            <span class="user-name-text">
                                                <?= e($user['name']) ?>
                                            </span>
                                            <i class="fa-solid fa-circle-chevron-right ms-1" style="font-size: 11px; color: var(--color-teal); opacity: 0.8;"></i>
                                        </a>
                                    </td>

                                    <!-- Gender -->
                                    <td>
                                        <span class="gender-pill <?= $genderClass ?>">
                                            <i class="fa-solid <?= $genderIcon ?>"></i>
                                            <?= e($user['gender']) ?>
                                        </span>
                                    </td>

                                    <!-- Date of Birth -->
                                    <td>
                                        <i class="fa-regular fa-calendar me-1" style="color: var(--color-teal); opacity: 0.8;"></i>
                                        <?= e($user['date_of_birth']) ?>
                                    </td>

                                    <!-- Symptoms -->
                                    <td>
                                        <?= e($user['symptoms']) ?>
                                    </td>

                                    <!-- Causes -->
                                    <td>
                                        <?= e($user['causes']) ?>
                                    </td>

                                    <!-- Diagnosis / Disease -->
                                    <td>
                                        <?= e($user['disease']) ?>
                                    </td>

                                    <!-- Medicine -->
                                    <td>
                                        <span class="medicine-pill">
                                            <i class="fa-solid fa-capsules"></i>
                                            <?= e($user['medicine']) ?>
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td>
                                        <div class="action-buttons justify-content-center">
                                            <a href="user-details.php?id=<?= urlencode($user['user_id']) ?>"
                                               class="btn-action btn-view-details"
                                               title="ดูประวัติการกินยาและตารางเวลา">
                                                <i class="fa-solid fa-file-medical"></i>
                                                <span>ดูข้อมูล</span>
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
                                <td colspan="10" class="empty-state">
                                    <i class="fa-solid fa-users-slash d-block"></i>
                                    <h6>ไม่พบข้อมูลผู้ใช้ที่ค้นหา</h6>
                                    <p class="mb-3">
                                        <?= $search !== '' ? 'ไม่มีข้อมูลที่ตรงกับคำค้นหา "' . e($search) . '"' : 'ยังไม่มีข้อมูลผู้ใช้ในระบบ' ?>
                                    </p>
                                    <?php if ($search !== ''): ?>
                                        <a href="user.php" class="btn btn-sm btn-outline-primary" style="border-radius: 20px;">
                                            <i class="fa-solid fa-rotate-left me-1"></i> ดูข้อมูลทั้งหมด
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
                    <?php if ($search !== ''): ?>
                        (ผลการค้นหาสำหรับ "<em><?= e($search) ?></em>")
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
