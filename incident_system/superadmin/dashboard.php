<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

$authUser  = AuthMiddleware::requireRole('superadmin');
$pageTitle = 'Super Admin Dashboard';

$totalUsers     = (int)db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalAdmins    = (int)db()->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
$blockedUsers   = (int)db()->query("SELECT COUNT(*) FROM users WHERE is_blocked=1")->fetchColumn();
$totalIncidents = (int)db()->query("SELECT COUNT(*) FROM incidents")->fetchColumn();
$openIncidents  = (int)db()->query("SELECT COUNT(*) FROM incidents WHERE status='Open'")->fetchColumn();
$totalLogs      = (int)db()->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();

$recentLogs = db()->query("
    SELECT al.*, u.name AS user_name
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id=u.id
    ORDER BY al.created_at DESC LIMIT 8
")->fetchAll();

$roleRows = db()->query("SELECT role, COUNT(*) AS cnt FROM users GROUP BY role")->fetchAll();
$roleLabels = json_encode(array_column($roleRows, 'role'));
$roleData   = json_encode(array_column($roleRows, 'cnt'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $pageTitle ?> — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <script>
    const BASE_URL = '<?= BASE_URL ?>';
  </script>
</head>

<body>
  <div class="d-flex">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>
    <div class="main-content flex-grow-1">
      <?php include __DIR__ . '/../includes/layout/topbar.php'; ?>
      <div class="p-4">

        <div class="mb-3">
          <h4 class="fw-bold mb-0">Super Admin Control Center</h4>
          <p class="text-muted">Full system overview and management.</p>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-6 col-md-2">
            <div class="stat-card stat-primary">
              <div class="stat-icon"><i class="fa fa-users"></i></div>
              <div class="stat-number"><?= $totalUsers ?></div>
              <div class="stat-label">Total Users</div>
            </div>
          </div>
          <div class="col-6 col-md-2">
            <div class="stat-card stat-success">
              <div class="stat-icon"><i class="fa fa-user-shield"></i></div>
              <div class="stat-number"><?= $totalAdmins ?></div>
              <div class="stat-label">Admins</div>
            </div>
          </div>
          <div class="col-6 col-md-2">
            <div class="stat-card stat-danger">
              <div class="stat-icon"><i class="fa fa-ban"></i></div>
              <div class="stat-number"><?= $blockedUsers ?></div>
              <div class="stat-label">Blocked</div>
            </div>
          </div>
          <div class="col-6 col-md-2">
            <div class="stat-card stat-warning">
              <div class="stat-icon"><i class="fa fa-triangle-exclamation"></i></div>
              <div class="stat-number"><?= $totalIncidents ?></div>
              <div class="stat-label">Incidents</div>
            </div>
          </div>
          <div class="col-6 col-md-2">
            <div class="stat-card stat-danger">
              <div class="stat-icon"><i class="fa fa-circle-exclamation"></i></div>
              <div class="stat-number"><?= $openIncidents ?></div>
              <div class="stat-label">Open</div>
            </div>
          </div>
          <div class="col-6 col-md-2">
            <div class="stat-card" style="background:linear-gradient(135deg,#6f42c1,#4a148c)">
              <div class="stat-icon"><i class="fa fa-scroll"></i></div>
              <div class="stat-number"><?= $totalLogs ?></div>
              <div class="stat-label">Audit Logs</div>
            </div>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-md-4">
            <div class="card">
              <div class="card-header"><i class="fa fa-chart-pie me-2 text-primary"></i>User Roles</div>
              <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="rolePie" style="max-height:200px"></canvas>
              </div>
            </div>
            <div class="card mt-3">
              <div class="card-header">Quick Actions</div>
              <div class="list-group list-group-flush">
                <a href="<?= BASE_URL ?>/superadmin/users.php" class="list-group-item list-group-item-action">
                  <i class="fa fa-users me-2 text-primary"></i>Manage Users
                </a>
                <a href="<?= BASE_URL ?>/admin/incidents.php" class="list-group-item list-group-item-action">
                  <i class="fa fa-triangle-exclamation me-2 text-warning"></i>All Incidents
                </a>
                <a href="<?= BASE_URL ?>/admin/audit_logs.php" class="list-group-item list-group-item-action">
                  <i class="fa fa-scroll me-2 text-secondary"></i>Audit Logs
                </a>
                <a href="<?= BASE_URL ?>/admin/export.php" class="list-group-item list-group-item-action">
                  <i class="fa fa-file-export me-2 text-success"></i>Export Data
                </a>
              </div>
            </div>
          </div>

          <div class="col-md-8">
            <div class="card">
              <div class="card-header d-flex justify-content-between">
                <span><i class="fa fa-scroll me-2 text-primary"></i>Recent Audit Activity</span>
                <a href="<?= BASE_URL ?>/admin/audit_logs.php" class="btn btn-sm btn-outline-primary">View All</a>
              </div>
              <div class="card-body p-0">
                <div class="list-group list-group-flush">
                  <?php foreach ($recentLogs as $log): ?>
                    <div class="list-group-item px-3 py-2">
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <?php
                          $bc = 'bg-secondary';
                          if (str_contains($log['action'], 'Login'))   $bc = 'bg-primary';
                          if (str_contains($log['action'], 'Created')) $bc = 'bg-success';
                          if (str_contains($log['action'], 'Updated')) $bc = 'bg-warning text-dark';
                          if (str_contains($log['action'], 'Deleted')) $bc = 'bg-danger';
                          ?>
                          <span class="badge <?= $bc ?> me-2"><?= htmlspecialchars($log['action']) ?></span>
                          <span class="fw-semibold small"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></span>
                          <div class="text-muted" style="font-size:.78rem;margin-top:2px">
                            <?= htmlspecialchars($log['description'] ?? '') ?>
                          </div>
                        </div>
                        <small class="text-muted text-nowrap ms-2">
                          <?= date('d M, h:i A', strtotime($log['created_at'])) ?>
                        </small>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
  <script>
    new Chart(document.getElementById('rolePie'), {
      type: 'doughnut',
      data: {
        labels: <?= $roleLabels ?>,
        datasets: [{
          data: <?= $roleData ?>,
          backgroundColor: ['#1a73e8', '#28a745', '#dc3545'],
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });
  </script>
</body>

</html>