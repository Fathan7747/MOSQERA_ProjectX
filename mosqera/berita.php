<?php $title='Berita'; $page='berita'; require 'includes/header.php';
$list = $db->query("SELECT * FROM berita ORDER BY tanggal DESC, id DESC")->fetchAll(); ?>
<section class="sec page">
  <h2>Berita</h2>
  <?php if (!$list): ?><p>Belum ada berita. Data akan tampil di sini setelah ditambahkan.</p><?php endif; ?>
  <?php foreach ($list as $b): ?>
    <article class="news">
      <time><?= date('d M Y', strtotime($b['tanggal'])) ?></time>
      <h3><?= e($b['judul']) ?></h3>
      <p><?= nl2br(e($b['isi'])) ?></p>
    </article>
  <?php endforeach; ?>
</section>
<?php require 'includes/footer.php'; ?>
