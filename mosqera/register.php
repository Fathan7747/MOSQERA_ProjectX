<?php $title='Daftar'; $page='login'; require_once 'config.php';
$err = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $nama = trim($_POST['nama'] ?? ''); $email = trim($_POST['email'] ?? ''); $pw = $_POST['password'] ?? '';
  if ($nama==='' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Isi nama dan email yang valid.';
  elseif (strlen($pw) < 6) $err = 'Kata sandi minimal 6 karakter.';
  else {
    $c = $db->prepare("SELECT id FROM users WHERE email=?"); $c->execute([$email]);
    if ($c->fetch()) $err = 'Email sudah terdaftar. Silakan masuk.';
    else {
      $db->prepare("INSERT INTO users (nama,email,password) VALUES (?,?,?)")
         ->execute([$nama,$email,password_hash($pw, PASSWORD_DEFAULT)]);
      header('Location: login.php'); exit;
    }
  }
}
require 'includes/header.php'; ?>
<section class="sec page narrow">
  <h2>Daftar akun</h2>
  <?php if ($err): ?><p class="err"><?= e($err) ?></p><?php endif; ?>
  <form method="post" class="form">
    <label>Nama lengkap<input name="nama" required></label>
    <label>Email<input type="email" name="email" required></label>
    <label>Kata sandi<input type="password" name="password" minlength="6" required></label>
    <button class="btn">Buat akun</button>
  </form>
</section>
<?php require 'includes/footer.php'; ?>
