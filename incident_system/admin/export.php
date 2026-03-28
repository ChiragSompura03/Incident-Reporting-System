<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/audit_helper.php';

$authUser = AuthMiddleware::requireRole(['admin', 'superadmin']);
$format   = strtolower($_GET['format'] ?? '');
$status   = trim($_GET['status']   ?? '');
$category = trim($_GET['category'] ?? '');

$where  = ['1=1'];
$params = [];
if ($status) {
  $where[] = 'i.status=:status';
  $params[':status']   = $status;
}
if ($category) {
  $where[] = 'i.category=:category';
  $params[':category'] = $category;
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$stmt = db()->prepare("
    SELECT i.id, i.title, u.name AS reporter, i.category, i.priority,
           i.status, i.incident_date, i.created_at, i.resolved_at,
           a.name AS assigned_admin
    FROM incidents i
    JOIN users u ON i.user_id=u.id
    LEFT JOIN users a ON i.assigned_to=a.id
    $whereSQL
    ORDER BY i.created_at DESC
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

AuditHelper::log(
  $authUser['id'],
  'Exported Incidents',
  'incidents',
  null,
  "Format: $format | Status: $status | Count: " . count($rows)
);

if ($format === 'csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="incidents_' . date('Ymd_His') . '.csv"');

  $out = fopen('php://output', 'w');
  fputcsv($out, [
    'ID',
    'Title',
    'Reporter',
    'Category',
    'Priority',
    'Status',
    'Incident Date',
    'Submitted',
    'Resolved',
    'Assigned Admin'
  ]);
  foreach ($rows as $r) {
    fputcsv($out, [
      $r['id'],
      $r['title'],
      $r['reporter'],
      $r['category'],
      $r['priority'],
      $r['status'],
      $r['incident_date'],
      $r['created_at'],
      $r['resolved_at'] ?? '',
      $r['assigned_admin'] ?? '',
    ]);
  }
  fclose($out);
  exit;
}

if ($format === 'pdf') {
  header('Content-Type: text/html; charset=utf-8');
?>
  <!DOCTYPE html>
  <html>

  <head>
    <meta charset="UTF-8">
    <title>Incidents Export</title>
    <style>
      body {
        font-family: Arial, sans-serif;
        font-size: 11px;
        color: #333;
      }

      h2 {
        text-align: center;
        font-size: 16px;
        margin-bottom: 4px;
      }

      p.sub {
        text-align: center;
        color: #888;
        margin-top: 0;
        margin-bottom: 12px;
      }

      table {
        width: 100%;
        border-collapse: collapse;
      }

      th {
        background: #1a73e8;
        color: #fff;
        padding: 6px 8px;
        text-align: left;
        font-size: 10px;
      }

      td {
        padding: 5px 8px;
        border-bottom: 1px solid #eee;
        vertical-align: top;
      }

      tr:nth-child(even) td {
        background: #f9f9f9;
      }

      .badge {
        padding: 2px 7px;
        border-radius: 10px;
        font-size: 9px;
        font-weight: bold;
      }

      .Open {
        background: #fde8e8;
        color: #c0392b;
      }

      .InProgress {
        background: #fff3cd;
        color: #856404;
      }

      .Resolved {
        background: #d4edda;
        color: #155724;
      }

      .Closed {
        background: #e2e3e5;
        color: #383d41;
      }

      .Low {
        background: #d4edda;
        color: #155724;
      }

      .Medium {
        background: #fff3cd;
        color: #856404;
      }

      .High {
        background: #fde8e8;
        color: #c0392b;
      }

      .Critical {
        background: #dc3545;
        color: #fff;
      }

      @media print {
        button {
          display: none !important;
        }
      }
    </style>
  </head>

  <body>
    <h2>🛡️ Incident Report Export</h2>
    <p class="sub">Generated on <?= date('d M Y, h:i A') ?> by <?= htmlspecialchars($authUser['name']) ?></p>

    <div style="text-align:right;margin-bottom:8px">
      <button onclick="window.print()" style="background:#1a73e8;color:#fff;border:none;padding:6px 16px;border-radius:6px;cursor:pointer;font-size:12px">
        🖨️ Print / Save as PDF
      </button>
    </div>

    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Title</th>
          <th>Reporter</th>
          <th>Category</th>
          <th>Priority</th>
          <th>Status</th>
          <th>Date</th>
          <th>Assigned</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= $r['id'] ?></td>
            <td><?= htmlspecialchars($r['title']) ?></td>
            <td><?= htmlspecialchars($r['reporter']) ?></td>
            <td><?= $r['category'] ?></td>
            <td>
              <span class="badge <?= $r['priority'] ?>"><?= $r['priority'] ?></span>
            </td>
            <td>
              <span class="badge <?= str_replace(' ', '', $r['status']) ?>"><?= $r['status'] ?></span>
            </td>
            <td><?= date('d M Y', strtotime($r['incident_date'])) ?></td>
            <td><?= htmlspecialchars($r['assigned_admin'] ?? 'Unassigned') ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr>
            <td colspan="8" style="text-align:center;padding:20px;color:#999">No incidents found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
    <p style="margin-top:12px;color:#888;font-size:10px;text-align:center">
      Total: <?= count($rows) ?> record(s) | <?= APP_NAME ?>
    </p>
  </body>

  </html>
<?php
  exit;
}

$pageTitle = 'Export Data';
$categories = ['Phishing', 'Malware', 'Ransomware', 'Unauthorized Access', 'Data Breach', 'DDoS', 'Insider Threat', 'Other'];
$statuses   = ['Open', 'In Progress', 'Resolved', 'Closed'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Export Data — <?= APP_NAME ?></title>
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

        <div class="card" style="max-width:600px">
          <div class="card-header"><i class="fa fa-file-export me-2 text-primary"></i>Export Incidents</div>
          <div class="card-body p-4">

            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label">Filter by Status</label>
                <select id="expStatus" class="form-select">
                  <option value="">All Statuses</option>
                  <?php foreach ($statuses as $s): ?>
                    <option value="<?= urlencode($s) ?>"><?= $s ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Filter by Category</label>
                <select id="expCat" class="form-select">
                  <option value="">All Categories</option>
                  <?php foreach ($categories as $c): ?>
                    <option value="<?= urlencode($c) ?>"><?= $c ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="d-flex gap-3">
              <button onclick="doExport('csv')" class="btn btn-success px-4">
                <i class="fa fa-file-csv me-2"></i>Export CSV
              </button>
              <button onclick="doExport('pdf')" class="btn btn-danger px-4">
                <i class="fa fa-file-pdf me-2"></i>Export PDF
              </button>
            </div>
            <div class="text-muted mt-3" style="font-size:.85rem">
              <i class="fa fa-info-circle me-1"></i>
              PDF will open in a new tab. Use your browser's Print → Save as PDF option.
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function doExport(fmt) {
      const st = document.getElementById('expStatus').value;
      const cat = document.getElementById('expCat').value;
      let url = `<?= BASE_URL ?>/admin/export.php?format=${fmt}`;
      if (st) url += `&status=${st}`;
      if (cat) url += `&category=${cat}`;
      if (fmt === 'pdf') window.open(url, '_blank');
      else window.location.href = url;
    }
  </script>
</body>

</html>