<?php $title='Masuk'; $page='login'; require_once 'config.php';
$err = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $s = $db->prepare("SELECT * FROM users WHERE email=?"); $s->execute([trim($_POST['email'] ?? '')]);
  $u = $s->fetch();
  if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
    session_regenerate_id(true);
    $_SESSION['user'] = ['id'=>$u['id'],'nama'=>$u['nama'],'role'=>$u['role']];
    header('Location: index.php'); exit;
  }
  $err = 'Email atau kata sandi salah.';
}
require 'includes/header.php'; ?>
<section class="sec page narrow">
  <h2>Masuk</h2>
  <?php if ($err): ?><p class="err"><?= e($err) ?></p><?php endif; ?>
  <form method="post" class="form">
    <label>Email<input type="email" name="email" required></label>
    <label>Kata sandi<input type="password" name="password" required></label>
    <button class="btn">Masuk</button>
  </form>
  <p>Belum punya akun? <a href="register.php">Daftar di sini</a>.</p>
</section>
<?php require 'includes/footer.php'; ?>
