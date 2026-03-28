<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/notification_helper.php';

$authUser  = AuthMiddleware::requireRole('user');
$pageTitle = 'My Dashboard';
$uid       = $authUser['id'];

$stats = [];
foreach (['Open', 'In Progress', 'Resolved'] as $s) {
  $st = db()->prepare("SELECT COUNT(*) FROM incidents WHERE user_id=? AND status=?");
  $st->execute([$uid, $s]);
  $stats[$s] = (int)$st->fetchColumn();
}
$total = array_sum($stats);

$stmt = db()->prepare("SELECT * FROM incidents WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$uid]);
$recent = $stmt->fetchAll();
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

        <div class="mb-4">
          <h4 class="fw-bold mb-0">Welcome back, <?= htmlspecialchars($authUser['name']) ?> 👋</h4>
          <p class="text-muted">Here's a summary of your incident reports.</p>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-6 col-md-3">
            <div class="stat-card stat-primary">
              <div class="stat-icon"><i class="fa fa-list"></i></div>
              <div class="stat-number" id="stat-total"><?= $total ?></div>
              <div class="stat-label">Total Submitted</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="stat-card stat-danger">
              <div class="stat-icon"><i class="fa fa-circle-exclamation"></i></div>
              <div class="stat-number" id="stat-open"><?= $stats['Open'] ?></div>
              <div class="stat-label">Open</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="stat-card stat-warning">
              <div class="stat-icon"><i class="fa fa-spinner"></i></div>
              <div class="stat-number" id="stat-inprogress"><?= $stats['In Progress'] ?></div>
              <div class="stat-label">In Progress</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="stat-card stat-success">
              <div class="stat-icon"><i class="fa fa-circle-check"></i></div>
              <div class="stat-number" id="stat-resolved"><?= $stats['Resolved'] ?></div>
              <div class="stat-label">Resolved</div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fa fa-clock-rotate-left me-2 text-primary"></i>Recent Incidents</span>
            <a href="<?= BASE_URL ?>/user/my_incidents.php" class="btn btn-sm btn-outline-primary">View All</a>
          </div>
          <div class="card-body p-0">
            <table class="table table-custom mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Title</th>
                  <th>Category</th>
                  <th>Priority</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="recentIncidentsBody">
                <?php if ($recent): foreach ($recent as $i): ?>
                    <tr data-incident-id="<?= $i['id'] ?>">
                      <td><?= $i['id'] ?></td>
                      <td><?= htmlspecialchars($i['title']) ?></td>
                      <td><span class="badge bg-light text-dark"><?= $i['category'] ?></span></td>
                      <td>
                        <span class="priority-badge badge-<?= strtolower($i['priority']) ?>">
                          <?= $i['priority'] ?>
                        </span>
                      </td>
                      <td>
                        <span class="status-badge badge-<?= strtolower(str_replace(' ', '-', $i['status'])) ?>
                        incident-status-cell">
                          <?= $i['status'] ?>
                        </span>
                      </td>
                      <td><?= date('d M Y', strtotime($i['created_at'])) ?></td>
                      <td>
                        <a href="<?= BASE_URL ?>/user/view_incident.php?id=<?= $i['id'] ?>"
                          class="btn btn-sm btn-outline-secondary">
                          <i class="fa fa-eye"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach;
                else: ?>
                  <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No incidents yet.
                      <a href="<?= BASE_URL ?>/user/submit_incident.php">Submit your first incident</a>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>

</html>