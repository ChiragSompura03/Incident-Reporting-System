<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/audit_helper.php';

$authUser  = AuthMiddleware::requireRole(['admin', 'superadmin']);
$pageTitle = 'Audit Logs';

$filters = [
  'user_id' => (int)($_GET['user_id'] ?? 0) ?: null,
  'action'  => trim($_GET['action']  ?? ''),
  'from'    => trim($_GET['from']    ?? ''),
  'to'      => trim($_GET['to']      ?? ''),
];

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

$total      = AuditHelper::countLogs($filters);
$totalPages = max(1, ceil($total / $limit));
$logs       = AuditHelper::getLogs($limit, $offset, $filters);

$users = db()->query("SELECT id,name FROM users ORDER BY name")->fetchAll();
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

        <div class="card mb-3">
          <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
              <div class="col-md-3">
                <select name="user_id" class="form-select">
                  <option value="">All Users</option>
                  <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $filters['user_id'] == $u['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($u['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <input type="text" name="action" class="form-control" placeholder="Search action..."
                  value="<?= htmlspecialchars($filters['action']) ?>">
              </div>
              <div class="col-md-2">
                <input type="date" name="from" class="form-control" value="<?= $filters['from'] ?>">
              </div>
              <div class="col-md-2">
                <input type="date" name="to" class="form-control" value="<?= $filters['to'] ?>">
              </div>
              <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100"><i class="fa fa-search"></i></button>
                <a href="audit_logs.php" class="btn btn-outline-secondary"><i class="fa fa-xmark"></i></a>
              </div>
            </form>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <i class="fa fa-scroll me-2 text-primary"></i>Audit Trail
            <span class="badge bg-secondary ms-1"><?= $total ?></span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-custom mb-0">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Table</th>
                    <th>Record ID</th>
                    <th>Description</th>
                    <th>IP Address</th>
                    <th>Timestamp</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($logs): foreach ($logs as $l): ?>
                      <tr>
                        <td><?= $l['id'] ?></td>
                        <td>
                          <?php if ($l['user_name']): ?>
                            <span class="fw-semibold"><?= htmlspecialchars($l['user_name']) ?></span>
                            <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($l['user_email']) ?></div>
                          <?php else: ?>
                            <span class="text-muted">System</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php
                          $badgeClass = 'bg-secondary';
                          if (str_contains($l['action'], 'Login'))   $badgeClass = 'bg-primary';
                          if (str_contains($l['action'], 'Created')) $badgeClass = 'bg-success';
                          if (str_contains($l['action'], 'Updated')) $badgeClass = 'bg-warning text-dark';
                          if (str_contains($l['action'], 'Deleted')) $badgeClass = 'bg-danger';
                          if (str_contains($l['action'], 'Blocked')) $badgeClass = 'bg-danger';
                          ?>
                          <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($l['action']) ?></span>
                        </td>
                        <td><code><?= htmlspecialchars($l['target_table']) ?></code></td>
                        <td><?= $l['target_id'] ? '#' . $l['target_id'] : '—' ?></td>
                        <td style="max-width:250px;font-size:.85rem"><?= htmlspecialchars($l['description'] ?? '—') ?></td>
                        <td><code style="font-size:.8rem"><?= htmlspecialchars($l['ip_address'] ?? '—') ?></code></td>
                        <td style="font-size:.82rem"><?= date('d M Y, h:i A', strtotime($l['created_at'])) ?></td>
                      </tr>
                    <?php endforeach;
                  else: ?>
                    <tr>
                      <td colspan="8" class="text-center py-5 text-muted">No audit logs found.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <?php if ($totalPages > 1): ?>
            <div class="card-footer d-flex justify-content-between align-items-center">
              <small class="text-muted">Showing <?= $offset + 1 ?>–<?= min($offset + $limit, $total) ?> of <?= $total ?></small>
              <nav>
                <ul class="pagination pagination-sm mb-0">
                  <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                      <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
                    </li>
                  <?php endfor; ?>
                </ul>
              </nav>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>

</html>