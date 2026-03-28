<?php

$role        = $authUser['role']  ?? 'user';
$currentPage = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . '/../notification_helper.php';

?>

<nav class="sidebar d-flex flex-column" id="mainSidebar">

    <div class="brand d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-shield-halved"></i>
            <span class="brand-text">IRS</span>
        </div>
        <button class="sidebar-toggle-btn" onclick="toggleSidebar()" title="Toggle sidebar">
            <i class="fa fa-bars" id="toggleIcon"></i>
        </button>
    </div>

    <div class="px-3 py-2 role-badge">
        <span class="badge text-uppercase"
            style="font-size:.68rem;letter-spacing:.8px;
            background:<?= $role === 'superadmin' ? '#dc3545' : ($role === 'admin' ? '#1a73e8' : '#28a745') ?>">
            <?= htmlspecialchars($role) ?>
        </span>
    </div>

    <ul class="nav flex-column flex-grow-1 mt-1">

        <?php if ($role === 'user'): ?>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/user/dashboard.php"
                    class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fa fa-gauge-high"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/user/submit_incident.php"
                    class="nav-link <?= $currentPage === 'submit_incident.php' ? 'active' : '' ?>">
                    <i class="fa fa-circle-plus"></i>
                    <span>Report Incident</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/user/my_incidents.php"
                    class="nav-link <?= $currentPage === 'my_incidents.php' ? 'active' : '' ?>">
                    <i class="fa fa-list-check"></i>
                    <span>My Incidents</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/user/notifications.php"
                    class="nav-link <?= $currentPage === 'notifications.php' ? 'active' : '' ?>">
                    <i class="fa fa-bell"></i>
                    <span>Notifications</span>
                    <?php $unread = NotificationHelper::countUnread($authUser['id']);
                    if ($unread > 0): ?>
                        <span class="badge bg-danger ms-auto"><?= $unread ?></span>
                    <?php endif; ?>
                </a>
            </li>

        <?php elseif ($role === 'admin'): ?>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/admin/dashboard.php"
                    class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fa fa-gauge-high"></i><span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/admin/incidents.php"
                    class="nav-link <?= $currentPage === 'incidents.php' ? 'active' : '' ?>">
                    <i class="fa fa-triangle-exclamation"></i><span>All Incidents</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/admin/audit_logs.php"
                    class="nav-link <?= $currentPage === 'audit_logs.php' ? 'active' : '' ?>">
                    <i class="fa fa-scroll"></i><span>Audit Logs</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/admin/export.php"
                    class="nav-link <?= $currentPage === 'export.php' ? 'active' : '' ?>">
                    <i class="fa fa-file-export"></i><span>Export Data</span>
                </a>
            </li>

        <?php elseif ($role === 'superadmin'): ?>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/superadmin/dashboard.php"
                    class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fa fa-gauge-high"></i><span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/superadmin/users.php"
                    class="nav-link <?= $currentPage === 'users.php' ? 'active' : '' ?>">
                    <i class="fa fa-users"></i><span>User Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/admin/incidents.php"
                    class="nav-link <?= $currentPage === 'incidents.php' ? 'active' : '' ?>">
                    <i class="fa fa-triangle-exclamation"></i><span>All Incidents</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/admin/audit_logs.php"
                    class="nav-link <?= $currentPage === 'audit_logs.php' ? 'active' : '' ?>">
                    <i class="fa fa-scroll"></i><span>Audit Logs</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/admin/export.php"
                    class="nav-link <?= $currentPage === 'export.php' ? 'active' : '' ?>">
                    <i class="fa fa-file-export"></i><span>Export Data</span>
                </a>
            </li>
        <?php endif; ?>

    </ul>

    <div class="p-3 border-top user-info" style="border-color:rgba(255,255,255,.1)!important">
        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center"
                style="width:34px;height:34px;font-size:.85rem;color:#fff;font-weight:600;flex-shrink:0">
                <?= strtoupper(substr($authUser['name'], 0, 1)) ?>
            </div>
            <div style="overflow:hidden" class="brand-text">
                <div style="font-size:.85rem;color:#fff;font-weight:500;
                            white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    <?= htmlspecialchars($authUser['name']) ?>
                </div>
                <div style="font-size:.72rem;color:#9db0c5">
                    <?= htmlspecialchars($authUser['email']) ?>
                </div>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/auth/logout.php"
            class="btn btn-sm w-100 mt-1 brand-text"
            style="background:rgba(255,255,255,.1);color:#fff;border:none">
            <i class="fa fa-right-from-bracket me-1"></i>Logout
        </a>
    </div>
</nav>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('mainSidebar');
        const main = document.querySelector('.main-content');
        const icon = document.getElementById('toggleIcon');
        const collapsed = sidebar.classList.toggle('collapsed');
        main.classList.toggle('expanded', collapsed);
        icon.className = collapsed ? 'fa fa-indent' : 'fa fa-bars';
        localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
    }
    (function() {
        if (localStorage.getItem('sidebarCollapsed') === '1') {
            const s = document.getElementById('mainSidebar');
            const m = document.querySelector('.main-content');
            const i = document.getElementById('toggleIcon');
            if (s) {
                s.classList.add('collapsed');
            }
            if (m) {
                m.classList.add('expanded');
            }
            if (i) {
                i.className = 'fa fa-indent';
            }
        }
    })();
</script>