<?php
$title = 'Pendaftaran Akun Pengurus';
$page = 'register';
require_once 'config.php';

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pw = $_POST['password'] ?? '';
    $role = 'Pengurus';

    if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Mohon isi nama lengkap dan email yang valid.';
    } elseif (strlen($pw) < 6) {
        $err = 'Kata sandi minimal 6 karakter.';
    } else {
        $check = $db->prepare("SELECT id FROM users WHERE email=?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $err = 'Email sudah terdaftar. Silakan langsung masuk.';
        } else {
            $ins = $db->prepare("INSERT INTO users (nama, email, password, role) VALUES (?,?,?,?)");
            $ins->execute([$nama, $email, password_hash($pw, PASSWORD_DEFAULT), $role]);
            header('Location: login.php?registered=1');
            exit;
        }
    }
}

require 'includes/header.php';
?>

<div class="page-container" style="max-width:540px; padding-top:40px;">
  
  <div style="text-align:center; margin-bottom:28px;">
    <span class="page-badge">Registrasi Pengurus</span>
    <h1 style="font-size:2.2rem; color:var(--primary); margin:8px 0 6px;">Daftar Akun Pengurus</h1>
    <p style="color:var(--text-muted); font-size:0.95rem; margin:0;">
      Daftarkan akun Pengurus Masjid untuk mengelola pembukuan dan sistem MOSQERA.
    </p>
  </div>

  <?php if ($err): ?>
    <div class="alert alert-error"><?= e($err) ?></div>
  <?php endif; ?>

  <div class="card" style="padding:32px 28px;">
    <form method="post">
      <div class="form-group">
        <label class="form-label">Nama Lengkap & Gelar</label>
        <input type="text" name="nama" class="form-input" placeholder="Contoh: H. Ahmad Fauzi" value="<?= e($_POST['nama'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label">Alamat Email</label>
        <input type="email" name="email" class="form-input" placeholder="nama@email.com" value="<?= e($_POST['email'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label">Kata Sandi (Minimal 6 karakter)</label>
        <input type="password" name="password" class="form-input" minlength="6" placeholder="Buat kata sandi aman" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%; padding:14px; font-size:1.05rem; font-weight:700; margin-top:8px;">
        Daftar Akun Pengurus
      </button>
    </form>

    <div style="text-align:center; margin-top:20px; font-size:0.92rem;">
      Sudah memiliki akun pengurus? <a href="login.php" style="font-weight:700; color:var(--primary);">Masuk di sini</a>
    </div>
  </div>

</div>

<?php require 'includes/footer.php'; ?>
