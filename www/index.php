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

// ข้อมูลสำหรับ Summary Cards
$stats = [
    [
        "title" => "Total Users",
        "value" => number_format($totalUsers),
        "icon" => "fa-users",
        "color" => "#294549",
        "description" => "Registered users"
    ],
    [
        "title" => "Total Medicines",
        "value" => number_format($totalMedicines),
        "icon" => "fa-pills",
        "color" => "#4E7F90",
        "description" => "Medicines in system"
    ],
    [
        "title" => "Medication Logs",
        "value" => number_format($totalLogs),
        "icon" => "fa-notes-medical",
        "color" => "#4A807E",
        "description" => "Recorded medication logs"
    ],
    [
        "title" => "Appointments",
        "value" => number_format($totalAppointments),
        "icon" => "fa-calendar-check",
        "color" => "#90C2B7",
        "description" => "Scheduled appointments"
    ],
];


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
    LIMIT 5
";

$result = $conn->query($sql);
$recentAppointments = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentAppointments[] = [
            "id" => $row['appointment_id'],
            "name" => $row['user_name'] ?? 'Unknown User',
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle) ?> | Medisync Admin</title>

    <!-- Bootstrap 5 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    >

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>
        :root {
            --primary: #294549;
            --secondary: #4E7F90;
            --teal: #4A807E;
            --mint: #90C2B7;
            --light-blue: #BBD7DA;
            --white: #FFFFFF;
            --black: #000000;
            --background: #F4F8F8;
            --text-muted: #75878A;
            --sidebar-width: 260px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Prompt', sans-serif;
            background: var(--background);
            color: var(--primary);
            font-size: 14px;
        }

        a {
            text-decoration: none;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: var(--primary);
            color: var(--white);
            padding: 28px 18px;
            z-index: 1050;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 12px 35px;
            color: var(--white);
        }

        .brand-icon {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--mint);
            color: var(--primary);
            border-radius: 14px;
            font-size: 22px;
        }

        .brand h4 {
            font-size: 22px;
            font-weight: 600;
            margin: 0;
        }

        .brand small {
            display: block;
            color: var(--light-blue);
            font-size: 11px;
            margin-top: 2px;
        }

        .menu-label {
            color: #A9C5C7;
            font-size: 11px;
            letter-spacing: 1px;
            padding: 0 14px;
            margin: 15px 0 12px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 7px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 13px 15px;
            border-radius: 10px;
            color: #D0E0E0;
            font-size: 13px;
            transition: 0.2s;
        }

        .sidebar-menu a i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: var(--secondary);
            color: var(--white);
        }

        .sidebar-menu a.active {
            box-shadow: inset 3px 0 var(--mint);
        }

        .sidebar-footer {
            margin-top: 40px;
            padding: 18px 12px;
            border-top: 1px solid rgba(255,255,255,0.15);
            color: var(--light-blue);
            font-size: 12px;
            line-height: 1.8;
        }

        /* Main content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        /* Top navbar */
        .topbar {
            background: var(--white);
            min-height: 80px;
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            border-bottom: 1px solid #E5EEEE;
        }

        .page-heading h5 {
            font-weight: 600;
            margin: 0;
            font-size: 19px;
        }

        .page-heading p {
            margin: 5px 0 0;
            color: var(--text-muted);
            font-size: 12px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-avatar {
            width: 42px;
            height: 42px;
            background: var(--light-blue);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }

        .admin-info strong {
            display: block;
            font-size: 13px;
        }

        .admin-info small {
            color: var(--text-muted);
            font-size: 11px;
        }

        .content-wrapper {
            padding: 30px 32px;
        }

        .welcome-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        .welcome-section h2 {
            font-size: 25px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .welcome-section p {
            color: var(--text-muted);
            margin: 0;
            font-size: 13px;
        }

        .date-label {
            color: var(--primary);
            background: var(--white);
            border: 1px solid #DCE8E8;
            border-radius: 10px;
            padding: 11px 15px;
            white-space: nowrap;
            font-size: 12px;
        }

        /* Summary cards */
        .stat-card {
            height: 100%;
            background: var(--white);
            border: 1px solid #E6EEEE;
            border-radius: 16px;
            padding: 22px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(41, 69, 73, 0.07);
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 22px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            font-size: 21px;
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 9px;
        }

        .stat-value {
            font-size: 29px;
            font-weight: 600;
            line-height: 1.2;
            margin-bottom: 9px;
            overflow-wrap: anywhere;
        }

        .stat-description {
            font-size: 11px;
            color: var(--text-muted);
        }

        /* Content panels */
        .panel {
            background: var(--white);
            border: 1px solid #E6EEEE;
            border-radius: 16px;
            padding: 24px;
            height: 100%;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
        }

        .panel-header h5 {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }

        .panel-header p {
            font-size: 12px;
            color: var(--text-muted);
            margin: 6px 0 0;
        }

        .view-all {
            font-size: 12px;
            color: var(--secondary);
            font-weight: 500;
            white-space: nowrap;
        }

        .view-all:hover {
            color: var(--primary);
        }

        .table {
            font-size: 12px;
            margin-bottom: 0;
            vertical-align: middle;
        }

        .table thead th {
            color: var(--text-muted);
            font-weight: 500;
            background: #F7FAFA;
            padding: 14px 12px;
            border-bottom: 1px solid #E6EEEE;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 16px 12px;
            border-color: #EDF2F2;
        }

        .patient-name {
            font-weight: 500;
            color: var(--primary);
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 10px;
            white-space: nowrap;
        }

        .status-confirmed {
            color: #276C59;
            background: #E2F3EC;
        }

        .status-pending {
            color: #946C20;
            background: #FFF2D6;
        }

        .status-cancelled {
            color: #A64B4B;
            background: #FBE9E9;
        }

        /* Quick actions */
        .quick-action {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 15px;
            border: 1px solid #E6EEEE;
            border-radius: 12px;
            color: var(--primary);
            margin-bottom: 12px;
            transition: 0.2s;
        }

        .quick-action:hover {
            background: #F4F9F9;
            border-color: var(--mint);
            color: var(--primary);
        }

        .quick-action:last-child {
            margin-bottom: 0;
        }

        .quick-icon {
            flex-shrink: 0;
            width: 43px;
            height: 43px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: #E7F2F1;
            color: var(--teal);
            font-size: 18px;
        }

        .quick-action strong {
            display: block;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .quick-action small {
            display: block;
            font-size: 11px;
            color: var(--text-muted);
        }

        .quick-arrow {
            margin-left: auto;
            color: #8BA3A5;
        }

        .footer {
            text-align: center;
            padding: 25px;
            color: var(--text-muted);
            font-size: 11px;
        }

        .mobile-menu-btn {
            display: none;
            border: none;
            background: transparent;
            color: var(--primary);
            font-size: 20px;
            padding: 4px 8px;
        }

        .sidebar-overlay {
            display: none;
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
                background: rgba(0, 0, 0, 0.35);
                z-index: 1040;
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: inline-flex;
            }

            .topbar {
                padding: 16px 20px;
            }

            .content-wrapper {
                padding: 24px 20px;
            }
        }

        @media (max-width: 575.98px) {
            .content-wrapper {
                padding: 20px 14px;
            }

            .topbar {
                padding: 14px;
            }

            .welcome-section {
                align-items: flex-start;
                flex-direction: column;
            }

            .welcome-section h2 {
                font-size: 22px;
            }

            .admin-info {
                display: none;
            }

            .panel {
                padding: 16px;
            }

            .stat-card {
                padding: 17px;
            }

            .stat-value {
                font-size: 25px;
            }
        }
    </style>
</head>

<body>

<!-- Mobile sidebar overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">

    <a href="index.php" class="brand">
        <div class="brand-icon">
            <i class="fa-solid fa-heart-pulse"></i>
        </div>

        <div>
            <h4>Medisync</h4>
            <small>Medicine Management</small>
        </div>
    </a>

    <!-- <div class="menu-label">MAIN MENU</div> -->

    <ul class="sidebar-menu">
        <li>
            <a href="index.php" class="active">
                <i class="fa-solid fa-chart-pie"></i>
                Home
            </a>
        </li>

        <li>
            <a href="user-page/user.php">
                <i class="fa-solid fa-users"></i>
                Users
            </a>
        </li>

        <li>
            <a href="medicines/medicine.php">
                <i class="fa-solid fa-pills"></i>
                Medicines
            </a>
        </li>

        <li>
            <a href="appointment-page/appointment.php">
                <i class="fa-solid fa-calendar-check"></i>
                Appointments
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <i class="fa-solid fa-shield-heart me-2"></i>
        Medisync Admin
        <br>
        Personal Medicine Management
    </div>

</aside>

<!-- Main content -->
<main class="main-content">

    <!-- Topbar -->
    <header class="topbar">

        <div class="d-flex align-items-center gap-2">
            <button
                type="button"
                class="mobile-menu-btn"
                id="mobileMenuBtn"
                aria-label="Toggle navigation"
                aria-controls="sidebar"
                aria-expanded="false"
            >
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="page-heading">
                <h5>Home</h5>
                <p>ภาพรวมระบบการจัดการแอป Medisync</p>
            </div>
        </div>

        <div class="admin-profile">
            <div class="admin-avatar">
                <i class="fa-solid fa-user-shield"></i>
            </div>

            <div class="admin-info">
                <strong>Administrator</strong>
                <small>System Admin</small>
            </div>
        </div>

    </header>

    <!-- Page body -->
    <div class="content-wrapper">

        <!-- Welcome section -->
        <section class="welcome-section">

            <div>
                <h2>ยินดีต้อรับกลับมา!</h2>
                <p>
                    นี่คือสิ่งที่เกิดขึ้นทั้งหมดกับระบบของเราในวันนี้
                </p>
            </div>

            <div class="date-label">
                <i class="fa-regular fa-calendar me-2"></i>
                <?= date('d M Y') ?>
            </div>

        </section>

        <!-- Summary cards -->
        <section class="row g-3 mb-4">

            <?php foreach ($stats as $stat): ?>
                <div class="col-12 col-sm-6 col-xl-3">

                    <div class="stat-card">

                        <div class="stat-top">
                            <div class="stat-label">
                                <?= e($stat['title']) ?>
                            </div>

                            <div
                                class="stat-icon"
                                style="background: <?= e($stat['color']) ?>20;
                                       color: <?= e($stat['color']) ?>;"
                            >
                                <i class="fa-solid <?= e($stat['icon']) ?>"></i>
                            </div>
                        </div>

                        <div class="stat-value">
                            <?= e($stat['value']) ?>
                        </div>

                        <div class="stat-description">
                            <i class="fa-solid fa-circle-info me-1"></i>
                            <?= e($stat['description']) ?>
                        </div>

                    </div>

                </div>
            <?php endforeach; ?>

        </section>

        <!-- Recent appointments and quick actions -->
        <section class="row g-4">

            <!-- Recent appointments -->
            <div class="col-12 col-8">

                <div class="panel">

                    <div class="panel-header">

                        <div>
                            <h5>
                                <i class="fa-solid fa-calendar-days me-2"
                                   style="color: var(--secondary);"></i>
                                สถานะการนัดหมายล่าสุด
                            </h5>

                            <p>สถานะของรายการนัดหมายล่าสุดในระบบ</p>
                        </div>

                        <a href="appointments.php" class="view-all">
                            ดูทั้งหมด
                            <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>

                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ชื่อ</th>
                                    <th>วันที่นัด</th>
                                    <th>เวลาที่นัด</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($recentAppointments as $appointment): ?>
                                    <?php
                                    $statusClass = [
                                        'Confirmed' => 'status-confirmed',
                                        'Pending' => 'status-pending',
                                        'Cancelled' => 'status-cancelled'
                                    ][$appointment['status']] ?? 'status-pending';
                                    ?>
                                    <tr>
                                        <!-- User ID -->
                                        <td>
                                            <?= e($appointment['id']) ?>
                                        </td>

                                        <!-- Patient Name -->
                                        <td>
                                            <span class="patient-name">
                                                <?= e($appointment['name']) ?>
                                            </span>
                                        </td>
                                        <!-- Date -->
                                        <td>
                                            <?= e($appointment['date']) ?>
                                        </td>
                                        <!-- Time -->
                                        <td>
                                            <?= e($appointment['time']) ?>
                                        </td>
                                        <!-- Status -->
                                        <td>
                                            <span class="status <?= e($statusClass) ?>">
                                                <?= e($appointment['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    </div>

                </div>

            </div>

        </section>

        <!-- Footer -->
        <footer class="footer">
            &copy; <?= date('Y') ?> Medisync.
            Personal Medicine Management System.
        </footer>

    </div>

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
        menuBtn.setAttribute('aria-expanded', String(show));
    }

    menuBtn.addEventListener('click', function () {
        toggleSidebar(!sidebar.classList.contains('show'));
    });

    overlay.addEventListener('click', function () {
        toggleSidebar(false);
    });

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
