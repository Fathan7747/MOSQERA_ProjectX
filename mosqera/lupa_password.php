<?php
$title = 'Ubah Kata Sandi';
$page = 'lupa_password';
require_once 'config.php';

// Jika sudah login, langsung ke halaman admin
if (!empty($_SESSION['user'])) {
    header('Location: admin.php');
    exit;
}

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pwBaru = $_POST['password_baru'] ?? '';
    $pwKonfirmasi = $_POST['konfirmasi_password'] ?? '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Mohon masukkan alamat email yang valid.';
    } elseif (strlen($pwBaru) < 8) {
        $err = 'Kata sandi baru minimal 8 karakter.';
    } elseif ($pwBaru !== $pwKonfirmasi) {
        $err = 'Konfirmasi kata sandi baru tidak cocok dengan kata sandi baru.';
    } else {
        // Cek apakah akun dengan email tersebut ada
        $stmt = $db->prepare("SELECT id, nama FROM users WHERE email=? LIMIT 1");
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if (!$u) {
            $err = 'Alamat email tidak ditemukan dalam sistem akun pengurus.';
        } else {
            // Perbarui kata sandi dengan hash aman
            $hashed = password_hash($pwBaru, PASSWORD_DEFAULT);
            $upd = $db->prepare("UPDATE users SET password=? WHERE email=?");
            $upd->execute([$hashed, $email]);

            header('Location: login.php?reset=1');
            exit;
        }
    }
}

require 'includes/header.php';
?>

<div class="page-container" style="max-width:520px; padding-top:48px;">
  
  <div style="text-align:center; margin-bottom:28px;">
    <span class="page-badge">Pemulihan Akun</span>
    <h1 style="font-size:2.2rem; color:var(--primary); margin:8px 0 6px;">Ubah Kata Sandi</h1>
    <p style="color:var(--text-muted); font-size:0.95rem; margin:0;">
      Perbarui kata sandi akun pengurus Anda untuk dapat mengakses kembali portal MOSQERA.
    </p>
  </div>

  <?php if ($err): ?>
    <div class="alert alert-error"><?= e($err) ?></div>
  <?php endif; ?>

  <div class="card" style="padding:32px 28px;">
    <form method="post" data-confirm="Simpan kata sandi baru untuk akun pengurus Anda?" data-confirm-title="Ubah Kata Sandi" data-confirm-btn="Simpan" data-confirm-type="primary">
      <div class="form-group">
        <label class="form-label" style="font-size:1.02rem;">Alamat Email</label>
        <input type="email" name="email" class="form-input" style="padding:14px; font-size:1rem;" placeholder="Email" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" style="font-size:1.02rem;">Kata Sandi Baru (Minimal 8 karakter)</label>
        <div class="password-wrapper">
          <input type="password" name="password_baru" class="form-input" style="padding:14px; font-size:1rem;" placeholder="Kata Sandi Baru" minlength="8" required>
          <button type="button" class="btn-toggle-password" aria-label="Tampilkan kata sandi" title="Tampilkan / Sembunyikan Kata Sandi">
            <svg class="eye-icon eye-show" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <svg class="eye-icon eye-hide" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
              <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>
          </button>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" style="font-size:1.02rem;">Konfirmasi Kata Sandi Baru</label>
        <div class="password-wrapper">
          <input type="password" name="konfirmasi_password" class="form-input" style="padding:14px; font-size:1rem;" placeholder="Konfirmasi Kata Sandi Baru" minlength="8" required>
          <button type="button" class="btn-toggle-password" aria-label="Tampilkan kata sandi" title="Tampilkan / Sembunyikan Kata Sandi">
            <svg class="eye-icon eye-show" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <svg class="eye-icon eye-hide" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
              <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%; padding:15px; font-size:1.05rem; font-weight:700; margin-top:10px;">
        Simpan Kata Sandi Baru
      </button>
    </form>

    <div style="text-align:center; margin-top:24px; font-size:0.92rem;">
      Sudah ingat kata sandi Anda? <a href="login.php" style="font-weight:700; color:var(--primary);">Kembali ke Halaman Masuk</a>
    </div>
  </div>

</div>

<?php require 'includes/footer.php'; ?>
