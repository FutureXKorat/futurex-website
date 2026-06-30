<?php
declare(strict_types=1);
include '../database.php';
include 'auth.php';

// Only the super-admin (futurexkorat@gmail.com) can manage admin accounts
if (!$isSuperAdmin) {
    header('Location: /admin/index.php'); exit;
}

// One-time migration: add AUTO_INCREMENT to admins.id if not already set
$_col = $conn->query("SHOW COLUMNS FROM admins LIKE 'id'");
$_colRow = $_col ? $_col->fetch_assoc() : null;
if ($_colRow && strpos((string)($_colRow['Extra'] ?? ''), 'auto_increment') === false) {
    $conn->query("ALTER TABLE admins MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");
}

$success = '';
$errors  = [];

// form sticky values
$f = ['name' => '', 'surname' => '', 'username' => '', 'email' => '', 'phoneno' => '', 'cc' => '+66'];

// ── Add new admin ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $f['name']     = trim((string)($_POST['name']     ?? ''));
    $f['surname']  = trim((string)($_POST['surname']  ?? ''));
    $f['username'] = trim((string)($_POST['username'] ?? ''));
    $f['email']    = strtolower(trim((string)($_POST['email'] ?? '')));
    $password      = (string)($_POST['password']         ?? '');
    $confirm       = (string)($_POST['confirm_password'] ?? '');

    // Phone: same as register.php
    $allowed_cc    = ['+66', '+60', '+856'];
    $f['cc']       = in_array($_POST['cc'] ?? '', $allowed_cc, true) ? $_POST['cc'] : '+66';
    $f['phoneno']  = trim((string)($_POST['phoneno'] ?? ''));
    $digits_only   = preg_replace('/\D+/', '', $f['phoneno']);
    $phoneno_full  = $f['cc'] . $digits_only;

    // Required
    if ($f['name'] === '' || $f['surname'] === '' || $f['username'] === '' || $f['email'] === '' || $digits_only === '' || $password === '') {
        $errors[] = 'All fields are required.';
    }

    // Username rules (same as register.php)
    if ($f['username'] !== '') {
        if (!preg_match('/^[A-Za-z0-9._]+$/', $f['username'])) {
            $errors[] = 'Username can only include English letters, numbers, . or _';
        } elseif (strlen($f['username']) < 5) {
            $errors[] = 'Username must be at least 5 characters long.';
        }
    }

    // Email must be valid and end with .com
    if ($f['email'] !== '' && (!filter_var($f['email'], FILTER_VALIDATE_EMAIL) || !preg_match('/@[^@]+\.com$/i', $f['email']))) {
        $errors[] = 'Please enter a valid email address ending with .com.';
    }

    // Phone format (same as register.php)
    if ($digits_only !== '' && !preg_match('/^\+(66|60|856)\d{7,12}$/', $phoneno_full)) {
        $errors[] = 'Invalid phone number format.';
    }

    // Password rules (same as register.php)
    if ($password !== '') {
        if (strlen($password) < 6 || strlen($password) > 12) {
            $errors[] = 'Password must be 6–12 characters.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        if (!preg_match('/[a-zA-Z]/', $password)) {
            $errors[] = 'Password must contain at least one letter.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one capital letter.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }
    }

    if (empty($errors)) {
        $chk = $conn->prepare("SELECT id FROM admins WHERE username = ? OR email = ? LIMIT 1");
        $chk->bind_param('ss', $f['username'], $f['email']);
        $chk->execute();
        $exists = $chk->get_result()->fetch_assoc();
        $chk->close();

        if ($exists) {
            $errors[] = 'That username or email is already in use.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare(
                "INSERT INTO admins (name, surname, username, email, phoneno, password, verified) VALUES (?, ?, ?, ?, ?, ?, 1)"
            );
            $ins->bind_param('ssssss', $f['name'], $f['surname'], $f['username'], $f['email'], $phoneno_full, $hashed);
            if ($ins->execute()) {
                $success = "Admin account created for {$f['name']} ({$f['username']}).";
                $f = ['name' => '', 'surname' => '', 'username' => '', 'email' => '', 'phoneno' => '', 'cc' => '+66'];
            } else {
                $errors[] = 'Database error — please try again.';
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

// CC button label helper
$ccLabel = $f['cc'] === '+60' ? '🇲🇾 +60' : ($f['cc'] === '+856' ? '🇱🇦 +856' : '🇹🇭 +66');
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

    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 10px 12px; text-align: left; font-size: .88rem; border-bottom: 1px solid rgba(0,0,0,.07); }
    th { font-weight: 600; color: #555; font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; }
    tr:last-child td { border-bottom: none; }

    .btn-del {
      background: none; border: 1px solid #dc3545; color: #dc3545;
      padding: 4px 14px; border-radius: 8px; font-size: .8rem;
      cursor: pointer; transition: background .2s, color .2s; white-space: nowrap;
    }
    .btn-del:hover { background: #dc3545; color: #fff; }

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

    .empty-note { color: #888; font-size: .9rem; }

    /* ─── Phone input ─────────────────────────────────────── */
    .cc-dropdown-wrap { position: relative; }
    .phone-wrap {
      display: flex; align-items: center;
      background: #fff; border: 1.5px solid #dee2e6;
      border-radius: 12px; overflow: visible;
      transition: border-color .2s, box-shadow .2s;
    }
    .phone-wrap:focus-within {
      border-color: #86b7fe;
      box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
    }
    .phone-cc {
      display: flex; align-items: center; gap: 5px;
      padding: 12px 10px 12px 14px;
      cursor: pointer; white-space: nowrap;
      font-weight: 600; font-size: .95rem; color: #212529;
      border-radius: 12px 0 0 12px;
      user-select: none; flex-shrink: 0;
      transition: background .15s;
    }
    .phone-cc:hover { background: rgba(0,123,255,.06); }
    .cc-chevron { transition: transform .2s; flex-shrink: 0; }
    .cc-chevron.open { transform: rotate(180deg); }
    .phone-divider { width: 1px; height: 20px; background: #dee2e6; flex-shrink: 0; }
    .phone-num-input {
      flex: 1; border: none; outline: none;
      padding: 12px 14px; font-size: 1rem;
      font-family: 'Inter', sans-serif;
      background: transparent; border-radius: 0 12px 12px 0;
      color: #212529; min-width: 0;
    }
    .phone-num-input::placeholder { color: #adb5bd; }
    .cc-dropdown {
      display: none; position: absolute; top: calc(100% + 6px); left: 0;
      z-index: 1060; background: #fff; border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0,0,0,.14); overflow: hidden; min-width: 190px;
    }
    .cc-dropdown.open { display: block; }
    .cc-opt {
      display: flex; align-items: center; gap: 10px;
      padding: 11px 16px; font-size: .95rem; font-weight: 500; cursor: pointer;
      transition: background .15s, color .15s;
    }
    .cc-opt:hover { background: #007BFF; color: #fff; }

    /* Password requirements checklist — same as register.php */
    .pw-wrap { position: relative; }
    .pw-wrap .form-control { padding-right: 2.75rem; }
    .pwd-eye {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: none; border: none; padding: 0; cursor: pointer;
      color: #6B7280; line-height: 0; user-select: none; -webkit-user-select: none;
      touch-action: none;
    }
    .pwd-eye:focus { outline: none; }
    .pwd-eye:hover { color: #374151; }
    .pw-reqs { list-style: none; margin: 7px 0 0; padding: 0; display: flex; flex-direction: column; gap: 5px; }
    .pw-req { display: flex; align-items: center; gap: 8px; font-size: 0.79rem; color: #9ca3af; transition: color .2s; }
    .pw-req.met { color: #15803d; }
    .pw-req-dot {
      width: 17px; height: 17px; border-radius: 50%; border: 2px solid #d1d5db;
      display: inline-flex; align-items: center; justify-content: center;
      flex-shrink: 0; transition: background .2s, border-color .2s;
    }
    .pw-req.met .pw-req-dot { background: #16a34a; border-color: #16a34a; }
    .pw-req-dot svg { display: none; }
    .pw-req.met .pw-req-dot svg { display: block; }
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
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
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
    <form method="post" autocomplete="off" id="addAdminForm">
      <input type="hidden" name="action" value="add">
      <div class="row g-3">

        <div class="col-sm-6">
          <label for="name">First Name</label>
          <input type="text" id="name" name="name" class="form-control"
            value="<?= htmlspecialchars($f['name']) ?>" required>
        </div>

        <div class="col-sm-6">
          <label for="surname">Last Name</label>
          <input type="text" id="surname" name="surname" class="form-control"
            value="<?= htmlspecialchars($f['surname']) ?>" required>
        </div>

        <div class="col-sm-6">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" class="form-control"
            value="<?= htmlspecialchars($f['username']) ?>" required>
        </div>

        <div class="col-sm-6">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" class="form-control"
            value="<?= htmlspecialchars($f['email']) ?>" required>
        </div>

        <!-- Phone with country code -->
        <div class="col-sm-6">
          <label>Phone Number</label>
          <div class="cc-dropdown-wrap">
            <div class="phone-wrap">
              <div class="phone-cc" id="ccToggle">
                <span id="selectedCC"><?= $ccLabel ?></span>
                <svg class="cc-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
              </div>
              <div class="phone-divider"></div>
              <input type="tel" name="phoneno" class="phone-num-input"
                value="<?= htmlspecialchars($f['phoneno']) ?>"
                placeholder="Phone Number" required>
            </div>
            <input type="hidden" name="cc" id="ccInput" value="<?= htmlspecialchars($f['cc']) ?>">
            <div class="cc-dropdown" id="ccDropdown">
              <div class="cc-opt" data-cc="+66" data-label="🇹🇭 +66">🇹🇭 &nbsp;+66 &nbsp; Thailand</div>
              <div class="cc-opt" data-cc="+60" data-label="🇲🇾 +60">🇲🇾 &nbsp;+60 &nbsp; Malaysia</div>
              <div class="cc-opt" data-cc="+856" data-label="🇱🇦 +856">🇱🇦 &nbsp;+856 &nbsp; Laos</div>
            </div>
          </div>
        </div>

        <!-- Password + requirements -->
        <div class="col-sm-6">
          <label for="pwInput">Password</label>
          <div class="pw-wrap">
            <input type="password" id="pwInput" name="password" class="form-control"
              autocomplete="new-password" required>
            <button type="button" class="pwd-eye" aria-label="Hold to show password">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <ul class="pw-reqs">
            <li class="pw-req" id="req-min">
              <span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
              <span>At least 6 characters</span>
            </li>
            <li class="pw-req" id="req-max">
              <span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
              <span>No more than 12 characters</span>
            </li>
            <li class="pw-req" id="req-num">
              <span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
              <span>At least one number</span>
            </li>
            <li class="pw-req" id="req-letter">
              <span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
              <span>At least one letter</span>
            </li>
            <li class="pw-req" id="req-upper">
              <span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
              <span>At least one capital letter</span>
            </li>
          </ul>
        </div>

        <!-- Confirm password -->
        <div class="col-sm-6">
          <label for="cfInput">Confirm Password</label>
          <div class="pw-wrap">
            <input type="password" id="cfInput" name="confirm_password" class="form-control"
              autocomplete="new-password" required>
            <button type="button" class="pwd-eye" aria-label="Hold to show password">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <ul class="pw-reqs">
            <li class="pw-req" id="req-match">
              <span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
              <span>Passwords match</span>
            </li>
          </ul>
        </div>

        <div class="col-12">
          <button type="submit" class="btn-add">Add Admin Account</button>
        </div>
      </div>
    </form>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Hold-to-show password eyes
document.querySelectorAll('.pwd-eye').forEach(function(btn) {
    var inp = btn.previousElementSibling;
    btn.addEventListener('mousedown',  function()  { inp.type = 'text'; });
    btn.addEventListener('mouseup',    function()  { inp.type = 'password'; });
    btn.addEventListener('mouseleave', function()  { inp.type = 'password'; });
    btn.addEventListener('touchstart', function(e) { e.preventDefault(); inp.type = 'text'; }, { passive: false });
    btn.addEventListener('touchend',   function()  { inp.type = 'password'; });
});

// Live password requirements check
var pwInput = document.getElementById('pwInput');
var cfInput = document.getElementById('cfInput');
function checkReqs() {
    var v = pwInput.value;
    var set = function(id, met) {
        var el = document.getElementById(id);
        if (el) el.classList.toggle('met', met);
    };
    set('req-min',    v.length >= 6);
    set('req-max',    v.length > 0 && v.length <= 12);
    set('req-num',    /[0-9]/.test(v));
    set('req-letter', /[a-zA-Z]/.test(v));
    set('req-upper',  /[A-Z]/.test(v));
    checkMatch();
}
function checkMatch() {
    var el = document.getElementById('req-match');
    if (el) el.classList.toggle('met', cfInput.value.length > 0 && cfInput.value === pwInput.value);
}
if (pwInput) pwInput.addEventListener('input', checkReqs);
if (cfInput) cfInput.addEventListener('input', checkMatch);

// Country code custom dropdown
(function() {
    var toggle   = document.getElementById('ccToggle');
    var dropdown = document.getElementById('ccDropdown');
    var hidden   = document.getElementById('ccInput');
    var display  = document.getElementById('selectedCC');
    if (!toggle || !dropdown) return;
    var chevron  = toggle.querySelector('.cc-chevron');

    toggle.addEventListener('click', function(e) {
        e.stopPropagation();
        var open = dropdown.classList.toggle('open');
        if (chevron) chevron.classList.toggle('open', open);
    });

    dropdown.querySelectorAll('.cc-opt').forEach(function(opt) {
        opt.addEventListener('click', function(e) {
            e.stopPropagation();
            display.textContent = this.dataset.label;
            hidden.value = this.dataset.cc;
            dropdown.classList.remove('open');
            if (chevron) chevron.classList.remove('open');
        });
    });

    document.addEventListener('click', function() {
        dropdown.classList.remove('open');
        if (chevron) chevron.classList.remove('open');
    });
})();
</script>
</body>
</html>
