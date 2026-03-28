<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/audit_helper.php';
require_once __DIR__ . '/../includes/notification_helper.php';

$authUser  = AuthMiddleware::requireRole(['admin', 'superadmin']);
$pageTitle = 'All Incidents';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['bulk_action'] ?? '';
  $ids    = array_map('intval', $_POST['incident_ids'] ?? []);

  if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    if ($action === 'resolve') {
      $stmt = db()->prepare("
                UPDATE incidents SET status='Resolved', resolved_at=NOW()
                WHERE id IN ($placeholders)
            ");
      $stmt->execute($ids);

      $rows = db()->prepare("SELECT id,user_id,title FROM incidents WHERE id IN ($placeholders)");
      $rows->execute($ids);
      foreach ($rows->fetchAll() as $r) {
        NotificationHelper::send(
          $r['user_id'],
          $r['id'],
          "Your incident \"{$r['title']}\" has been marked Resolved."
        );
        AuditHelper::log(
          $authUser['id'],
          'Bulk Resolved Incident',
          'incidents',
          $r['id'],
          "Admin marked resolved."
        );
      }
      $_SESSION['flash_success'] = count($ids) . ' incident(s) marked as Resolved.';
    } elseif ($action === 'assign' && !empty($_POST['assign_to'])) {
      $adminId = (int)$_POST['assign_to'];
      $params  = $ids;
      $params[] = $adminId;
      $stmt = db()->prepare("
                UPDATE incidents SET assigned_to=?
                WHERE id IN ($placeholders)
            ");
      $stmt = db()->prepare("
                UPDATE incidents SET assigned_to=?
                WHERE id IN ($placeholders)
            ");
      $bindParams = array_merge([$adminId], $ids);
      $stmt->execute($bindParams);

      AuditHelper::log(
        $authUser['id'],
        'Bulk Assigned Incidents',
        'incidents',
        null,
        "Assigned " . count($ids) . " incidents to admin #$adminId"
      );
      $_SESSION['flash_success'] = count($ids) . ' incident(s) assigned.';
    } elseif ($action === 'delete' && $authUser['role'] === 'superadmin') {
      $stmt = db()->prepare("DELETE FROM incidents WHERE id IN ($placeholders)");
      $stmt->execute($ids);
      AuditHelper::log(
        $authUser['id'],
        'Bulk Deleted Incidents',
        'incidents',
        null,
        "Deleted IDs: " . implode(',', $ids)
      );
      $_SESSION['flash_success'] = count($ids) . ' incident(s) deleted.';
    }
  }
  header('Location: incidents.php');
  exit;
}

$search   = trim($_GET['search']   ?? '');
$status   = trim($_GET['status']   ?? '');
$category = trim($_GET['category'] ?? '');
$sort     = in_array($_GET['sort'] ?? '', ['created_at', 'priority', 'status', 'severity']) ? $_GET['sort'] : 'created_at';
$order    = ($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = RECORDS_PER_PAGE;
$offset   = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];

if ($search) {
  $where[]       = '(i.title LIKE :s OR i.description LIKE :s2)';
  $params[':s']  = "%$search%";
  $params[':s2'] = "%$search%";
}
if ($status) {
  $where[] = 'i.status=:status';
  $params[':status']   = $status;
}
if ($category) {
  $where[] = 'i.category=:category';
  $params[':category'] = $category;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$cStmt = db()->prepare("SELECT COUNT(*) FROM incidents i $whereSQL");
$cStmt->execute($params);
$totalRows  = (int)$cStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $limit));

$stmt = db()->prepare("
    SELECT i.*, u.name AS reporter_name, a.name AS admin_name
    FROM incidents i
    JOIN users u ON i.user_id=u.id
    LEFT JOIN users a ON i.assigned_to=a.id
    $whereSQL
    ORDER BY i.$sort $order
    LIMIT :lim OFFSET :off
");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':lim',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':off',  $offset, PDO::PARAM_INT);
$stmt->execute();
$incidents = $stmt->fetchAll();

$admins = db()->query("SELECT id,name FROM users WHERE role IN ('admin','superadmin') AND is_blocked=0")->fetchAll();

$categories = ['Phishing', 'Malware', 'Ransomware', 'Unauthorized Access', 'Data Breach', 'DDoS', 'Insider Threat', 'Other'];
$statuses   = ['Open', 'In Progress', 'Resolved', 'Closed'];

$flash = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);
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

        <?php if ($flash): ?>
          <div class="alert flash-success auto-dismiss"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <div class="card mb-3">
          <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
              <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search title..."
                  value="<?= htmlspecialchars($search) ?>">
              </div>
              <div class="col-md-2">
                <select name="status" class="form-select">
                  <option value="">All Status</option>
                  <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <select name="category" class="form-select">
                  <option value="">All Categories</option>
                  <?php foreach ($categories as $c): ?>
                    <option value="<?= $c ?>" <?= $category === $c ? 'selected' : '' ?>><?= $c ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2">
                <select name="sort" class="form-select">
                  <option value="created_at" <?= $sort === 'created_at' ? 'selected' : '' ?>>Sort: Date</option>
                  <option value="priority" <?= $sort === 'priority' ? 'selected' : '' ?>>Sort: Priority</option>
                  <option value="status" <?= $sort === 'status' ? 'selected' : '' ?>>Sort: Status</option>
                </select>
              </div>
              <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100"><i class="fa fa-search me-1"></i>Filter</button>
                <a href="incidents.php" class="btn btn-outline-secondary"><i class="fa fa-xmark"></i></a>
              </div>
            </form>
          </div>
        </div>

        <form method="POST" id="bulkForm">
          <div id="bulkActionBar" class="alert d-none align-items-center gap-3 mb-3"
            style="background:#e3f2fd;display:none!important">
            <span id="selectedCount" class="fw-semibold text-primary">0 selected</span>
            <select name="bulk_action" class="form-select form-select-sm" style="width:auto">
              <option value="">-- Action --</option>
              <option value="resolve">Mark as Resolved</option>
              <option value="assign">Assign to Admin</option>
              <?php if ($authUser['role'] === 'superadmin'): ?>
                <option value="delete">Delete</option>
              <?php endif; ?>
            </select>
            <select name="assign_to" id="assignSelect" class="form-select form-select-sm d-none" style="width:auto">
              <option value="">Select Admin</option>
              <?php foreach ($admins as $a): ?>
                <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-primary">Apply</button>
          </div>

          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span><i class="fa fa-triangle-exclamation me-2 text-primary"></i>All Incidents
                <span class="badge bg-secondary ms-1"><?= $totalRows ?></span>
              </span>
              <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/admin/export.php?format=csv<?= $status ? "&status=$status" : '' ?>"
                  class="btn btn-sm btn-outline-success">
                  <i class="fa fa-file-csv me-1"></i>CSV
                </a>
                <a href="<?= BASE_URL ?>/admin/export.php?format=pdf<?= $status ? "&status=$status" : '' ?>"
                  class="btn btn-sm btn-outline-danger">
                  <i class="fa fa-file-pdf me-1"></i>PDF
                </a>
              </div>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-custom mb-0">
                  <thead>
                    <tr>
                      <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                      <th>#</th>
                      <th>Title</th>
                      <th>Reporter</th>
                      <th>Category</th>
                      <th>Priority</th>
                      <th>Status</th>
                      <th>Assigned</th>
                      <th>Date</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($incidents): foreach ($incidents as $i): ?>
                        <tr>
                          <td><input type="checkbox" name="incident_ids[]" value="<?= $i['id'] ?>" class="row-checkbox"></td>
                          <td><?= $i['id'] ?></td>
                          <td><?= htmlspecialchars($i['title']) ?></td>
                          <td><?= htmlspecialchars($i['reporter_name']) ?></td>
                          <td><span class="badge bg-light text-dark border"><?= $i['category'] ?></span></td>
                          <td><span class="priority-badge badge-<?= strtolower($i['priority']) ?>"><?= $i['priority'] ?></span></td>
                          <td><span class="status-badge badge-<?= strtolower(str_replace(' ', '-', $i['status'])) ?>"><?= $i['status'] ?></span></td>
                          <td><?= $i['admin_name'] ? htmlspecialchars($i['admin_name']) : '<span class="text-muted">Unassigned</span>' ?></td>
                          <td><?= date('d M Y', strtotime($i['created_at'])) ?></td>
                          <td>
                            <a href="<?= BASE_URL ?>/admin/edit_incident.php?id=<?= $i['id'] ?>"
                              class="btn btn-sm btn-outline-primary" title="Edit">
                              <i class="fa fa-pen"></i>
                            </a>
                          </td>
                        </tr>
                      <?php endforeach;
                    else: ?>
                      <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                          <i class="fa fa-inbox fa-2x d-block mb-2"></i>No incidents found.
                        </td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <?php if ($totalPages > 1): ?>
              <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-muted">
                  Showing <?= $offset + 1 ?>–<?= min($offset + $limit, $totalRows) ?> of <?= $totalRows ?>
                </small>
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
        </form>

      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
  <script>
    document.querySelector('[name="bulk_action"]').addEventListener('change', function() {
      document.getElementById('assignSelect').classList.toggle('d-none', this.value !== 'assign');
    });
    document.addEventListener('change', function(e) {
      if (e.target.classList.contains('row-checkbox') || e.target.id === 'selectAll') {
        const n = document.querySelectorAll('.row-checkbox:checked').length;
        const bar = document.getElementById('bulkActionBar');
        bar.style.display = n > 0 ? 'flex' : 'none';
        bar.classList.toggle('d-none', n === 0);
        document.getElementById('selectedCount').textContent = n + ' selected';
      }
    });
  </script>
</body>

</html>