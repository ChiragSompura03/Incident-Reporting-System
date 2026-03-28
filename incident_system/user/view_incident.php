<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

$authUser  = AuthMiddleware::requireRole('user');
$pageTitle = 'View Incident';
$id        = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare("
    SELECT i.*, u.name AS assigned_name
    FROM incidents i
    LEFT JOIN users u ON i.assigned_to = u.id
    WHERE i.id=? AND i.user_id=?
");
$stmt->execute([$id, $authUser['id']]);
$inc = $stmt->fetch();

if (!$inc) {
  header('Location: ' . BASE_URL . '/user/my_incidents.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Incident #<?= $id ?> — <?= APP_NAME ?></title>
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

        <a href="my_incidents.php" class="btn btn-sm btn-outline-secondary mb-3">
          <i class="fa fa-arrow-left me-1"></i>Back to My Incidents
        </a>

        <div class="card" style="max-width:800px">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fa fa-file-shield me-2 text-primary"></i>Incident #<?= $inc['id'] ?></span>
            <span class="status-badge badge-<?= strtolower(str_replace(' ', '-', $inc['status'])) ?>">
              <?= $inc['status'] ?>
            </span>
          </div>
          <div class="card-body">
            <h5 class="fw-bold mb-3"><?= htmlspecialchars($inc['title']) ?></h5>

            <div class="row g-3 mb-3">
              <div class="col-sm-6">
                <div class="p-3 rounded" style="background:#f8f9fa">
                  <div class="text-muted small mb-1">Category</div>
                  <div class="fw-semibold"><?= $inc['category'] ?></div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="p-3 rounded" style="background:#f8f9fa">
                  <div class="text-muted small mb-1">Priority</div>
                  <span class="priority-badge badge-<?= strtolower($inc['priority']) ?>"><?= $inc['priority'] ?></span>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="p-3 rounded" style="background:#f8f9fa">
                  <div class="text-muted small mb-1">Incident Date</div>
                  <div class="fw-semibold"><?= date('d M Y', strtotime($inc['incident_date'])) ?></div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="p-3 rounded" style="background:#f8f9fa">
                  <div class="text-muted small mb-1">Submitted On</div>
                  <div class="fw-semibold"><?= date('d M Y, h:i A', strtotime($inc['created_at'])) ?></div>
                </div>
              </div>
              <?php if ($inc['assigned_name']): ?>
                <div class="col-sm-6">
                  <div class="p-3 rounded" style="background:#e3f2fd">
                    <div class="text-muted small mb-1">Assigned Admin</div>
                    <div class="fw-semibold text-primary"><?= htmlspecialchars($inc['assigned_name']) ?></div>
                  </div>
                </div>
              <?php endif; ?>
              <?php if ($inc['resolved_at']): ?>
                <div class="col-sm-6">
                  <div class="p-3 rounded" style="background:#e8f5e9">
                    <div class="text-muted small mb-1">Resolved On</div>
                    <div class="fw-semibold text-success"><?= date('d M Y, h:i A', strtotime($inc['resolved_at'])) ?></div>
                  </div>
                </div>
              <?php endif; ?>
            </div>

            <div class="mb-3">
              <div class="text-muted small fw-semibold mb-1">DESCRIPTION</div>
              <div class="p-3 rounded border" style="white-space:pre-wrap"><?= htmlspecialchars($inc['description']) ?></div>
            </div>

            <?php if ($inc['evidence_path']): ?>
              <div class="mb-2">
                <div class="text-muted small fw-semibold mb-1">EVIDENCE</div>
                <?php $ext = strtolower(pathinfo($inc['evidence_path'], PATHINFO_EXTENSION)); ?>
                <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                  <img src="<?= UPLOAD_URL . htmlspecialchars($inc['evidence_path']) ?>"
                    class="img-fluid rounded border" style="max-height:300px">
                <?php else: ?>
                  <a href="<?= UPLOAD_URL . htmlspecialchars($inc['evidence_path']) ?>"
                    class="btn btn-sm btn-outline-primary" target="_blank">
                    <i class="fa fa-file-pdf me-2"></i>View PDF Evidence
                  </a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>

</html>