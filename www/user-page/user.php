<?php
$pageTitle = "Users";
require_once '../db_conn.php';

// Escape HTML
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Search
$search = trim($_GET['search'] ?? '');

// Summary Cards
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

// ดึงข้อมูล Users พร้อมจำนวน Schedule และ Medication Logs
$sql = "
    SELECT
        u.user_id,
        u.name,
        u.gender,
        u.date_of_birth,
        u.symptoms,
        u.causes,
        u.disease,

        (
            SELECT COUNT(*)
            FROM schedules s
            WHERE s.user_id = u.user_id
        ) AS total_schedules,

        (SELECT COUNT(*)
            FROM medication_log ml
            INNER JOIN schedules s ON ml.schedule_id = s.schedule_id
            WHERE s.user_id = u.user_id
        ) AS total_logs

    FROM users u
";

if ($search !== '') {
    $sql .= "
        WHERE CAST(user_id AS CHAR) LIKE ?
            OR name LIKE ?
            OR gender LIKE ?
            OR symptoms LIKE ?
            OR causes LIKE ?
            OR disease LIKE ?
    ";
}

$sql .= " ORDER BY u.user_id ASC";

$stmt = $conn->prepare($sql);

if ($search !== '') {
    $keyword = "%{$search}%";
    $stmt->bind_param(
        "ssss",
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
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Users | Medisync Admin</title>

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
            color: white;
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
            color: white;
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
            color: white;
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

        /* Main */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .topbar {
            background: white;
            min-height: 80px;
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
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

        /* Summary */
        .stat-card {
            background: white;
            border: 1px solid #E6EEEE;
            border-radius: 16px;
            padding: 22px;
            height: 100%;
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 12px;
        }

        .stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .stat-description {
            font-size: 11px;
            color: var(--text-muted);
        }

        /* User panel */
        .panel {
            background: white;
            border: 1px solid #E6EEEE;
            border-radius: 16px;
            padding: 24px;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .panel-header h5 {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }

        .panel-header p {
            color: var(--text-muted);
            font-size: 12px;
            margin: 6px 0 0;
        }

        /* Search */
        .search-box {
            display: flex;
            gap: 8px;
            width: 100%;
            max-width: 400px;
        }

        .search-box input {
            border: 1px solid #DCE8E8;
            border-radius: 9px;
            padding: 10px 13px;
            font-family: inherit;
            font-size: 12px;
        }

        .search-box input:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(78,127,144,0.12);
        }

        .btn-search {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 9px;
            padding: 9px 16px;
            font-size: 12px;
        }

        .btn-search:hover {
            background: var(--secondary);
            color: white;
        }

        .btn-reset {
            border: 1px solid #DCE8E8;
            color: var(--primary);
            border-radius: 9px;
            padding: 9px 12px;
            font-size: 12px;
        }

        /* Table */
        .table {
            font-size: 12px;
            vertical-align: middle;
            margin-bottom: 0;
        }

        .table thead th {
            color: var(--text-muted);
            background: #F7FAFA;
            font-weight: 500;
            padding: 15px 13px;
            border-bottom: 1px solid #E6EEEE;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 15px 13px;
            border-color: #EDF2F2;
        }

        .user-id {
            color: var(--secondary);
            font-weight: 600;
        }

        .user-name {
            color: var(--primary);
            font-weight: 500;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: #E7F2F1;
            color: var(--teal);
            border-radius: 50%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            margin-right: 9px;
        }

        .count-badge {
            display: inline-block;
            min-width: 35px;
            padding: 6px 9px;
            border-radius: 8px;
            background: #E7F2F1;
            color: var(--teal);
            text-align: center;
            font-weight: 500;
        }

        .log-badge {
            background: #EAF1F6;
            color: var(--secondary);
        }

        .btn-view {
            background: var(--primary);
            color: white;
            border-radius: 8px;
            padding: 7px 12px;
            font-size: 11px;
            border: none;
            white-space: nowrap;
        }

        .btn-view:hover {
            background: var(--secondary);
            color: white;
        }

        .empty-state {
            padding: 50px 15px !important;
            text-align: center;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 35px;
            margin-bottom: 12px;
            color: var(--light-blue);
        }

        .result-info {
            color: var(--text-muted);
            font-size: 12px;
            margin-top: 18px;
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
                background: rgba(0,0,0,0.35);
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

            .admin-info {
                display: none;
            }

            .content-wrapper h2 {
                font-size: 22px;
            }

            .panel {
                padding: 16px;
            }

            .search-box {
                max-width: 100%;
            }
        }
    </style>
</head>

<body>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">

    <a href="index.php" class="brand">
        <div class="brand-icon">
            <i class="fa-solid fa-heart-pulse"></i>
        </div>

        <div>
            <h4>Medisync</h4>
            <small>Healthcare Management</small>
        </div>
    </a>

    <div class="menu-label">MAIN MENU</div>

    <ul class="sidebar-menu">

        <li>
            <a href="../index.php">
                <i class="fa-solid fa-chart-pie"></i>
                Dashboard
            </a>
        </li>

        <li>
            <a href="users.php" class="active">
                <i class="fa-solid fa-users"></i>
                Users
            </a>
        </li>

        <li>
            <a href="../medicine-page/medicine.php">
                <i class="fa-solid fa-pills"></i>
                Medicines
            </a>
        </li>

        <li>
            <a href="../appointment-page/appointment.php">
                <i class="fa-solid fa-calendar-check"></i>
                Appointments
            </a>
        </li>

    </ul>

    <div class="sidebar-footer">
        <i class="fa-solid fa-shield-heart me-2"></i>
        Medisync Admin Panel
        <br>
        Personal Medicine Management
    </div>

</aside>

<!-- Main Content -->
<main class="main-content">

    <!-- Navbar -->
    <header class="topbar">

        <div class="d-flex align-items-center gap-2">

            <button
                class="mobile-menu-btn"
                id="mobileMenuBtn"
                type="button"
                aria-label="Toggle navigation"
                aria-controls="sidebar"
                aria-expanded="false"
            >
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="page-heading">
                <h5>Users</h5>
                <p>การจัดการรายการข้อมูลผู้ใช้</p>
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

    <!-- Content -->
    <div class="content-wrapper">

        <!-- Welcome -->
        <section class="welcome-section">

            <h2>หน้าการจัดการข้อมูลผู้ใช้</h2>

            <p>
                ตรวจสอบและจัดการรายการข้อมูลต่าง ๆ เกี่ยวกับผู้ใช้
            </p>

        </section>

        <!-- Summary Cards -->
        <section class="row g-3 mb-4">

            <!-- Users -->
            <div class="col-12 col-sm-6 col-xl-4">

                <div class="stat-card">

                    <div class="stat-top">
                        <div class="stat-label">Total Users</div>

                        <div class="stat-icon"
                             style="background:#E7F2F1;color:#294549;">
                            <i class="fa-solid fa-users"></i>
                        </div>
                    </div>

                    <div class="stat-value">
                        <?= number_format($totalUsers) ?>
                    </div>

                    <div class="stat-description">
                        Registered users
                    </div>

                </div>

            </div>

            <!-- Schedules -->
            <div class="col-12 col-sm-6 col-xl-4">

                <div class="stat-card">

                    <div class="stat-top">
                        <div class="stat-label">Total Schedules</div>

                        <div class="stat-icon"
                             style="background:#EAF1F6;color:#4E7F90;">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                    </div>

                    <div class="stat-value">
                        <?= number_format($totalSchedules) ?>
                    </div>

                    <div class="stat-description">
                        Medication schedules
                    </div>

                </div>

            </div>

            <!-- Medication Logs -->
            <div class="col-12 col-sm-6 col-xl-4">

                <div class="stat-card">

                    <div class="stat-top">
                        <div class="stat-label">Total Medication Logs</div>

                        <div class="stat-icon"
                             style="background:#E2F1ED;color:#4A807E;">
                            <i class="fa-solid fa-notes-medical"></i>
                        </div>
                    </div>

                    <div class="stat-value">
                        <?= number_format($totalLogs) ?>
                    </div>

                    <div class="stat-description">
                        Recorded medication logs
                    </div>

                </div>

            </div>

        </section>

        <!-- Users Table -->
        <section class="panel">

            <div class="panel-header">

                <div>
                    <h5>
                        <i class="fa-solid fa-user-group me-2"
                           style="color:var(--secondary);"></i>
                        รายการผู้ใช้ทั้งหมด
                    </h5>

                    <!-- <p>Users with their schedules and medication history</p> -->
                </div>

                <!-- Search -->
                <form method="GET" action="users.php" class="search-box">

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="ค้นหา ID ผู้ใช้, ชื่อ, อาการ, สาเหตุ, โรคที่วินิจฉัย..."
                        value="<?= e($search) ?>"
                    >

                    <button type="submit" class="btn-search">
                        <i class="fa-solid fa-search"></i>
                        Search
                    </button>

                    <?php if ($search !== ''): ?>
                        <a href="users.php" class="btn-reset">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>

                </form>

            </div>

            <!-- Table -->
            <div class="table-responsive">

                <table class="table">

                    <thead>
                        <tr>
                            <th>ID ของผู้ใช้</th>
                            <th>ชื่อผู้ใช้</th>
                            <th>เพศ</th>
                            <th>วันที่เกิด</th>
                            <th>อาการ</th>
                            <th>สาเหตุ</th>
                            <th>โรคที่วินิจฉัย</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (count($users) > 0): ?>

                            <?php foreach ($users as $user): ?>

                                <tr>

                                    <!-- User ID -->
                                    <td>
                                        <span class="user-id">
                                            <?= e($user['user_id']) ?>
                                        </span>
                                    </td>

                                    <!-- User Name -->
                                    <td>
                                        <?= e($user['name']) ?>
                                    </td>

                                    <!-- Gender -->
                                    <td>
                                        <?= e($user['gender']) ?>
                                    </td>

                                    <!-- Date of Birth -->
                                    <td>
                                        <?= e($user['date_of_birth']) ?>
                                    </td>

                                    <td>
                                        <?= e($user['symptoms']) ?>
                                    </td>

                                    <td>
                                        <?= e($user['causes']) ?>
                                    </td>

                                    <td>
                                        <?= e($user['disease']) ?>
                                    </td>

                                    <!-- Action -->
                                    <td>
                                        <a href="edit_user.php?id=<?= urlencode($user['user_id']) ?>"
                                        class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>

                                        <a href="delete_user.php?id=<?= urlencode($user['user_id']) ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Are you sure you want to delete this user?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="7" class="empty-state">

                                    <i class="fa-solid fa-users d-block"></i>

                                    <strong>No users found</strong>

                                    <div class="mt-2">
                                        No matching users were found.
                                    </div>

                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="result-info">
                จำนวนผู้ใช้ทั้งหมด <?= count($users) ?> คน
                <?php if ($search !== ''): ?>
                    for "<?= e($search) ?>"
                <?php endif; ?>
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

<!-- Sidebar Script -->
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
