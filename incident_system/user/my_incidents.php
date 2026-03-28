<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

$authUser  = AuthMiddleware::requireRole('user');
$pageTitle = 'My Incidents';
$uid       = $authUser['id'];

$search   = trim($_GET['search']   ?? '');
$status   = trim($_GET['status']   ?? '');
$category = trim($_GET['category'] ?? '');
$sort     = in_array($_GET['sort'] ?? '', ['created_at', 'priority', 'status']) ? $_GET['sort'] : 'created_at';
$order    = ($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = RECORDS_PER_PAGE;
$offset   = ($page - 1) * $limit;

$where  = ['user_id = :uid'];
$params = [':uid' => $uid];

if ($search) {
  $where[]         = '(title LIKE :s OR description LIKE :s2)';
  $params[':s']    = "%$search%";
  $params[':s2']   = "%$search%";
}
if ($status) {
  $where[] = 'status = :status';
  $params[':status']   = $status;
}
if ($category) {
  $where[] = 'category = :category';
  $params[':category'] = $category;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$cStmt = db()->prepare("SELECT COUNT(*) FROM incidents $whereSQL");
$cStmt->execute($params);
$totalRows = (int)$cStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $limit));


$stmt = db()->prepare("
    SELECT * FROM incidents $whereSQL
    ORDER BY $sort $order
    LIMIT :lim OFFSET :off
");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':lim',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':off',  $offset, PDO::PARAM_INT);
$stmt->execute();
$incidents = $stmt->fetchAll();

$categories = ['Phishing', 'Malware', 'Ransomware', 'Unauthorized Access', 'Data Breach', 'DDoS', 'Insider Threat', 'Other'];
$statuses   = ['Open', 'In Progress', 'Resolved', 'Closed'];
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
              <div class="col-md-4">
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
              <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100"><i class="fa fa-search"></i></button>
                <a href="my_incidents.php" class="btn btn-outline-secondary w-100"><i class="fa fa-xmark"></i></a>
              </div>
            </form>
          </div>
        </div>

        <div class="card">
          <div class="card-header d-flex justify-content-between">
            <span><i class="fa fa-list-check me-2 text-primary"></i>My Incidents
              <span class="badge bg-secondary ms-1"><?= $totalRows ?></span>
            </span>
            <a href="<?= BASE_URL ?>/user/submit_incident.php" class="btn btn-sm btn-primary">
              <i class="fa fa-plus me-1"></i>New Report
            </a>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-custom mb-0">
                <thead>
                  <tr>
                    <th>#ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>
                      <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'priority', 'order' => $order === 'ASC' ? 'DESC' : 'ASC'])) ?>"
                        class="text-decoration-none text-dark">
                        Priority <i class="fa fa-sort fa-xs"></i>
                      </a>
                    </th>
                    <th>
                      <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'status', 'order' => $order === 'ASC' ? 'DESC' : 'ASC'])) ?>"
                        class="text-decoration-none text-dark">
                        Status <i class="fa fa-sort fa-xs"></i>
                      </a>
                    </th>
                    <th>
                      <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'created_at', 'order' => $order === 'ASC' ? 'DESC' : 'ASC'])) ?>"
                        class="text-decoration-none text-dark">
                        Date <i class="fa fa-sort fa-xs"></i>
                      </a>
                    </th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($incidents): foreach ($incidents as $i): ?>
                      <tr>
                        <td><span class="text-muted">#<?= $i['id'] ?></span></td>
                        <td><?= htmlspecialchars($i['title']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $i['category'] ?></span></td>
                        <td><span class="priority-badge badge-<?= strtolower($i['priority']) ?>"><?= $i['priority'] ?></span></td>
                        <td><span class="status-badge badge-<?= strtolower(str_replace(' ', '-', $i['status'])) ?>"><?= $i['status'] ?></span></td>
                        <td><?= date('d M Y', strtotime($i['created_at'])) ?></td>
                        <td>
                          <a href="view_incident.php?id=<?= $i['id'] ?>" class="btn btn-sm btn-outline-primary" title="View">
                            <i class="fa fa-eye"></i>
                          </a>
                        </td>
                      </tr>
                    <?php endforeach;
                  else: ?>
                    <tr>
                      <td colspan="7" class="text-center py-5 text-muted">
                        <i class="fa fa-inbox fa-2x mb-2 d-block"></i>No incidents found.
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
                      <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>">
                        <?= $p ?>
                      </a>
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