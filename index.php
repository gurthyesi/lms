<?php
require 'includes/config.php';
startSecureSession();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error   = '';
$success = '';
$mode    = $_GET['mode'] ?? 'login'; // login | register

// ── Handle POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();

    if ($_POST['action'] === 'login') {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $error = 'Please enter your email and password.';
        } else {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        }

    } elseif ($_POST['action'] === 'register') {
        $name     = trim($_POST['name'] ?? '');
        $surname  = trim($_POST['surname'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $lodge    = trim($_POST['lodge_name'] ?? '');
        $grand    = trim($_POST['grand_lodge'] ?? '');
        $year     = trim($_POST['year_of_registration'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!$name || !$surname || !$email || !$password) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            // Check duplicate email
            $check = $db->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'This email address is already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $db->prepare("INSERT INTO users (name, surname, email, phone, lodge_name, grand_lodge, year_of_registration, password, status) VALUES (?,?,?,?,?,?,?,?,'Member')");
                $stmt->execute([$name, $surname, $email, $phone, $lodge, $grand, $year ?: null, $hash]);
                $success = 'Account created successfully! You can now sign in.';
                $mode = 'login';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sacred Library — Sign In</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card">

    <div class="auth-logo">
      <div class="logo-icon"><i class="fa-solid fa-book-open-reader"></i></div>
      <h1>Sacred Library</h1>
      <p>Knowledge, Wisdom &amp; Brotherhood</p>
    </div>

    <?php if ($error): ?>
      <div class="alert-custom alert-danger mb-3">
        <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert-custom alert-success mb-3">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-pills mb-4 justify-content-center" style="background:var(--cream);border-radius:8px;padding:4px">
      <li class="nav-item flex-fill">
        <a href="?mode=login" class="nav-link text-center <?= $mode==='login'?'active':'' ?>"
           style="<?= $mode==='login'?'background:var(--primary);color:#fff':'color:var(--text-muted)' ?>">
          Sign In
        </a>
      </li>
      <li class="nav-item flex-fill">
        <a href="?mode=register" class="nav-link text-center <?= $mode==='register'?'active':'' ?>"
           style="<?= $mode==='register'?'background:var(--primary);color:#fff':'color:var(--text-muted)' ?>">
          Register
        </a>
      </li>
    </ul>

    <!-- Login Form -->
    <?php if ($mode === 'login'): ?>
    <form method="POST">
      <input type="hidden" name="action" value="login">

      <div class="mb-3">
        <label class="form-label"><i class="fa-solid fa-envelope me-1" style="color:var(--gold)"></i> Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="you@example.com" required
               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
      </div>

      <div class="mb-4">
        <label class="form-label"><i class="fa-solid fa-lock me-1" style="color:var(--gold)"></i> Password</label>
        <div class="input-group">
          <input type="password" name="password" id="loginPwd" class="form-control" placeholder="••••••••" required>
          <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('loginPwd',this)" style="border-radius:0 var(--radius-sm) var(--radius-sm) 0">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary-custom w-100 py-2 mb-3">
        <i class="fa-solid fa-right-to-bracket me-2"></i> Sign In
      </button>

      <p class="text-center text-muted" style="font-size:.85rem">
        Don't have an account? <a href="?mode=register">Register here</a>
      </p>
    </form>

    <!-- Register Form -->
    <?php else: ?>
    <form method="POST">
      <input type="hidden" name="action" value="register">

      <div class="row g-3">
        <div class="col-6">
          <label class="form-label">First Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" placeholder="John" required
                 value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
        </div>
        <div class="col-6">
          <label class="form-label">Surname <span class="text-danger">*</span></label>
          <input type="text" name="surname" class="form-control" placeholder="Smith" required
                 value="<?= isset($_POST['surname']) ? htmlspecialchars($_POST['surname']) : '' ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Email <span class="text-danger">*</span></label>
          <input type="email" name="email" class="form-control" placeholder="you@example.com" required
                 value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
        </div>
        <div class="col-6">
          <label class="form-label">Phone</label>
          <input type="tel" name="phone" class="form-control" placeholder="+39 000 000 0000"
                 value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
        </div>
        <div class="col-6">
          <label class="form-label">Year of Registration</label>
          <input type="number" name="year_of_registration" class="form-control" placeholder="<?= date('Y') ?>"
                 min="1900" max="<?= date('Y') ?>"
                 value="<?= isset($_POST['year_of_registration']) ? htmlspecialchars($_POST['year_of_registration']) : '' ?>">
        </div>
        <div class="col-6">
          <label class="form-label">Lodge Name</label>
          <input type="text" name="lodge_name" class="form-control" placeholder="Lodge name"
                 value="<?= isset($_POST['lodge_name']) ? htmlspecialchars($_POST['lodge_name']) : '' ?>">
        </div>
        <div class="col-6">
          <label class="form-label">Grand Lodge</label>
          <input type="text" name="grand_lodge" class="form-control" placeholder="Grand Lodge"
                 value="<?= isset($_POST['grand_lodge']) ? htmlspecialchars($_POST['grand_lodge']) : '' ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Password <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="password" name="password" id="regPwd" class="form-control" placeholder="Min. 8 characters" required>
            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('regPwd',this)">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
        </div>
        <div class="col-12">
          <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
          <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
        </div>
      </div>

      <button type="submit" class="btn btn-gold w-100 py-2 mt-4 mb-3">
        <i class="fa-solid fa-user-plus me-2"></i> Create Account
      </button>

      <p class="text-center text-muted" style="font-size:.85rem">
        Already have an account? <a href="?mode=login">Sign in</a>
      </p>
    </form>
    <?php endif; ?>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePwd(id, btn) {
  const input = document.getElementById(id);
  const icon  = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'fa-solid fa-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'fa-solid fa-eye';
  }
}
</script>
</body>
</html>
