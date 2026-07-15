<?php
/**
 * login.php - single login page for all three roles.
 *
 * The look of this page is based on the uploaded loginclinic.html design
 * (green side panel + role selector), but here it is connected to the
 * database and uses secure password checking.
 */
require_once __DIR__ . '/includes/init.php';

// If already logged in, go straight to the right dashboard.
if (is_logged_in()) {
    redirect(dashboard_for_role(current_role()));
}

$error = '';
$email = '';
$selectedRole = 'healthworker';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';
    $selectedRole = $_POST['role'] ?? 'healthworker';

    if ($email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Incorrect email or password. Please try again.';
        } elseif ($user['status'] !== 'active') {
            $error = 'This account has been deactivated. Please contact the administrator.';
        } elseif ($user['role'] !== $selectedRole) {
            $error = 'This is not a ' . ucfirst($selectedRole) . ' account. Please pick the correct role.';
        } else {
            login_user($user);
            redirect(dashboard_for_role($user['role']));
        }
    }
}

$roleMeta = [
    'admin'        => ['label' => 'Admin',         'desc' => 'Manage users and system settings', 'placeholder' => 'admin@dams.com'],
    'healthworker' => ['label' => 'Health Worker', 'desc' => 'Manage and view patient records',  'placeholder' => 'healthworker@clinic.com'],
    'patient'      => ['label' => 'Patient',       'desc' => 'View your antenatal information',   'placeholder' => 'patient@gmail.com'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - Digital Antenatal Monitoring System</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="<?= BASE_URL ?>/assets/css/login.css" rel="stylesheet" />
</head>
<body>

  <!-- LEFT PANEL -->
  <div class="left-panel">
    <img class="bg-photo"
      src="https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=800&h=1100&fit=crop&auto=format"
      alt="Antenatal care" />

    <div class="logo">
      <div class="logo-icon">
        <svg xmlns="http://www.w3.org/2000/svg" fill="white" viewBox="0 0 24 24" width="22" height="22">
          <path d="M12 21.593c-5.63-5.539-11-10.297-11-14.402 0-3.791 3.068-5.191 5.281-5.191 1.312 0 4.151.501 5.719 4.457 1.59-3.968 4.464-4.447 5.726-4.447 2.54 0 5.274 1.621 5.274 5.181 0 4.069-5.136 8.625-11 14.402z"/>
        </svg>
      </div>
      <div>
        <div class="logo-text">Digital Antenatal Monitoring System</div>
        <div class="logo-sub">DAMS &mdash; Health Information System</div>
      </div>
    </div>

    <div class="hero">
      <h1>Better Care for<br />Every Mother</h1>
      <p>A digital system designed to improve how antenatal health data is collected, stored and accessed by healthcare providers and patients.</p>
    </div>

    <div class="info-boxes">
      <div class="info-box"><strong>3 User Roles</strong><span>Admin, Worker, Patient</span></div>
      <div class="info-box"><strong>Secure Login</strong><span>Email-based access</span></div>
      <div class="info-box"><strong>Health Records</strong><span>Anytime access</span></div>
    </div>
  </div>

  <!-- RIGHT PANEL -->
  <div class="right-panel">
    <div class="form-wrapper">

      <div class="mobile-logo">
        <div class="logo-icon">
          <svg xmlns="http://www.w3.org/2000/svg" fill="white" viewBox="0 0 24 24" width="18" height="18">
            <path d="M12 21.593c-5.63-5.539-11-10.297-11-14.402 0-3.791 3.068-5.191 5.281-5.191 1.312 0 4.151.501 5.719 4.457 1.59-3.968 4.464-4.447 5.726-4.447 2.54 0 5.274 1.621 5.274 5.181 0 4.069-5.136 8.625-11 14.402z"/>
          </svg>
        </div>
        <div class="mobile-logo-text">Digital Antenatal Monitoring System</div>
      </div>

      <div class="card">
        <h2>Login to Your Account</h2>
        <p class="subtitle">Select your role and enter your credentials below</p>

        <p class="role-label">Select Role</p>
        <div class="role-grid">
          <?php foreach ($roleMeta as $key => $meta): ?>
            <button type="button" class="role-btn <?= $selectedRole === $key ? 'active' : '' ?>"
              data-role="<?= $key ?>" onclick="selectRole('<?= $key ?>')"><?= $meta['label'] ?></button>
          <?php endforeach; ?>
        </div>
        <p class="role-desc" id="role-desc"><?= e($roleMeta[$selectedRole]['desc']) ?></p>

        <form id="login-form" method="post" action="<?= BASE_URL ?>/login.php" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="role" id="role-input" value="<?= e($selectedRole) ?>">

          <div class="field">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>"
              placeholder="<?= e($roleMeta[$selectedRole]['placeholder']) ?>" autocomplete="email" />
          </div>

          <div class="field">
            <div class="field-header">
              <label for="password">Password</label>
              <a href="#" class="forgot-link">Forgot password?</a>
            </div>
            <div class="input-wrap">
              <input type="password" id="password" name="password"
                placeholder="Enter your password" autocomplete="current-password" />
              <button type="button" class="toggle-pw" onclick="togglePassword()" aria-label="Show or hide password">
                <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
              </button>
            </div>
          </div>

          <div class="error-box <?= $error ? 'show' : '' ?>" id="error-box"><?= e($error) ?></div>

          <button type="submit" class="submit-btn" id="submit-btn">
            <span id="btn-label">Login as <?= e($roleMeta[$selectedRole]['label']) ?></span>
          </button>
        </form>

        <div class="demo-note">
          <strong>Demo accounts</strong> (password: <code>password123</code>)<br>
          admin@dams.com &middot; healthworker@clinic.com &middot; patient@gmail.com
        </div>
      </div>

      <p class="footer-note">This system is for authorized users only. &copy; <?= date('Y') ?> DAMS Project</p>
    </div>
  </div>

  <script>
    const roleMeta = <?= json_encode($roleMeta) ?>;

    function selectRole(role) {
      document.getElementById('role-input').value = role;
      document.querySelectorAll('.role-btn').forEach(function (btn) {
        btn.classList.toggle('active', btn.dataset.role === role);
      });
      document.getElementById('role-desc').textContent = roleMeta[role].desc;
      document.getElementById('email').placeholder = roleMeta[role].placeholder;
      document.getElementById('btn-label').textContent = 'Login as ' + roleMeta[role].label;
    }

    function togglePassword() {
      var input = document.getElementById('password');
      var isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      var icon = document.getElementById('eye-icon');
      if (isHidden) {
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>';
      } else {
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>';
      }
    }
  </script>
</body>
</html>
