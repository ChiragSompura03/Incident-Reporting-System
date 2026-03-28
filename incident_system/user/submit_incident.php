<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/audit_helper.php';

$authUser  = AuthMiddleware::requireRole('user');
$pageTitle = 'Report Incident';
$error = $success = '';

$categories = ['Phishing', 'Malware', 'Ransomware', 'Unauthorized Access', 'Data Breach', 'DDoS', 'Insider Threat', 'Other'];
$priorities  = ['Low', 'Medium', 'High', 'Critical'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title       = trim($_POST['title']       ?? '');
  $description = trim($_POST['description'] ?? '');
  $category    = trim($_POST['category']    ?? '');
  $priority    = trim($_POST['priority']    ?? '');
  $inc_date    = trim($_POST['incident_date'] ?? '');

  if (!$title || !$description || !$category || !$priority || !$inc_date) {
    $error = 'All fields are required.';
  } elseif (!in_array($category, $categories)) {
    $error = 'Invalid category.';
  } elseif (!in_array($priority, $priorities)) {
    $error = 'Invalid priority.';
  } else {
    $evidencePath = null;
    if (!empty($_FILES['evidence']['name'])) {
      $file     = $_FILES['evidence'];
      $fileSize = $file['size'];
      $fileTmp  = $file['tmp_name'];
      $fileExt  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      $mime     = mime_content_type($fileTmp);

      if ($fileSize > MAX_FILE_SIZE) {
        $error = 'File too large. Max 5MB allowed.';
      } elseif (!in_array($mime, ALLOWED_TYPES) || !in_array($fileExt, ALLOWED_EXT)) {
        $error = 'Invalid file type. Allowed: JPG, PNG, GIF, PDF.';
      } else {
        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
        $newName      = uniqid('ev_', true) . '.' . $fileExt;
        $destination  = UPLOAD_DIR . $newName;
        if (move_uploaded_file($fileTmp, $destination)) {
          $evidencePath = $newName;
        } else {
          $error = 'File upload failed. Check folder permissions.';
        }
      }
    }

    if (!$error) {
      $stmt = db()->prepare("
                INSERT INTO incidents
                    (user_id, title, description, category, priority, status, evidence_path, incident_date)
                VALUES (?,?,?,?,?,'Open',?,?)
            ");
      $stmt->execute([
        $authUser['id'],
        $title,
        $description,
        $category,
        $priority,
        $evidencePath,
        $inc_date
      ]);
      $newId = (int)db()->lastInsertId();

      AuditHelper::log(
        $authUser['id'],
        'Created Incident',
        'incidents',
        $newId,
        "User {$authUser['name']} created incident: $title"
      );

      $success = 'Incident reported successfully! ID: #' . $newId;
    }
  }
}
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

        <?php if ($error): ?>
          <div class="alert flash-error auto-dismiss"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="alert flash-success auto-dismiss"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="card" style="max-width:750px">
          <div class="card-header">
            <i class="fa fa-circle-plus me-2 text-primary"></i>Report New Incident
          </div>
          <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data" novalidate>

              <div class="mb-3">
                <label class="form-label">Incident Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control"
                  placeholder="Brief title of the incident"
                  value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">Category <span class="text-danger">*</span></label>
                  <select name="category" class="form-select" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $c): ?>
                      <option value="<?= $c ?>" <?= (($_POST['category'] ?? '') === $c) ? 'selected' : '' ?>>
                        <?= $c ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Priority <span class="text-danger">*</span></label>
                  <select name="priority" class="form-select" required>
                    <option value="">-- Select Priority --</option>
                    <?php foreach ($priorities as $p): ?>
                      <option value="<?= $p ?>" <?= (($_POST['priority'] ?? '') === $p) ? 'selected' : '' ?>>
                        <?= $p ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Incident Date <span class="text-danger">*</span></label>
                <input type="date" name="incident_date" class="form-control"
                  max="<?= date('Y-m-d') ?>"
                  value="<?= htmlspecialchars($_POST['incident_date'] ?? date('Y-m-d')) ?>" required>
              </div>

              <div class="mb-3">
                <label class="form-label">Description <span class="text-danger">*</span></label>
                <textarea name="description" rows="5" class="form-control"
                  placeholder="Describe the incident in detail..." required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
              </div>

              <div class="mb-4">
                <label class="form-label">Evidence Upload
                  <small class="text-muted">(Optional — JPG, PNG, PDF — Max 5MB)</small>
                </label>
                <input type="file" name="evidence" class="form-control"
                  accept=".jpg,.jpeg,.png,.gif,.pdf">
                <div class="form-text">Upload screenshots or PDF reports as evidence.</div>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                  <i class="fa fa-paper-plane me-2"></i>Submit Report
                </button>
                <a href="<?= BASE_URL ?>/user/dashboard.php" class="btn btn-outline-secondary px-4">Cancel</a>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>

</html>