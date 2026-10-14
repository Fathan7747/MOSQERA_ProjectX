<?php
$title = 'Daftar Akun';
$page = 'register';
require_once 'config.php';

$allowedRoles = [
    'bendahara' => [
        'nama' => 'Admin / Bendahara',
        'sub'  => 'Input transaksi, verifikasi nota, kelola kategori dana & terbitkan laporan resmi.',
        'num'  => '1',
        'color'=> '#0e2a2f'
    ],
    'pengurus' => [
        'nama' => 'Pengurus Masjid',
        'sub'  => 'Memantau saldo kas real-time, mengelola program & agenda yang butuh anggaran.',
        'num'  => '2',
        'color'=> '#c99a2e'
    ],
    'jamaah' => [
        'nama' => 'Jamaah (Publik)',
        'sub'  => 'Melihat laporan keuangan terbuka, jadwal kegiatan & kajian masjid.',
        'num'  => '3',
        'color'=> '#0e2a2f'
    ]
];

$role = $_GET['role'] ?? ($_POST['role'] ?? 'jamaah');
if (!array_key_exists($role, $allowedRoles)) {
    $role = 'jamaah';
}

$roleInfo = $allowedRoles[$role];
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pw = $_POST['password'] ?? '';
    $rolePost = trim($_POST['role'] ?? 'jamaah');
    if (!array_key_exists($rolePost, $allowedRoles)) {
        $rolePost = 'jamaah';
    }

    if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Mohon isi nama lengkap dan alamat email yang valid.';
    } elseif (strlen($pw) < 6) {
        $err = 'Kata sandi minimal 6 karakter.';
    } else {
        $c = $db->prepare("SELECT id FROM users WHERE email=?");
        $c->execute([$email]);
        if ($c->fetch()) {
            $err = 'Email sudah terdaftar. Silakan langsung masuk.';
        } else {
            $ins = $db->prepare("INSERT INTO users (nama, email, password, role) VALUES (?,?,?,?)");
            $ins->execute([$nama, $email, password_hash($pw, PASSWORD_DEFAULT), $rolePost]);
            header('Location: login.php?role=' . urlencode($rolePost) . '&registered=1');
            exit;
        }
    }
}

require 'includes/header.php';
?>

<section class="sec page narrow">
  <div style="text-align:center; margin-bottom: 24px;">
    <h2>Daftar Akun Baru</h2>
    <p class="text-muted">Pilih peran akun yang sesuai sebelum mengisi formulir:</p>
  </div>

  <!-- Role Selector Radio Cards (Spacious & Clean) -->
  <div class="role-card-selector">
    <?php foreach ($allowedRoles as $k => $r): ?>
      <a href="register.php?role=<?= $k ?>" class="role-select-box <?= $role===$k ? 'active' : '' ?>">
        <span class="role-select-num" style="background:<?= $r['color'] ?>;"><?= $r['num'] ?></span>
        <div class="role-select-text">
          <strong><?= e($r['nama']) ?></strong>
          <small><?= e($r['sub']) ?></small>
        </div>
        <span class="role-select-check"><?= $role===$k ? '✓' : '' ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($err): ?><p class="err"><?= e($err) ?></p><?php endif; ?>

  <form method="post" class="form">
    <input type="hidden" name="role" value="<?= e($role) ?>">
    
    <label>Nama Lengkap
      <input type="text" name="nama" placeholder="Contoh: Fathur Rahman" value="<?= e($_POST['nama'] ?? '') ?>" required>
    </label>

    <label>Email
      <input type="email" name="email" placeholder="nama@email.com" value="<?= e($_POST['email'] ?? '') ?>" required>
    </label>

    <label>Kata Sandi (minimal 6 karakter)
      <input type="password" name="password" minlength="6" placeholder="••••••••" required>
    </label>

    <button class="btn" style="width:100%; margin-top:12px; padding:12px;">Daftar sebagai <?= e($roleInfo['nama']) ?></button>
  </form>

  <p style="text-align:center; margin-top:20px; font-size:0.95rem;">
    Sudah punya akun? <a href="login.php?role=<?= urlencode($role) ?>"><strong>Masuk di sini</strong></a>
  </p>
</section>

<?php require 'includes/footer.php'; ?>
