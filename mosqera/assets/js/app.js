// Menu mobile & dropdown
const burger = document.querySelector('.burger'), menu = document.getElementById('menu');
burger.addEventListener('click', () => burger.setAttribute('aria-expanded', menu.classList.toggle('open')));
const dd = document.querySelector('.dd'), ddBtn = document.querySelector('.dd-btn');
ddBtn.addEventListener('click', e => { e.stopPropagation(); ddBtn.setAttribute('aria-expanded', dd.classList.toggle('open')); });
document.addEventListener('click', () => { dd.classList.remove('open'); ddBtn.setAttribute('aria-expanded', 'false'); });

// Hitung mundur sholat berikutnya
const J = window.JADWAL || [];
const at = t => { const [h, m] = t.split(':'); const d = new Date(); d.setHours(+h, +m, 0, 0); return d; };
const pad = n => String(n).padStart(2, '0');
function tick() {
  if (!J.length) return;
  const now = new Date();
  let next = J.find(x => at(x.waktu) > now), target;
  if (next) target = at(next.waktu);
  else { next = J[0]; target = at(next.waktu); target.setDate(target.getDate() + 1); }
  const s = Math.max(0, Math.floor((target - now) / 1000));
  const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
  set('next-name', next.nama); set('next-time', 'pukul ' + next.waktu);
  set('next-count', `${pad(Math.floor(s / 3600))}:${pad(Math.floor(s % 3600 / 60))}:${pad(s % 60)}`);
  document.querySelectorAll('.tbl tr').forEach(r => r.classList.toggle('now', r.dataset.t === next.waktu));
}
const today = document.getElementById('today');
if (today) today.textContent = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
tick(); setInterval(tick, 1000);
