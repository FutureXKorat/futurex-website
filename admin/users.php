<?php
declare(strict_types=1);
include '../database.php';
include 'auth.php';

// Only the super-admin (futurexkorat@gmail.com) can manage admin accounts
if (!$isSuperAdmin) {
    header('Location: /admin/index.php'); exit;
}

$success = '';
$error   = '';

// ── Add new admin ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name     = trim((string)($_POST['name']     ?? ''));
    $surname  = trim((string)($_POST['surname']  ?? ''));
    $username = trim((string)($_POST['username'] ?? ''));
    $email    = strtolower(trim((string)($_POST['email'] ?? '')));
    $phoneno  = trim((string)($_POST['phoneno']  ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($name === '' || $username === '' || $email === '' || $password === '') {
        $error = 'Name, username, email, and password are required.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $chk = $conn->prepare("SELECT id FROM admins WHERE username = ? OR email = ? LIMIT 1");
        $chk->bind_param('ss', $username, $email);
        $chk->execute();
        $exists = $chk->get_result()->fetch_assoc();
        $chk->close();

        if ($exists) {
            $error = 'That username or email is already in use.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare(
                "INSERT INTO admins (name, surname, username, email, phoneno, password, verified) VALUES (?, ?, ?, ?, ?, ?, 1)"
            );
            $ins->bind_param('ssssss', $name, $surname, $username, $email, $phoneno, $hashed);
            if ($ins->execute()) {
                $success = "Admin account created for {$name} ({$username}).";
            } else {
                $error = 'Database error — please try again.';
            }
            $ins->close();
        }
    }
}

// ── Delete admin ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $delId = (int)($_POST['delete_id'] ?? 0);
    if ($delId > 0) {
        $del = $conn->prepare("DELETE FROM admins WHERE id = ?");
        $del->bind_param('i', $delId);
        $del->execute();
        $del->close();
        $success = 'Admin account deleted.';
    }
}

// ── Fetch all admins ───────────────────────────────────────────
$admins = [];
$res = $conn->query("SELECT id, name, surname, username, email, phoneno FROM admins ORDER BY id ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) $admins[] = $r;
    $res->free();
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Users — Future X</title>
  <link rel="icon" type="image/png" href="/logo_transparent_onlyblack.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body {
      background: linear-gradient(135deg, #E6F0FF, #CCE0FF, #FFFFFF);
      min-height: 100vh;
      color: #111;
    }
    @supports (min-height: 100dvh) { body { min-height: 100dvh; } }
    .page-wrap { max-width: 900px; margin: 0 auto; padding: 36px 20px 60px; }
    .page-title   { font-size: 1.75rem; font-weight: 700; margin-bottom: 4px; }
    .page-subtitle { font-size: .84rem; color: #666; margin-bottom: 32px; }

    .section-card {
      background: rgba(255,255,255,0.55);
      backdrop-filter: blur(10px);
      border-radius: 18px;
      padding: 28px 24px;
      box-shadow: 0 6px 22px rgba(0,0,0,0.09);
      margin-bottom: 28px;
    }
    .section-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 20px; }

    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 10px 12px; text-align: left; font-size: .88rem; border-bottom: 1px solid rgba(0,0,0,.07); }
    th { font-weight: 600; color: #555; font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; }
    tr:last-child td { border-bottom: none; }

    .btn-del {
      background: none; border: 1px solid #dc3545; color: #dc3545;
      padding: 4px 14px; border-radius: 8px; font-size: .8rem;
      cursor: pointer; transition: background .2s, color .2s;
      white-space: nowrap;
    }
    .btn-del:hover { background: #dc3545; color: #fff; }

    label { font-size: .88rem; font-weight: 600; }
    .form-control { border-radius: 10px; }

    .btn-add {
      background: #007BFF; color: #fff; border: none;
      padding: 10px 28px; border-radius: 10px;
      font-weight: 600; font-size: .92rem; cursor: pointer;
      transition: background .2s;
    }
    .btn-add:hover { background: #0056b3; }

    .empty-note { color: #888; font-size: .9rem; }
  </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="page-wrap">
  <h1 class="page-title">Admin Users</h1>
  <p class="page-subtitle">Manage employee admin accounts — only you can see this page</p>

  <?php if ($success !== ''): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- ── Current admins table ── -->
  <div class="section-card">
    <div class="section-title">Current Admin Accounts</div>
    <?php if (empty($admins)): ?>
      <p class="empty-note">No employee admin accounts yet. Add one below.</p>
    <?php else: ?>
      <div style="overflow-x:auto;">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Username</th>
              <th>Email</th>
              <th>Phone</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($admins as $a): ?>
              <tr>
                <td style="color:#aaa;"><?= (int)$a['id'] ?></td>
                <td><strong><?= htmlspecialchars(trim($a['name'] . ' ' . $a['surname'])) ?></strong></td>
                <td><?= htmlspecialchars($a['username']) ?></td>
                <td><?= htmlspecialchars($a['email']) ?></td>
                <td><?= htmlspecialchars($a['phoneno']) ?></td>
                <td>
                  <form method="post" onsubmit="return confirm('Delete admin account for <?= htmlspecialchars(addslashes($a['name'])) ?>? This cannot be undone.');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="delete_id" value="<?= (int)$a['id'] ?>">
                    <button type="submit" class="btn-del">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- ── Add new admin form ── -->
  <div class="section-card">
    <div class="section-title">Add New Admin Account</div>
    <form method="post" autocomplete="off">
      <input type="hidden" name="action" value="add">
      <div class="row g-3">
        <div class="col-sm-6">
          <label for="name">First Name *</label>
          <input type="text" id="name" name="name" class="form-control"
            value="<?= htmlspecialchars((string)($_POST['name'] ?? '')) ?>" required>
        </div>
        <div class="col-sm-6">
          <label for="surname">Last Name</label>
          <input type="text" id="surname" name="surname" class="form-control"
            value="<?= htmlspecialchars((string)($_POST['surname'] ?? '')) ?>">
        </div>
        <div class="col-sm-6">
          <label for="username">Username *</label>
          <input type="text" id="username" name="username" class="form-control"
            value="<?= htmlspecialchars((string)($_POST['username'] ?? '')) ?>" required>
        </div>
        <div class="col-sm-6">
          <label for="email">Email *</label>
          <input type="email" id="email" name="email" class="form-control"
            value="<?= htmlspecialchars((string)($_POST['email'] ?? '')) ?>" required>
        </div>
        <div class="col-sm-6">
          <label for="phoneno">Phone</label>
          <input type="text" id="phoneno" name="phoneno" class="form-control"
            value="<?= htmlspecialchars((string)($_POST['phoneno'] ?? '')) ?>">
        </div>
        <div class="col-sm-6">
          <label for="password">
            Password *
            <span style="color:#888;font-weight:400;font-size:.8rem;">(min 8 characters)</span>
          </label>
          <input type="password" id="password" name="password" class="form-control"
            autocomplete="new-password" required minlength="8">
        </div>
        <div class="col-12" style="margin-top:4px;">
          <button type="submit" class="btn-add">Add Admin Account</button>
        </div>
      </div>
    </form>
  </div>

</div>
</body>
</html>
