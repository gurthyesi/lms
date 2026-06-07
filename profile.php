<?php
require 'includes/config.php';
require 'includes/layout.php';
$user = requireLogin();
$db   = getDB();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name    = trim($_POST['name'] ?? '');
        $surname = trim($_POST['surname'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $lodge   = trim($_POST['lodge_name'] ?? '');
        $grand   = trim($_POST['grand_lodge'] ?? '');
        $year    = trim($_POST['year_of_registration'] ?? '');

        if (!$name || !$surname) {
            $error = 'Name and Surname are required.';
        } else {
            $avatarPath = $user['avatar'];

            // Handle avatar upload
            if (!empty($_FILES['avatar']['name'])) {
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                    $error = 'Invalid image format. Use JPG, PNG, GIF, or WebP.';
                } elseif ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
                    $error = 'Image must be under 5MB.';
                } else {
                    $dir = UPLOAD_PATH . 'avatars/';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    $filename = 'avatar_' . $user['id'] . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . $filename)) {
                        $avatarPath = 'avatars/' . $filename;
                    }
                }
            }

            if (!$error) {
                $stmt = $db->prepare("UPDATE users SET name=?,surname=?,phone=?,address=?,lodge_name=?,grand_lodge=?,year_of_registration=?,avatar=? WHERE id=?");
                $stmt->execute([$name,$surname,$phone,$address,$lodge,$grand,$year ?: null,$avatarPath,$user['id']]);
                $success = 'Profile updated successfully!';
                $user = getCurrentUser(); // reload
            }
        }

    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$current || !$new || !$confirm) {
            $error = 'All password fields are required.';
        } elseif (!password_verify($current, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $db->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->execute([$hash, $user['id']]);
            $success = 'Password changed successfully!';
        }
    }
}

$initials = strtoupper(substr($user['name'],0,1) . substr($user['surname'],0,1));
$avatar   = $user['avatar'] ? APP_URL . '/assets/uploads/' . $user['avatar'] : null;

layout_head('My Profile');
layout_sidebar($user);
layout_topbar('My Profile', $user);
?>

<div class="page-content">

  <?php if ($success): ?>
    <div class="alert-custom alert-success mb-3 auto-dismiss">
      <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert-custom alert-danger mb-3 auto-dismiss">
      <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <div class="row g-4">

    <!-- Profile Info Card -->
    <div class="col-12 col-lg-8">
      <div class="card">
        <div class="card-header-custom">
          <h5><i class="fa-solid fa-circle-user"></i> Profile Information</h5>
        </div>
        <div class="card-body-custom">
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_profile">

            <!-- Avatar -->
            <div class="text-center mb-4">
              <div class="profile-avatar-wrapper">
                <?php if ($avatar): ?>
                  <img src="<?= $avatar ?>?v=<?= time() ?>" class="profile-avatar" id="avatarPreview" alt="Avatar">
                <?php else: ?>
                  <div class="profile-avatar-initials" id="avatarInitials"><?= $initials ?></div>
                  <img id="avatarPreview" class="profile-avatar" style="display:none" alt="Avatar">
                <?php endif; ?>
              </div>
              <div class="mt-3">
                <label for="avatarInput" class="btn btn-outline-custom btn-sm" style="cursor:pointer">
                  <i class="fa-solid fa-camera me-1"></i> Change Photo
                </label>
                <input type="file" id="avatarInput" name="avatar" accept="image/*" class="d-none"
                       onchange="previewImage(this,'avatarPreview'); document.getElementById('avatarInitials') && (document.getElementById('avatarInitials').style.display='none'); document.getElementById('avatarPreview').style.display='block'">
                <p class="text-muted mt-1" style="font-size:.75rem">Square image recommended. Max 5MB.</p>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Surname <span class="text-danger">*</span></label>
                <input type="text" name="surname" class="form-control" value="<?= htmlspecialchars($user['surname']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled
                       style="background:var(--cream);color:var(--text-muted)">
                <small class="text-muted">Email cannot be changed.</small>
              </div>
              <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+39 000 000 0000">
              </div>
              <div class="col-12">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2" placeholder="Your address..."><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label">Lodge Name</label>
                <input type="text" name="lodge_name" class="form-control" value="<?= htmlspecialchars($user['lodge_name'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Grand Lodge</label>
                <input type="text" name="grand_lodge" class="form-control" value="<?= htmlspecialchars($user['grand_lodge'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Year of Registration</label>
                <input type="number" name="year_of_registration" class="form-control"
                       value="<?= htmlspecialchars($user['year_of_registration'] ?? '') ?>"
                       min="1900" max="<?= date('Y') ?>" placeholder="<?= date('Y') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Membership Status</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['status']) ?>" disabled
                       style="background:var(--cream);color:var(--text-muted)">
              </div>
            </div>

            <div class="mt-4">
              <button type="submit" class="btn btn-gold">
                <i class="fa-solid fa-floppy-disk me-2"></i> Save Changes
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Change Password -->
    <div class="col-12 col-lg-4">
      <div class="card">
        <div class="card-header-custom">
          <h5><i class="fa-solid fa-lock"></i> Change Password</h5>
        </div>
        <div class="card-body-custom">
          <form method="POST">
            <input type="hidden" name="action" value="change_password">

            <div class="mb-3">
              <label class="form-label">Current Password</label>
              <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="mb-3">
              <label class="form-label">New Password</label>
              <input type="password" name="new_password" class="form-control" placeholder="Min. 8 characters" required>
            </div>
            <div class="mb-4">
              <label class="form-label">Confirm New Password</label>
              <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
            </div>

            <button type="submit" class="btn btn-primary-custom w-100">
              <i class="fa-solid fa-key me-2"></i> Update Password
            </button>
          </form>
        </div>
      </div>

      <!-- Membership Info -->
      <div class="card mt-3">
        <div class="card-header-custom">
          <h5><i class="fa-solid fa-id-card"></i> Membership</h5>
        </div>
        <div class="card-body-custom">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span style="font-size:.85rem;color:var(--text-muted)">Status</span>
            <span class="badge-status <?= $user['status'] ?>"><?= $user['status'] ?></span>
          </div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span style="font-size:.85rem;color:var(--text-muted)">Annual Fee</span>
            <span style="font-weight:700;color:var(--gold-dark)">
              <?= $user['status'] === 'Founder' ? '€50' : ($user['status'] === 'Member' ? '€10' : 'N/A') ?>
            </span>
          </div>
          <div class="d-flex align-items-center justify-content-between">
            <span style="font-size:.85rem;color:var(--text-muted)">Member Since</span>
            <span style="font-weight:700;color:var(--primary)"><?= htmlspecialchars($user['year_of_registration'] ?? '—') ?></span>
          </div>

          <?php if ($user['status'] === 'Member'): ?>
          <hr>
          <div style="background:rgba(201,168,76,0.08);border:1px solid rgba(201,168,76,0.2);border-radius:8px;padding:12px;font-size:.82rem;text-align:center">
            <i class="fa-solid fa-star" style="color:var(--gold)"></i>
            <strong style="color:var(--primary)"> Become a Founder</strong><br>
            <span style="color:var(--text-muted)">Support our platform for just €50/year and enjoy founder recognition.</span>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>

<?php layout_scripts(); ?>
