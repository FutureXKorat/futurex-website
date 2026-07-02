<?php
declare(strict_types=1);
include '../database.php';
include 'auth.php';
include_once '../send_username_changed_email.php';

$success = '';
$errors  = [];

$q = trim((string)($_GET['q'] ?? ''));
$viewId = (int)($_GET['id'] ?? 0);

// ── Change a customer's username ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_username') {
    $targetId    = (int)($_POST['id'] ?? 0);
    $newUsername = trim((string)($_POST['username'] ?? ''));
    $viewId      = $targetId;

    if ($targetId <= 0) {
        $errors[] = 'Invalid user.';
    } elseif (!preg_match('/^[A-Za-z0-9._]+$/', $newUsername)) {
        $errors[] = 'Username can only include English letters, numbers, . or _';
    } elseif (strlen($newUsername) < 5) {
        $errors[] = 'Username must be at least 5 characters long.';
    }

    if (empty($errors)) {
        $cur = $conn->prepare("SELECT name, username, email FROM users WHERE id = ? LIMIT 1");
        $cur->bind_param('i', $targetId);
        $cur->execute();
        $target = $cur->get_result()->fetch_assoc();
        $cur->close();

        if (!$target) {
            $errors[] = 'User not found.';
        } elseif ($target['username'] === $newUsername) {
            $errors[] = 'That is already this user\'s username.';
        } else {
            $chk = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
            $chk->bind_param('si', $newUsername, $targetId);
            $chk->execute();
            $taken = $chk->get_result()->fetch_assoc();
            $chk->close();

            if ($taken) {
                $errors[] = 'That username is already in use.';
            } else {
                $upd = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
                $upd->bind_param('si', $newUsername, $targetId);
                if ($upd->execute()) {
                    $success = "Username changed from \"{$target['username']}\" to \"{$newUsername}\".";
                    sendUsernameChangedEmail($target['email'], $target['name'], $target['username'], $newUsername);
                } else {
                    $errors[] = 'Database error — please try again.';
                }
                $upd->close();
            }
        }
    }
}

// ── Search customers ────────────────────────────────────────────
$results = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $sql = "SELECT id, name, surname, username, email, phoneno FROM users
            WHERE name LIKE ? OR surname LIKE ? OR username LIKE ? OR email LIKE ? OR phoneno LIKE ? OR id = ?
            ORDER BY id ASC LIMIT 50";
    $stmt = $conn->prepare($sql);
    $idMatch = ctype_digit($q) ? (int)$q : 0;
    $stmt->bind_param('sssssi', $like, $like, $like, $like, $like, $idMatch);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $results[] = $r;
    $stmt->close();
}

// ── Load viewed user detail ─────────────────────────────────────
$viewUser = null;
if ($viewId > 0) {
    $stmt = $conn->prepare("SELECT id, name, surname, username, email, phoneno, verified, profile_picture, google_id, apple_id, passkey_id FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $viewId);
    $stmt->execute();
    $viewUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Users — Future X</title>
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
    .page-wrap { max-width: 900px; margin: 0 auto; padding: 36px 20px 30px; }
    .page-title    { font-size: 1.75rem; font-weight: 700; margin-bottom: 4px; }
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

    .search-row { display: flex; gap: 10px; }
    .search-row .form-control { border-radius: 12px; padding: 12px; font-size: 1rem; flex: 1; }
    .btn-search {
      padding: 12px 22px; font-size: 1rem; font-weight: 600;
      border-radius: 12px; border: none;
      background: linear-gradient(135deg, #007BFF, #0056b3);
      color: #fff; cursor: pointer; white-space: nowrap;
    }
    .btn-search:hover { background: linear-gradient(135deg, #0056b3, #003f7f); }

    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 10px 12px; text-align: left; font-size: .88rem; border-bottom: 1px solid rgba(0,0,0,.07); }
    th { font-weight: 600; color: #555; font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; }
    tr:last-child td { border-bottom: none; }

    .btn-view {
      background: none; border: 1px solid #007BFF; color: #007BFF;
      padding: 4px 14px; border-radius: 8px; font-size: .8rem;
      cursor: pointer; text-decoration: none; white-space: nowrap;
      transition: background .2s, color .2s;
    }
    .btn-view:hover { background: #007BFF; color: #fff; }

    .empty-note { color: #888; font-size: .9rem; }

    .detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-bottom: 22px; }
    .detail-item .detail-label { font-size: .75rem; color: #888; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 2px; }
    .detail-item .detail-value { font-size: .96rem; font-weight: 600; word-break: break-word; }
    .badge-verified { background: rgba(25,135,84,.12); color: #198754; padding: 3px 10px; border-radius: 20px; font-size: .78rem; font-weight: 600; }
    .badge-unverified { background: rgba(220,53,69,.12); color: #dc3545; padding: 3px 10px; border-radius: 20px; font-size: .78rem; font-weight: 600; }

    label { font-size: .88rem; font-weight: 600; }
    .form-control { border-radius: 12px; padding: 12px; font-size: 1rem; }
    .btn-add {
      display: block; width: 100%; margin-top: 12px;
      padding: 14px; font-size: 1.1rem; font-weight: 600;
      border-radius: 14px; border: none;
      background: linear-gradient(135deg, #007BFF, #0056b3);
      color: #fff; cursor: pointer; transition: all .3s ease;
    }
    .btn-add:hover {
      background: linear-gradient(135deg, #0056b3, #003f7f);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.35);
    }
  </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<?php include 'scroll-restore.php'; ?>

<div class="page-wrap">
  <h1 class="page-title">Users</h1>
  <p class="page-subtitle">Search for a customer to view or edit their account</p>

  <?php if ($success !== ''): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- ── Search ── -->
  <div class="section-card">
    <div class="section-title">Find a Customer</div>
    <form method="get" class="search-row">
      <input type="text" name="q" class="form-control" placeholder="Search by name, username, email, phone, or ID"
        value="<?= htmlspecialchars($q) ?>">
      <button type="submit" class="btn-search">Search</button>
    </form>
  </div>

  <?php if ($q !== ''): ?>
  <!-- ── Search results ── -->
  <div class="section-card">
    <div class="section-title">Results for "<?= htmlspecialchars($q) ?>"</div>
    <?php if (empty($results)): ?>
      <p class="empty-note">No matching users found.</p>
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
            <?php foreach ($results as $r): ?>
              <tr>
                <td style="color:#aaa;"><?= (int)$r['id'] ?></td>
                <td><strong><?= htmlspecialchars(trim($r['name'] . ' ' . $r['surname'])) ?></strong></td>
                <td><?= htmlspecialchars($r['username']) ?></td>
                <td><?= htmlspecialchars($r['email']) ?></td>
                <td><?= htmlspecialchars($r['phoneno']) ?></td>
                <td><a href="users.php?id=<?= (int)$r['id'] ?>&q=<?= urlencode($q) ?>" class="btn-view">View</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if ($viewId > 0): ?>
  <!-- ── User detail ── -->
  <div class="section-card">
    <?php if (!$viewUser): ?>
      <div class="section-title">User Not Found</div>
      <p class="empty-note">No user with ID <?= $viewId ?>.</p>
    <?php else: ?>
      <div class="section-title">User #<?= (int)$viewUser['id'] ?></div>
      <div class="detail-grid">
        <div class="detail-item">
          <div class="detail-label">Name</div>
          <div class="detail-value"><?= htmlspecialchars(trim($viewUser['name'] . ' ' . $viewUser['surname'])) ?></div>
        </div>
        <div class="detail-item">
          <div class="detail-label">Username</div>
          <div class="detail-value"><?= htmlspecialchars($viewUser['username']) ?></div>
        </div>
        <div class="detail-item">
          <div class="detail-label">Email</div>
          <div class="detail-value"><?= htmlspecialchars($viewUser['email']) ?></div>
        </div>
        <div class="detail-item">
          <div class="detail-label">Phone</div>
          <div class="detail-value"><?= htmlspecialchars($viewUser['phoneno']) ?></div>
        </div>
        <div class="detail-item">
          <div class="detail-label">Verified</div>
          <div class="detail-value">
            <?php if ((int)$viewUser['verified'] === 1): ?>
              <span class="badge-verified">Verified</span>
            <?php else: ?>
              <span class="badge-unverified">Not Verified</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="detail-item">
          <div class="detail-label">Login Method</div>
          <div class="detail-value">
            <?php
              if (!empty($viewUser['google_id']))       echo 'Google';
              elseif (!empty($viewUser['apple_id']))    echo 'Apple';
              elseif (!empty($viewUser['passkey_id']))  echo 'Passkey';
              else                                       echo 'Password';
            ?>
          </div>
        </div>
      </div>

      <form method="post" style="max-width:360px;">
        <input type="hidden" name="action" value="update_username">
        <input type="hidden" name="id" value="<?= (int)$viewUser['id'] ?>">
        <label for="usernameInput">Change Username</label>
        <input type="text" id="usernameInput" name="username" class="form-control"
          value="<?= htmlspecialchars($viewUser['username']) ?>" required>
        <button type="submit" class="btn-add">Save Username</button>
      </form>
      <p class="empty-note" style="margin-top:10px;">The customer will get an email letting them know their username changed.</p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</div>

</body>
</html>
