<?php
$title = 'Masuk / Login';
$page = 'login';
require_once 'config.php';

$allowedRoles = [
    'bendahara' => [
        'nama' => 'Admin / Bendahara',
        'sub'  => 'Input kas, verifikasi nota, kelola dana & terbitkan laporan resmi.',
        'num'  => '1',
        'color'=> '#0e2a2f'
    ],
    'pengurus' => [
        'nama' => 'Pengurus Masjid',
        'sub'  => 'Memantau saldo kas real-time dan mengelola agenda program masjid.',
        'num'  => '2',
        'color'=> '#c99a2e'
    ],
    'jamaah' => [
        'nama' => 'Jamaah (Publik)',
        'sub'  => 'Mengakses portal transparansi kas, jadwal kegiatan & berita.',
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
$success = '';

if (isset($_GET['registered'])) {
    $success = 'Pendaftaran berhasil! Silakan masuk dengan email dan kata sandi Anda.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $s = $db->prepare("SELECT * FROM users WHERE email=?");
    $s->execute([$email]);
    $u = $s->fetch();

    if ($u && password_verify($password, $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'    => $u['id'],
            'nama'  => $u['nama'],
            'email' => $u['email'],
            'role'  => $u['role']
        ];

        // Arahkan otomatis ke dashboard masing-masing role
        if (in_array($u['role'], ['bendahara', 'admin'])) {
            header('Location: dashboard_bendahara.php');
        } elseif ($u['role'] === 'pengurus') {
            header('Location: dashboard_pengurus.php');
        } else {
            header('Location: dashboard_jamaah.php');
        }
        exit;
    } else {
        $err = 'Email atau kata sandi tidak cocok. Mohon periksa kembali.';
    }
}

require 'includes/header.php';
?>

<section class="sec page narrow">
  <div style="text-align:center; margin-bottom: 24px;">
    <h2>Masuk ke MOSQERA</h2>
    <p class="text-muted">Pilih peran akun Anda untuk masuk ke sistem:</p>
  </div>

  <!-- Role Selector Radio Cards (Spacious & Clean) -->
  <div class="role-card-selector">
    <?php foreach ($allowedRoles as $k => $r): ?>
      <a href="login.php?role=<?= $k ?>" class="role-select-box <?= $role===$k ? 'active' : '' ?>">
        <span class="role-select-num" style="background:<?= $r['color'] ?>;"><?= $r['num'] ?></span>
        <div class="role-select-text">
          <strong><?= e($r['nama']) ?></strong>
          <small><?= e($r['sub']) ?></small>
        </div>
        <span class="role-select-check"><?= $role===$k ? '✓' : '' ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($success): ?><div class="alert-success"><?= e($success) ?></div><?php endif; ?>
  <?php if ($err): ?><p class="err"><?= e($err) ?></p><?php endif; ?>

  <form method="post" class="form">
    <input type="hidden" name="role" value="<?= e($role) ?>">

    <label>Email
      <input type="email" name="email" placeholder="nama@email.com" value="<?= e($_POST['email'] ?? '') ?>" required>
    </label>

    <label>Kata Sandi
      <input type="password" name="password" placeholder="••••••••" required>
    </label>

    <button class="btn" style="width:100%; margin-top:12px; padding:12px;">Masuk sebagai <?= e($roleInfo['nama']) ?></button>
  </form>

  <p style="text-align:center; margin-top:20px; font-size:0.95rem;">
    Belum punya akun? <a href="register.php?role=<?= urlencode($role) ?>"><strong>Daftar di sini</strong></a>
  </p>
</section>

<?php require 'includes/footer.php'; ?>
