<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

$authUser  = AuthMiddleware::requireRole(['admin', 'superadmin']);
$pageTitle = 'Admin Dashboard';

$total    = (int)db()->query("SELECT COUNT(*) FROM incidents")->fetchColumn();
$open     = (int)db()->query("SELECT COUNT(*) FROM incidents WHERE status='Open'")->fetchColumn();
$inprog   = (int)db()->query("SELECT COUNT(*) FROM incidents WHERE status='In Progress'")->fetchColumn();
$resolved = (int)db()->query("SELECT COUNT(*) FROM incidents WHERE status='Resolved'")->fetchColumn();

$avgRes = db()->query("
    SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at))
    FROM incidents WHERE status='Resolved' AND resolved_at IS NOT NULL
")->fetchColumn();
$avgRes = $avgRes ? round($avgRes, 1) : 0;

$pieData   = json_encode([$open, $inprog, $resolved]);
$pieLabels = json_encode(['Open', 'In Progress', 'Resolved']);

$catRows   = db()->query("SELECT category, COUNT(*) AS cnt FROM incidents GROUP BY category ORDER BY cnt DESC")->fetchAll();
$catLabels = json_encode(array_column($catRows, 'category'));
$catData   = json_encode(array_column($catRows, 'cnt'));
$catCount  = count($catRows);

$recentAll = db()->query("
    SELECT i.*, u.name AS reporter_name
    FROM incidents i JOIN users u ON i.user_id=u.id
    ORDER BY i.created_at DESC LIMIT 10
")->fetchAll();
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

        <div class="row g-3 mb-4">
          <div class="col-6 col-md-3">
            <div class="stat-card stat-primary">
              <div class="stat-icon"><i class="fa fa-triangle-exclamation"></i></div>
              <div class="stat-number"><?= $total ?></div>
              <div class="stat-label">Total Incidents</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="stat-card stat-danger">
              <div class="stat-icon"><i class="fa fa-circle-exclamation"></i></div>
              <div class="stat-number"><?= $open ?></div>
              <div class="stat-label">Open</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="stat-card stat-warning">
              <div class="stat-icon"><i class="fa fa-hourglass-half"></i></div>
              <div class="stat-number"><?= $inprog ?></div>
              <div class="stat-label">In Progress</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="stat-card stat-success">
              <div class="stat-icon"><i class="fa fa-clock"></i></div>
              <div class="stat-number"><?= $avgRes ?>h</div>
              <div class="stat-label">Avg Resolution</div>
            </div>
          </div>
        </div>

        <div class="row g-3 mb-4">

          <div class="col-md-4">
            <div class="card h-100">
              <div class="card-header"><i class="fa fa-chart-pie me-2 text-primary"></i>Open vs Resolved</div>
              <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="statusPie" style="max-height:240px"></canvas>
              </div>
            </div>
          </div>

          <div class="col-md-8">
            <div class="card h-100">
              <div class="card-header"><i class="fa fa-chart-bar me-2 text-primary"></i>Incidents by Category</div>
              <div class="card-body p-3">
                <?php if ($catCount === 0): ?>
                  <div class="text-center text-muted py-4">No incidents yet.</div>
                <?php else: ?>
                  <div style="overflow-x:auto; overflow-y:hidden;">
                    <div style="min-width:100%; width:<?= max(100, $catCount * 80) ?>px; height:240px; position:relative;">
                      <canvas id="catBar"></canvas>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

        </div>

        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fa fa-clock-rotate-left me-2 text-primary"></i>Recent Incidents</span>
            <a href="<?= BASE_URL ?>/admin/incidents.php" class="btn btn-sm btn-outline-primary">View All</a>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-custom mb-0">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Reporter</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentAll as $i): ?>
                    <tr>
                      <td><?= $i['id'] ?></td>
                      <td><?= htmlspecialchars($i['title']) ?></td>
                      <td><?= htmlspecialchars($i['reporter_name']) ?></td>
                      <td><span class="badge bg-light text-dark border"><?= $i['category'] ?></span></td>
                      <td><span class="priority-badge badge-<?= strtolower($i['priority']) ?>"><?= $i['priority'] ?></span></td>
                      <td><span class="status-badge badge-<?= strtolower(str_replace(' ', '-', $i['status'])) ?>"><?= $i['status'] ?></span></td>
                      <td><?= date('d M Y', strtotime($i['created_at'])) ?></td>
                      <td>
                        <a href="<?= BASE_URL ?>/admin/edit_incident.php?id=<?= $i['id'] ?>"
                          class="btn btn-sm btn-outline-primary"><i class="fa fa-pen"></i></a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
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
    new Chart(document.getElementById('statusPie'), {
      type: 'doughnut',
      data: {
        labels: <?= $pieLabels ?>,
        datasets: [{
          data: <?= $pieData ?>,
          backgroundColor: ['#dc3545', '#ffc107', '#28a745'],
          borderWidth: 2,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });

    <?php if ($catCount > 0): ?>
      new Chart(document.getElementById('catBar'), {
        type: 'bar',
        data: {
          labels: <?= $catLabels ?>,
          datasets: [{
            label: 'Incidents',
            data: <?= $catData ?>,
            backgroundColor: [
              '#1a73e8', '#dc3545', '#ffc107', '#28a745',
              '#17a2b8', '#6f42c1', '#fd7e14', '#6c757d'
            ],
            borderRadius: 6,
            maxBarThickness: 60,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            x: {
              ticks: {
                maxRotation: 30,
                font: {
                  size: 11
                }
              }
            },
            y: {
              beginAtZero: true,
              ticks: {
                stepSize: 1,
                precision: 0
              }
            }
          }
        }
      });
    <?php endif; ?>
  </script>
</body>

</html>