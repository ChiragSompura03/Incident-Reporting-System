<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/audit_helper.php';

$authUser  = AuthMiddleware::requireRole('superadmin');
$pageTitle = 'User Management';

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'add') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = trim($_POST['role']     ?? 'user');

    if (!$name || !$email || !$password) {
      $error = 'Name, email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = 'Invalid email.';
    } elseif (!in_array($role, ['user', 'admin', 'superadmin'])) {
      $error = 'Invalid role.';
    } else {
      $chk = db()->prepare("SELECT id FROM users WHERE email=?");
      $chk->execute([$email]);
      if ($chk->fetch()) {
        $error = 'Email already exists.';
      } else {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = db()->prepare("INSERT INTO users(name,email,password,role) VALUES(?,?,?,?)");
        $stmt->execute([$name, $email, $hash, $role]);
        $newId = (int)db()->lastInsertId();
        AuditHelper::log($authUser['id'], 'Created User', 'users', $newId, "Added user: $name ($role)");
        $success = "User \"$name\" created successfully.";
      }
    }
  }

  elseif ($action === 'edit') {
    $uid   = (int)($_POST['user_id'] ?? 0);
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $role  = trim($_POST['role']  ?? '');

    if ($uid === $authUser['id']) {
      $error = 'You cannot edit your own account from here.';
    } elseif (!$name || !$email || !$role) {
      $error = 'All fields required.';
    } elseif (!in_array($role, ['user', 'admin', 'superadmin'])) {
      $error = 'Invalid role.';
    } else {
      $upd = db()->prepare("UPDATE users SET name=?,email=?,role=? WHERE id=?");
      $upd->execute([$name, $email, $role, $uid]);

      if (!empty($_POST['new_password'])) {
        $hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT, ['cost' => 12]);
        db()->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $uid]);
      }

      AuditHelper::log($authUser['id'], 'Updated User', 'users', $uid, "Updated: $name role=$role");
      $success = "User updated.";
    }
  }

  elseif ($action === 'toggle_block') {
    $uid = (int)($_POST['user_id'] ?? 0);
    if ($uid === $authUser['id']) {
      $error = 'You cannot block yourself.';
    } else {
      $cur = db()->prepare("SELECT is_blocked,name FROM users WHERE id=?");
      $cur->execute([$uid]);
      $row = $cur->fetch();
      if ($row) {
        $newBlock = $row['is_blocked'] ? 0 : 1;
        db()->prepare("UPDATE users SET is_blocked=? WHERE id=?")->execute([$newBlock, $uid]);
        $act = $newBlock ? 'Blocked User' : 'Unblocked User';
        AuditHelper::log($authUser['id'], $act, 'users', $uid, "{$row['name']} " . ($newBlock ? 'blocked' : 'unblocked'));
        $success = "User \"{$row['name']}\" " . ($newBlock ? 'blocked.' : 'unblocked.');
      }
    }
  }

  elseif ($action === 'delete') {
    $uid = (int)($_POST['user_id'] ?? 0);
    if ($uid === $authUser['id']) {
      $error = 'You cannot delete yourself.';
    } else {
      $cur = db()->prepare("SELECT name FROM users WHERE id=?");
      $cur->execute([$uid]);
      $row = $cur->fetch();
      if ($row) {
        db()->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
        AuditHelper::log($authUser['id'], 'Deleted User', 'users', $uid, "Deleted: {$row['name']}");
        $success = "User \"{$row['name']}\" deleted.";
      }
    }
  }
}

$search = trim($_GET['search'] ?? '');
$roleF  = trim($_GET['role']   ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];
if ($search) {
  $where[] = '(name LIKE :s OR email LIKE :s2)';
  $params[':s'] = "%$search%";
  $params[':s2'] = "%$search%";
}
if ($roleF) {
  $where[] = 'role=:role';
  $params[':role'] = $roleF;
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$cStmt = db()->prepare("SELECT COUNT(*) FROM users $whereSQL");
$cStmt->execute($params);
$totalRows  = (int)$cStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $limit));

$stmt = db()->prepare("
    SELECT u.*,
        (SELECT COUNT(*) FROM incidents WHERE user_id=u.id) AS incident_count
    FROM users u $whereSQL ORDER BY u.created_at DESC
    LIMIT :lim OFFSET :off
");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();
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

        <?php if ($error):   ?><div class="alert flash-error auto-dismiss"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert flash-success auto-dismiss"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div class="card mb-3">
          <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
              <form method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm"
                  placeholder="Search name/email..." value="<?= htmlspecialchars($search) ?>">
                <select name="role" class="form-select form-select-sm" style="width:130px">
                  <option value="">All Roles</option>
                  <option value="user" <?= $roleF === 'user' ? 'selected' : '' ?>>User</option>
                  <option value="admin" <?= $roleF === 'admin' ? 'selected' : '' ?>>Admin</option>
                  <option value="superadmin" <?= $roleF === 'superadmin' ? 'selected' : '' ?>>Super Admin</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i></button>
                <a href="users.php" class="btn btn-outline-secondary btn-sm"><i class="fa fa-xmark"></i></a>
              </form>
              <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fa fa-user-plus me-1"></i>Add User
              </button>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <i class="fa fa-users me-2 text-primary"></i>All Users
            <span class="badge bg-secondary ms-1"><?= $totalRows ?></span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-custom mb-0">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Incidents</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users as $u): ?>
                    <tr>
                      <td><?= $u['id'] ?></td>
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                            style="width:32px;height:32px;font-size:.8rem;background:<?= $u['role'] === 'superadmin' ? '#dc3545' : ($u['role'] === 'admin' ? '#1a73e8' : '#28a745') ?>">
                            <?= strtoupper(substr($u['name'], 0, 1)) ?>
                          </div>
                          <?= htmlspecialchars($u['name']) ?>
                        </div>
                      </td>
                      <td><?= htmlspecialchars($u['email']) ?></td>
                      <td>
                        <?php
                        $rc = $u['role'] === 'superadmin' ? 'bg-danger' : ($u['role'] === 'admin' ? 'bg-primary' : 'bg-success');
                        ?>
                        <span class="badge <?= $rc ?>"><?= $u['role'] ?></span>
                      </td>
                      <td><span class="badge bg-light text-dark border"><?= $u['incident_count'] ?></span></td>
                      <td>
                        <?php if ($u['is_blocked']): ?>
                          <span class="badge bg-danger">Blocked</span>
                        <?php else: ?>
                          <span class="badge bg-success">Active</span>
                        <?php endif; ?>
                      </td>
                      <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                      <td>
                        <div class="d-flex gap-1">
                          <button class="btn btn-xs btn-outline-primary"
                            onclick='openEdit(<?= json_encode($u) ?>)'
                            title="Edit">
                            <i class="fa fa-pen"></i>
                          </button>
                          <?php if ($u['id'] !== $authUser['id']): ?>
                            <form method="POST" class="d-inline">
                              <input type="hidden" name="action" value="toggle_block">
                              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                              <button type="submit" class="btn btn-xs btn-outline-<?= $u['is_blocked'] ? 'success' : 'warning' ?>"
                                title="<?= $u['is_blocked'] ? 'Unblock' : 'Block' ?>">
                                <i class="fa fa-<?= $u['is_blocked'] ? 'unlock' : 'ban' ?>"></i>
                              </button>
                            </form>
                            <form method="POST" class="d-inline"
                              onsubmit="return confirm('Delete this user permanently?')">
                              <input type="hidden" name="action" value="delete">
                              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                              <button type="submit" class="btn btn-xs btn-outline-danger" title="Delete">
                                <i class="fa fa-trash"></i>
                              </button>
                            </form>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php if ($totalPages > 1): ?>
            <div class="card-footer d-flex justify-content-between align-items-center">
              <small class="text-muted">Showing <?= $offset + 1 ?>–<?= min($offset + $limit, $totalRows) ?> of <?= $totalRows ?></small>
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

        <div class="modal fade" id="addModal" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-user-plus me-2"></i>Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                  <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control"
                      placeholder="Min 8 characters" required>
                  </div>
                  <div class="mb-2">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                      <option value="user">User</option>
                      <option value="admin">Admin</option>
                      <option value="superadmin">Super Admin</option>
                    </select>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-primary">Create User</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="modal fade" id="editModal" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-pen me-2"></i>Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="editUserId">
                <div class="modal-body">
                  <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" id="editName" class="form-control" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="editEmail" class="form-control" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select name="role" id="editRole" class="form-select">
                      <option value="user">User</option>
                      <option value="admin">Admin</option>
                      <option value="superadmin">Super Admin</option>
                    </select>
                  </div>
                  <div class="mb-2">
                    <label class="form-label">New Password <small class="text-muted">(leave blank to keep)</small></label>
                    <input type="password" name="new_password" class="form-control" placeholder="Optional">
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
              </form>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
  <script>
    function openEdit(u) {
      document.getElementById('editUserId').value = u.id;
      document.getElementById('editName').value = u.name;
      document.getElementById('editEmail').value = u.email;
      document.getElementById('editRole').value = u.role;
      new bootstrap.Modal(document.getElementById('editModal')).show();
    }
  </script>
  <style>
    .btn-xs {
      padding: 3px 7px;
      font-size: .75rem;
    }
  </style>
</body>

</html>