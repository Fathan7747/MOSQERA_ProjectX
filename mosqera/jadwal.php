<?php $title='Jadwal'; $page='jadwal'; require 'includes/header.php'; $j = jadwal($db); ?>
<section class="sec page">
  <h2>Jadwal sholat hari ini</h2>
  <p id="today" class="lead"></p>
  <table class="tbl">
    <?php foreach ($j as $r): ?>
      <tr data-t="<?= e($r['waktu']) ?>"><th><?= e($r['nama']) ?></th><td><?= e($r['waktu']) ?></td></tr>
    <?php endforeach; ?>
  </table>
</section>
<script>window.JADWAL = <?= json_encode($j) ?>;</script>
<?php require 'includes/footer.php'; ?>
      
