// MOSQERA - Hitung Mundur Sholat & Interaktivitas Web
(function() {
  const DEFAULT_JADWAL = [
    { nama: 'Subuh', waktu: '04:42' },
    { nama: 'Terbit', waktu: '05:58' },
    { nama: 'Dzuhur', waktu: '12:03' },
    { nama: 'Ashar', waktu: '15:18' },
    { nama: 'Maghrib', waktu: '18:08' },
    { nama: 'Isya', waktu: '19:18' }
  ];

  function getTodayAt(timeStr) {
    const d = new Date();
    if (!timeStr) return d;
    const parts = timeStr.split(':');
    d.setHours(parseInt(parts[0], 10) || 0, parseInt(parts[1], 10) || 0, 0, 0);
    return d;
  }

  function formatDigit(n) {
    return String(Math.max(0, n)).padStart(2, '0');
  }

  function updatePrayerCountdown() {
    // Ambil jadwal dari window.JADWAL atau gunakan default jika kosong
    let schedule = (window.JADWAL && Array.isArray(window.JADWAL) && window.JADWAL.length > 0)
      ? window.JADWAL
      : DEFAULT_JADWAL;

    const now = new Date();

    // Filter sholat fardhu berikutnya hari ini
    let next = schedule.find(item => getTodayAt(item.waktu) > now);
    let targetDate;

    if (next) {
      targetDate = getTodayAt(next.waktu);
    } else {
      // Jika seluruh jadwal hari ini sudah lewat (misal ba'da Isya), target adalah Subuh esok hari
      next = schedule[0];
      targetDate = getTodayAt(next.waktu);
      targetDate.setDate(targetDate.getDate() + 1);
    }

    const diffSeconds = Math.max(0, Math.floor((targetDate.getTime() - now.getTime()) / 1000));
    const hours = Math.floor(diffSeconds / 3600);
    const mins = Math.floor((diffSeconds % 3600) / 60);
    const secs = diffSeconds % 60;
    const countStr = `${formatDigit(hours)}:${formatDigit(mins)}:${formatDigit(secs)}`;

    // Perbarui teks di elemen DOM (mendukung berbagai variasi id)
    ['next-prayer-name', 'next-name'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.textContent = next.nama;
    });

    ['next-prayer-time', 'next-time'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.textContent = 'Pukul ' + next.waktu + ' WIB';
    });

    ['next-prayer-count', 'next-count'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.textContent = countStr;
    });

    // Beri tanda aktif pada kotak daftar jadwal sholat
    document.querySelectorAll('.prayer-time-box, .tbl tr').forEach(box => {
      const pName = box.getAttribute('data-name') || box.getAttribute('data-t');
      if (pName && next && (pName.toLowerCase() === next.nama.toLowerCase() || pName === next.waktu)) {
        box.classList.add('active');
        box.classList.add('now');
      } else {
        box.classList.remove('active');
        box.classList.remove('now');
      }
    });
  }

  // Jalankan segera dan set interval tiap detik
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      updatePrayerCountdown();
      setInterval(updatePrayerCountdown, 1000);
    });
  } else {
    updatePrayerCountdown();
    setInterval(updatePrayerCountdown, 1000);
  }
})();
