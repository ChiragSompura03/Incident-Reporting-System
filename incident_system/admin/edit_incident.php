<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/audit_helper.php';
require_once __DIR__ . '/../includes/notification_helper.php';

$authUser  = AuthMiddleware::requireRole(['admin', 'superadmin']);
$pageTitle = 'Edit Incident';
$id        = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare("
    SELECT i.*, u.name AS reporter_name
    FROM incidents i JOIN users u ON i.user_id=u.id
    WHERE i.id=?
");
$stmt->execute([$id]);
$inc = $stmt->fetch();

if (!$inc) {
  header('Location: incidents.php');
  exit;
}

$error = $success = '';
$admins     = db()->query("SELECT id,name FROM users WHERE role IN ('admin','superadmin') AND is_blocked=0")->fetchAll();
$categories = ['Phishing', 'Malware', 'Ransomware', 'Unauthorized Access', 'Data Breach', 'DDoS', 'Insider Threat', 'Other'];
$priorities  = ['Low', 'Medium', 'High', 'Critical'];
$statuses   = ['Open', 'In Progress', 'Resolved', 'Closed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $newStatus   = trim($_POST['status']      ?? '');
  $newPriority = trim($_POST['priority']    ?? '');
  $newAssigned = (int)($_POST['assigned_to'] ?? 0) ?: null;
  $oldStatus   = $inc['status'];

  if (!in_array($newStatus, $statuses)) {
    $error = 'Invalid status.';
  } elseif (!in_array($newPriority, $priorities)) {
    $error = 'Invalid priority.';
  } else {
    $resolvedAt = ($newStatus === 'Resolved' && $oldStatus !== 'Resolved') ? date('Y-m-d H:i:s') : $inc['resolved_at'];

    $upd = db()->prepare("
            UPDATE incidents
            SET status=?, priority=?, assigned_to=?, resolved_at=?, updated_at=NOW()
            WHERE id=?
        ");
    $upd->execute([$newStatus, $newPriority, $newAssigned, $resolvedAt, $id]);

    if ($newStatus !== $oldStatus) {
      NotificationHelper::send(
        $inc['user_id'],
        $id,
        "Your incident \"{$inc['title']}\" status changed from $oldStatus to $newStatus."
      );
    }

    AuditHelper::log(
      $authUser['id'],
      'Updated Incident',
      'incidents',
      $id,
      "Status: $oldStatus → $newStatus | Priority: {$inc['priority']} → $newPriority"
    );

    $success = 'Incident updated successfully.';
    $stmt->execute([$id]);
    $inc = $stmt->fetch();
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Edit Incident #<?= $id ?> — <?= APP_NAME ?></title>
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

        <a href="incidents.php" class="btn btn-sm btn-outline-secondary mb-3">
          <i class="fa fa-arrow-left me-1"></i>Back to Incidents
        </a>

        <?php if ($error):   ?><div class="alert flash-error auto-dismiss"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert flash-success auto-dismiss"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div class="row g-3">
          <div class="col-md-7">
            <div class="card">
              <div class="card-header"><i class="fa fa-file-shield me-2 text-primary"></i>Incident #<?= $id ?> Details</div>
              <div class="card-body">
                <h5 class="fw-bold mb-3"><?= htmlspecialchars($inc['title']) ?></h5>
                <div class="row g-2 mb-3">
                  <div class="col-6">
                    <div class="text-muted small">Reporter</div>
                    <div class="fw-semibold"><?= htmlspecialchars($inc['reporter_name']) ?></div>
                  </div>
                  <div class="col-6">
                    <div class="text-muted small">Category</div>
                    <div class="fw-semibold"><?= $inc['category'] ?></div>
                  </div>
                  <div class="col-6">
                    <div class="text-muted small">Incident Date</div>
                    <div><?= date('d M Y', strtotime($inc['incident_date'])) ?></div>
                  </div>
                  <div class="col-6">
                    <div class="text-muted small">Submitted</div>
                    <div><?= date('d M Y, h:i A', strtotime($inc['created_at'])) ?></div>
                  </div>
                </div>
                <div class="text-muted small fw-semibold mb-1">DESCRIPTION</div>
                <div class="p-3 border rounded" style="white-space:pre-wrap;max-height:200px;overflow-y:auto">
                  <?= htmlspecialchars($inc['description']) ?>
                </div>
                <?php if ($inc['evidence_path']): ?>
                  <div class="mt-3">
                    <div class="text-muted small fw-semibold mb-1">EVIDENCE</div>
                    <?php $ext = strtolower(pathinfo($inc['evidence_path'], PATHINFO_EXTENSION)); ?>
                    <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                      <img src="<?= UPLOAD_URL . htmlspecialchars($inc['evidence_path']) ?>"
                        class="img-fluid rounded border" style="max-height:200px">
                    <?php else: ?>
                      <a href="<?= UPLOAD_URL . htmlspecialchars($inc['evidence_path']) ?>"
                        target="_blank" class="btn btn-sm btn-outline-danger">
                        <i class="fa fa-file-pdf me-1"></i>View Evidence PDF
                      </a>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="col-md-5">
            <div class="card">
              <div class="card-header"><i class="fa fa-pen me-2 text-primary"></i>Update Incident</div>
              <div class="card-body">
                <form method="POST" novalidate>
                  <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                      <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= $inc['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select" required>
                      <?php foreach ($priorities as $p): ?>
                        <option value="<?= $p ?>" <?= $inc['priority'] === $p ? 'selected' : '' ?>><?= $p ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="mb-4">
                    <label class="form-label">Assign To Admin</label>
                    <select name="assigned_to" class="form-select">
                      <option value="">Unassigned</option>
                      <?php foreach ($admins as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= $inc['assigned_to'] == $a['id'] ? 'selected' : '' ?>>
                          <?= htmlspecialchars($a['name']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-save me-2"></i>Save Changes
                  </button>
                </form>
              </div>
            </div>

            <?php if ($authUser['role'] === 'superadmin'): ?>
              <div class="card mt-3 border-danger">
                <div class="card-body">
                  <h6 class="text-danger fw-bold mb-2"><i class="fa fa-trash me-2"></i>Delete Incident</h6>
                  <p class="text-muted small mb-3">This action is permanent and cannot be undone.</p>
                  <a href="<?= BASE_URL ?>/admin/delete_incident.php?id=<?= $id ?>"
                    class="btn btn-danger w-100"
                    onclick="return confirm('Permanently delete this incident?')">
                    <i class="fa fa-trash me-2"></i>Delete Permanently
                  </a>
                </div>
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