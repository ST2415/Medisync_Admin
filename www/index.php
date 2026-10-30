<?php
$pageTitle = "Homepage";

require_once 'db_conn.php';

// ฟังก์ชันนับจำนวนข้อมูล
function countRecords($conn, $table) {
    $allowedTables = [
        'users',
        'medicines',
        'medication_log',
        'appointments'
    ];

    if (!in_array($table, $allowedTables, true)) {
        return 0;
    }

    $sql = "SELECT COUNT(*) AS total FROM `$table`";
    $result = $conn->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int) $row['total'];
}

// ดึงจำนวนข้อมูลจริง
$totalUsers = countRecords($conn, 'users');
$totalMedicines = countRecords($conn, 'medicines');
$totalLogs = countRecords($conn, 'medication_log');
$totalAppointments = countRecords($conn, 'appointments');

// ข้อมูลสำหรับ Summary Cards โดยใช้สีตาม Palette ที่กำหนด
$stats = [
    [
        "title" => "ผู้ใช้งานทั้งหมด",
        "en_title" => "Total Users",
        "value" => number_format($totalUsers),
        "icon" => "fa-hospital-user",
        "color" => "#294549",
        "bg_soft" => "rgba(41, 69, 73, 0.12)",
        "badge" => "ผู้ป่วยและสมาชิก",
        "link" => "user-page/user.php"
    ],
    [
        "title" => "รายการยาทั้งหมด",
        "en_title" => "Total Medicines",
        "value" => number_format($totalMedicines),
        "icon" => "fa-pills",
        "color" => "#4E7F90",
        "bg_soft" => "rgba(78, 127, 144, 0.14)",
        "badge" => "เวชภัณฑ์ในคลัง",
        "link" => "medicine-page/medicine.php"
    ],
    [
        "title" => "บันทึกการทานยา",
        "en_title" => "Medication Logs",
        "value" => number_format($totalLogs),
        "icon" => "fa-clipboard-check",
        "color" => "#4A807E",
        "bg_soft" => "rgba(74, 128, 126, 0.14)",
        "badge" => "ประวัติรับประทานยา",
        "link" => "medication-log-page/medication-log.php"
    ],
    [
        "title" => "รายการนัดหมาย",
        "en_title" => "Appointments",
        "value" => number_format($totalAppointments),
        "icon" => "fa-calendar-check",
        "color" => "#294549",
        "accent" => "#90C2B7",
        "bg_soft" => "rgba(144, 194, 183, 0.28)",
        "badge" => "ตารางพบแพทย์",
        "link" => "appointment-page/appointment.php"
    ],
];

// ดึงข้อมูลนัดหมายล่าสุด 6 รายการ
$sql = "
    SELECT
        a.appointment_id,
        u.name AS user_name,
        a.appointment_date,
        a.appointment_time,
        a.status
    FROM appointments a
    LEFT JOIN users u
        ON a.user_id = u.user_id
    ORDER BY
        a.appointment_date DESC,
        a.appointment_time DESC
    LIMIT 6
";

$result = $conn->query($sql);
$recentAppointments = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentAppointments[] = [
            "id" => $row['appointment_id'],
            "name" => $row['user_name'] ?? 'ไม่ระบุชื่อ',
            "date" => $row['appointment_date'],
            "time" => $row['appointment_time'],
            "status" => $row['status']
        ];
    }
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | Medisync ระบบบริหารจัดการยา</title>

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
            /* กำหนดตัวแปรสีตามชุดสีที่ผู้ใช้ระบุ */
            --color-primary: #294549;       /* น้ำเงินอมเขียวเข้ม / Deep Slate Pine */
            --color-secondary: #4E7F90;     /* ฟ้าน้ำทะเลหม่น / Steel Teal */
            --color-teal: #4A807E;          /* เขียวหัวเป็ดกลาง / Sage Teal */
            --color-mint: #90C2B7;          /* เขียวมินต์สดใส / Fresh Mint */
            --color-ice: #BBD7DA;           /* ฟ้าไอซ์พาสเทล / Ice Pastel Cyan */
            --color-white: #FFFFFF;
            --color-black: #000000;

            /* สีระบบสำหรับการจัดวาง */
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

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-date-badge {
            background: rgba(187, 215, 218, 0.35);
            border: 1px solid var(--color-ice);
            color: var(--color-primary);
            padding: 7px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-date-badge i {
            color: var(--color-teal);
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

        /* ===== WELCOME HERO BANNER ===== */
        .welcome-hero {
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

        .welcome-hero::after {
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

        .welcome-text h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.3px;
        }

        .welcome-text p {
            font-size: 13.5px;
            color: #dbe8e9;
            margin: 0;
            max-width: 600px;
            line-height: 1.6;
        }

        .hero-action-buttons {
            display: flex;
            gap: 12px;
            flex-shrink: 0;
            z-index: 1;
        }

        .btn-hero-primary {
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
        }

        .btn-hero-primary:hover {
            background: var(--color-white);
            color: var(--color-primary);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        .btn-hero-outline {
            background: rgba(255, 255, 255, 0.12);
            color: var(--color-white);
            border: 1px solid rgba(187, 215, 218, 0.4);
            font-weight: 500;
            padding: 10px 18px;
            border-radius: 12px;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(5px);
            transition: all 0.2s ease;
        }

        .btn-hero-outline:hover {
            background: rgba(255, 255, 255, 0.24);
            color: var(--color-white);
            transform: translateY(-2px);
        }

        /* ===== SUMMARY STATS CARDS ===== */
        .stat-card-link {
            display: block;
            height: 100%;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 22px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.24s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.24s ease, border-color 0.24s ease;
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
            transform: translateY(-5px);
            box-shadow: 0 14px 30px rgba(41, 69, 73, 0.12);
            border-color: var(--color-teal);
        }

        .stat-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .stat-titles h6 {
            font-size: 14.5px;
            font-weight: 600;
            color: var(--color-primary);
            margin: 0;
        }

        .stat-titles small {
            font-size: 11px;
            color: var(--text-muted);
            display: block;
            margin-top: 2px;
        }

        .stat-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            flex-shrink: 0;
            transition: transform 0.25s ease;
        }

        .stat-card:hover .stat-icon-wrapper {
            transform: scale(1.08) rotate(4deg);
        }

        .stat-body {
            margin-top: auto;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: var(--color-primary);
            line-height: 1.15;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .stat-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            color: var(--color-teal);
            font-weight: 500;
            background: rgba(74, 128, 126, 0.1);
            padding: 4px 10px;
            border-radius: 20px;
        }

        /* ===== CONTENT PANELS ===== */
        .panel {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 24px;
            height: 100%;
            box-shadow: 0 6px 20px rgba(41, 69, 73, 0.06);
            display: flex;
            flex-direction: column;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(74, 128, 126, 0.12);
        }

        .panel-header h5 {
            font-size: 16px;
            font-weight: 700;
            color: var(--color-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .panel-header h5 i {
            color: var(--color-teal);
        }

        .panel-header p {
            font-size: 12px;
            color: var(--text-muted);
            margin: 4px 0 0;
        }

        .view-all-link {
            font-size: 12.5px;
            color: var(--color-secondary);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: rgba(78, 127, 144, 0.08);
            border-radius: 20px;
            transition: all 0.2s ease;
        }

        .view-all-link:hover {
            background: var(--color-primary);
            color: var(--color-white);
            transform: translateX(2px);
        }

        /* ===== TABLE STYLING ===== */
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
            padding: 12px 14px;
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
            transition: background 0.18s ease;
        }

        .custom-table tbody tr:hover {
            background: rgba(187, 215, 218, 0.25);
        }

        .custom-table tbody td {
            padding: 14px 14px;
            border-bottom: 1px solid rgba(74, 128, 126, 0.1);
            color: var(--color-primary);
        }

        .id-badge {
            font-weight: 600;
            color: var(--color-secondary);
            background: rgba(78, 127, 144, 0.1);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            display: inline-block;
        }

        .patient-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .patient-avatar-mini {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(144, 194, 183, 0.35);
            color: var(--color-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 600;
        }

        .patient-name-text {
            font-weight: 600;
            color: var(--color-primary);
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
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
            border: 1px solid rgba(78, 127, 144, 0.3);
        }

        /* ===== QUICK ACTION SHORTCUTS ===== */
        .quick-action-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            background: #FFFFFF;
            border: 1px solid var(--card-border);
            border-radius: 14px;
            margin-bottom: 12px;
            transition: all 0.22s ease;
        }

        .quick-action-item:hover {
            background: rgba(187, 215, 218, 0.25);
            border-color: var(--color-teal);
            transform: translateX(4px);
            box-shadow: 0 4px 14px rgba(41, 69, 73, 0.08);
        }

        .quick-action-item:last-child {
            margin-bottom: 0;
        }

        .quick-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }

        .quick-action-item:hover .quick-icon-box {
            transform: scale(1.1);
        }

        .quick-action-text strong {
            display: block;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--color-primary);
            margin-bottom: 2px;
        }

        .quick-action-text small {
            display: block;
            font-size: 11.5px;
            color: var(--text-muted);
        }

        .quick-arrow-icon {
            margin-left: auto;
            color: var(--color-secondary);
            font-size: 13px;
            transition: transform 0.2s ease;
        }

        .quick-action-item:hover .quick-arrow-icon {
            transform: translateX(3px);
            color: var(--color-primary);
        }

        /* Medical Tip Card */
        .medical-tip-box {
            background: linear-gradient(135deg, rgba(144, 194, 183, 0.2) 0%, rgba(187, 215, 218, 0.35) 100%);
            border: 1px dashed var(--color-teal);
            border-radius: 14px;
            padding: 16px;
            margin-top: 18px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .tip-icon {
            font-size: 20px;
            color: var(--color-teal);
            margin-top: 2px;
        }

        .tip-content h6 {
            font-size: 13px;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: 4px;
        }

        .tip-content p {
            font-size: 11.5px;
            color: var(--text-muted);
            margin: 0;
            line-height: 1.5;
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

            .welcome-hero {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
                padding: 22px 20px;
            }
        }

        @media (max-width: 575.98px) {
            .header-date-badge {
                display: none;
            }

            .admin-info {
                display: none;
            }

            .stat-number {
                font-size: 26px;
            }

            .hero-action-buttons {
                flex-direction: column;
                width: 100%;
            }

            .btn-hero-primary, .btn-hero-outline {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>

<!-- Mobile Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">

    <!-- Brand -->
    <a href="index.php" class="sidebar-brand">
        <img src="assets/logo.svg"
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

    <!-- Navigation Menu -->
    <ul class="sidebar-menu">
        <li>
            <a href="index.php" class="active">
                <i class="fa-solid fa-chart-pie"></i>
                <span>หน้าหลัก (Dashboard)</span>
            </a>
        </li>

        <li>
            <a href="user-page/user.php">
                <i class="fa-solid fa-users"></i>
                <span>ข้อมูลผู้ใช้ (Users)</span>
            </a>
        </li>

        <li>
            <a href="medicine-page/medicine.php">
                <i class="fa-solid fa-pills"></i>
                <span>รายการยา (Medicines)</span>
            </a>
        </li>

        <li>
            <a href="appointment-page/appointment.php">
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
            Medisync Admin System<br>
            <span style="opacity: 0.8;">เวอร์ชัน 2.4.0 (Medical-Grade)</span>
        </div>
    </div>

</aside>

<!-- Main Content Area -->
<main class="main-content">

    <!-- Topbar -->
    <header class="topbar">

        <div class="d-flex align-items-center gap-3">
            <button
                type="button"
                class="mobile-menu-btn"
                id="mobileMenuBtn"
                aria-label="Toggle navigation"
            >
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="page-heading">
                <h5>
                    <i class="fa-solid fa-house-medical" style="color: var(--color-teal);"></i>
                    หน้าแรกแดชบอร์ด
                </h5>
                <p>ภาพรวมระบบการจัดการยาและผู้ป่วย Medisync</p>
            </div>
        </div>

        <div class="topbar-actions">
            <!-- Current Date Badge -->
            <div class="header-date-badge">
                <i class="fa-regular fa-calendar-days"></i>
                <span><?= date('d M Y') ?></span>
                <span style="opacity: 0.5;">|</span>
                <span id="liveClock"><?= date('H:i') ?></span>
            </div>

            <!-- Admin Profile -->
            <div class="admin-profile">
                <div class="admin-avatar">
                    <i class="fa-solid fa-user-doctor"></i>
                </div>
                <div class="admin-info">
                    <strong>ผู้ดูแลระบบ</strong>
                    <small>System Admin</small>
                </div>
            </div>
        </div>

    </header>

    <!-- Page Body Content -->
    <div class="content-wrapper">

        <!-- Welcome Hero Section -->
        <section class="welcome-hero">
            <div class="welcome-text">
                <h2>
                    <i class="fa-solid fa-heart-pulse me-2" style="color: var(--color-mint);"></i>
                    ยินดีต้อนรับกลับสู่ระบบ Medisync!
                </h2>
                <p>
                    ระบบบริหารจัดการยาและประวัติการดูแลผู้ป่วย ติดตามสถิติการจ่ายยา การนัดหมายแพทย์
                    และบันทึกการรับประทานยาได้อย่างสะดวก ถูกต้อง และปลอดภัย
                </p>
            </div>

            <div class="hero-action-buttons">
                <a href="user-page/user.php" class="btn-hero-primary">
                    <i class="fa-solid fa-user-plus"></i>
                    <span>จัดการผู้ใช้</span>
                </a>
                <a href="appointment-page/appointment.php" class="btn-hero-outline">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>ตรวจการนัดหมาย</span>
                </a>
            </div>
        </section>

        <!-- Summary Cards Grid -->
        <section class="row g-3 mb-4">
            <?php foreach ($stats as $stat): ?>
                <div class="col-12 col-sm-6 col-xl-3">
                    <a href="<?= e($stat['link']) ?>" class="stat-card-link">
                        <div class="stat-card" style="--stat-theme-color: <?= e($stat['color']) ?>;">
                            <div class="stat-header">
                                <div class="stat-titles">
                                    <h6><?= e($stat['title']) ?></h6>
                                    <small><?= e($stat['en_title']) ?></small>
                                </div>
                                <div class="stat-icon-wrapper" style="background: <?= e($stat['bg_soft']) ?>; color: <?= e($stat['color']) ?>;">
                                    <i class="fa-solid <?= e($stat['icon']) ?>"></i>
                                </div>
                            </div>

                            <div class="stat-body">
                                <div class="stat-number">
                                    <?= e($stat['value']) ?>
                                </div>
                                <div class="stat-badge">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span><?= e($stat['badge']) ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </section>

        <!-- Main Dashboard Split: Recent Appointments & Quick Actions -->
        <section class="row g-4">

            <!-- Recent Appointments Table (8 Columns on Large Screens) -->
            <div class="col-12 col-8">
                <div class="panel">
                    <div class="panel-header">
                        <div>
                            <h5>
                                <i class="fa-solid fa-calendar-check"></i>
                                รายการนัดหมายล่าสุด
                            </h5>
                            <p>รายการนัดหมายแพทย์และการติดตามอาการล่าสุดในระบบ</p>
                        </div>
                        <a href="appointment-page/appointment.php" class="view-all-link">
                            <span>ดูรายการทั้งหมด</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>รหัสนัดหมาย</th>
                                    <th>ชื่อผู้ป่วย</th>
                                    <th>วันที่นัด</th>
                                    <th>เวลานัด</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentAppointments)): ?>
                                    <?php foreach ($recentAppointments as $appointment): ?>
                                        <?php
                                        // จัดการสถานะทั้งภาษาไทยและอังกฤษ
                                        $rawStatus = trim($appointment['status']);
                                        $statusClass = 'status-pending';
                                        $statusIcon = 'fa-clock';

                                        if (in_array($rawStatus, ['ยืนยัน', 'Confirmed', 'confirmed'], true)) {
                                            $statusClass = 'status-confirmed';
                                            $statusIcon = 'fa-check-circle';
                                        } elseif (in_array($rawStatus, ['เลื่อน', 'Cancelled', 'cancelled', 'ยกเลิก'], true)) {
                                            $statusClass = 'status-rescheduled';
                                            $statusIcon = 'fa-clock-rotate-left';
                                        }
                                        ?>
                                        <tr>
                                            <!-- Appointment ID -->
                                            <td>
                                                <span class="id-badge">
                                                    <?= e($appointment['id']) ?>
                                                </span>
                                            </td>

                                            <!-- Patient Name -->
                                            <td>
                                                <span class="patient-name-text">
                                                    <?= e($appointment['name']) ?>
                                                </span>
                                            </td>

                                            <!-- Date -->
                                            <td>
                                                <i class="fa-regular fa-calendar me-1" style="color: var(--color-teal);"></i>
                                                <?= e($appointment['date']) ?>
                                            </td>

                                            <!-- Time -->
                                            <td>
                                                <i class="fa-regular fa-clock me-1" style="color: var(--color-secondary);"></i>
                                                <?= e(substr($appointment['time'], 0, 5)) ?> น.
                                            </td>

                                            <!-- Status -->
                                            <td>
                                                <span class="status-badge <?= $statusClass ?>">
                                                    <i class="fa-solid <?= $statusIcon ?>"></i>
                                                    <?= e($appointment['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="fa-solid fa-folder-open mb-2 fs-3 text-secondary d-block"></i>
                                            ยังไม่มีรายการนัดหมายในระบบ
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
    // Live Clock Update
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const clockEl = document.getElementById('liveClock');
        if (clockEl) {
            clockEl.textContent = hours + ':' + minutes;
        }
    }
    setInterval(updateClock, 30000);

    // Sidebar Mobile Toggle
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
