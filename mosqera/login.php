<?php
$title = 'Masuk Portal Pengurus';
$page = 'login';
require_once 'config.php';

// Jika sudah login, langsung ke dashboard admin
if (!empty($_SESSION['user'])) {
    header('Location: admin.php');
    exit;
}

$err = '';
$success = '';

if (isset($_GET['registered'])) {
    $success = 'Pendaftaran akun berhasil! Silakan masuk dengan email dan kata sandi Anda.';
}
if (isset($_GET['reset'])) {
    $success = 'Kata sandi berhasil diperbarui! Silakan masuk dengan kata sandi baru Anda.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $s = $db->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $s->execute([$email]);
    $u = $s->fetch();

    if ($u && password_verify($password, $u['password'])) {
        session_regenerate_id(true);
        $rolePengurus = (strtolower($u['role'] ?? '') === 'jamaah') ? 'jamaah' : 'Pengurus';
        $_SESSION['user'] = [
            'id'    => $u['id'],
            'nama'  => $u['nama'],
            'email' => $u['email'],
            'role'  => $rolePengurus
        ];
        header('Location: admin.php');
        exit;
    } else {
        $err = 'Email atau kata sandi tidak cocok. Mohon periksa kembali.';
    }
}

require 'includes/header.php';
?>

<div class="page-container" style="max-width:520px; padding-top:48px;">
  
  <div style="text-align:center; margin-bottom:28px;">
    <h1 style="font-size:2.2rem; color:var(--primary); margin:8px 0 6px;">Login Pengurus</h1>
    <p style="color:var(--text-muted); font-size:0.95rem; margin:0;">
      Akses pengelolaan sistem informasi dan keuangan masjid MOSQERA.
    </p>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
  <?php endif; ?>
  <?php if ($err): ?>
    <div class="alert alert-error"><?= e($err) ?></div>
  <?php endif; ?>

  <div class="card" style="padding:32px 28px;">
    <form method="post">
      <div class="form-group">
        <label class="form-label" style="font-size:1.05rem;">Alamat Email</label>
        <input type="email" name="email" class="form-input" style="padding:14px; font-size:1.05rem;" placeholder="bendahara@mosqera.id" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
      </div>

      <div class="form-group">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
          <label class="form-label" style="font-size:1.05rem; margin:0;">Kata Sandi</label>
          <a href="lupa_password.php" style="font-size:0.88rem; color:var(--primary); font-weight:600; text-decoration:none;" title="Atur ulang kata sandi">Lupa kata sandi?</a>
        </div>
        <input type="password" name="password" class="form-input" style="padding:14px; font-size:1.05rem;" placeholder="Masukkan kata sandi" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%; padding:15px; font-size:1.1rem; font-weight:700; margin-top:10px;">
        Login Pengurus
      </button>
    </form>

    <div style="text-align:center; margin-top:24px; font-size:0.92rem;">
      Belum memiliki akun pengurus? <a href="register.php" style="font-weight:700; color:var(--primary);">Daftar Pengurus Baru</a>
    </div>
  </div>

</div>

<?php require 'includes/footer.php'; ?>
