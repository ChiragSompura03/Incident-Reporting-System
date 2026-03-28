<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/notification_helper.php';

$authUser  = AuthMiddleware::requireRole('user');
$pageTitle = 'Notifications';

NotificationHelper::markAllRead($authUser['id']);

$notifications = NotificationHelper::getAll($authUser['id'], 50);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Notifications — <?= APP_NAME ?></title>
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

        <div class="card" style="max-width:750px">
          <div class="card-header"><i class="fa fa-bell me-2 text-primary"></i>Notifications</div>
          <div class="list-group list-group-flush">
            <?php if ($notifications): foreach ($notifications as $n): ?>
                <div class="list-group-item px-4 py-3">
                  <div class="d-flex gap-3 align-items-start">
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center mt-1"
                      style="width:36px;height:36px;flex-shrink:0">
                      <i class="fa fa-bell text-white" style="font-size:.8rem"></i>
                    </div>
                    <div class="flex-grow-1">
                      <div class="fw-semibold mb-1"><?= htmlspecialchars($n['incident_title']) ?></div>
                      <div class="text-muted" style="font-size:.9rem"><?= htmlspecialchars($n['message']) ?></div>
                      <div class="text-muted mt-1" style="font-size:.78rem">
                        <i class="fa fa-clock me-1"></i><?= date('d M Y, h:i A', strtotime($n['created_at'])) ?>
                      </div>
                    </div>
                    <a href="view_incident.php?id=<?= $n['incident_id'] ?>"
                      class="btn btn-sm btn-outline-primary">View</a>
                  </div>
                </div>
              <?php endforeach;
            else: ?>
              <div class="list-group-item text-center py-5 text-muted">
                <i class="fa fa-bell-slash fa-2x mb-2 d-block"></i>No notifications yet.
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