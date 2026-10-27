// MOSQERA - Interaktivitas Web Modern, Modal Konfirmasi, Toast & Hitung Mundur Sholat
(function() {
  'use strict';

  // =========================================================================
  // 1. SISTEM MODAL KONFIRMASI & TOAST INTERAKTIF (MosqeraUI)
  // =========================================================================
  const MosqeraUI = {
    modalBackdrop: null,
    modalDialog: null,
    modalTitle: null,
    modalDesc: null,
    modalIcon: null,
    btnCancel: null,
    btnConfirm: null,
    currentResolve: null,
    toastContainer: null,

    // Inisialisasi Elemen Modal dan Toast ke DOM
    init: function() {
      this.createModalDOM();
      this.createToastDOM();
      this.bindGlobalEvents();
      this.checkPageAlerts();
    },

    createModalDOM: function() {
      if (document.getElementById('mosqeraModalBackdrop')) return;

      const backdrop = document.createElement('div');
      backdrop.id = 'mosqeraModalBackdrop';
      backdrop.className = 'mosqera-modal-backdrop';
      backdrop.setAttribute('aria-hidden', 'true');

      backdrop.innerHTML = `
        <div class="mosqera-modal-dialog" role="dialog" aria-modal="true">
          <div class="mosqera-modal-icon-wrapper primary" id="mosqeraModalIcon">
            <!-- Icon dinamis via JS -->
          </div>
          <h3 class="mosqera-modal-title" id="mosqeraModalTitle">Konfirmasi Tindakan</h3>
          <p class="mosqera-modal-desc" id="mosqeraModalDesc">Apakah Anda yakin ingin melanjutkan tindakan ini?</p>
          <div class="mosqera-modal-actions">
            <button type="button" class="mosqera-modal-btn mosqera-modal-btn-cancel" id="mosqeraModalCancelBtn">Batal</button>
            <button type="button" class="mosqera-modal-btn mosqera-modal-btn-confirm primary" id="mosqeraModalConfirmBtn">Lanjutkan</button>
          </div>
        </div>
      `;

      document.body.appendChild(backdrop);

      this.modalBackdrop = backdrop;
      this.modalTitle = document.getElementById('mosqeraModalTitle');
      this.modalDesc = document.getElementById('mosqeraModalDesc');
      this.modalIcon = document.getElementById('mosqeraModalIcon');
      this.btnCancel = document.getElementById('mosqeraModalCancelBtn');
      this.btnConfirm = document.getElementById('mosqeraModalConfirmBtn');

      // Event listener tombol Batal
      this.btnCancel.addEventListener('click', () => this.close(false));

      // Event listener tombol Konfirmasi
      this.btnConfirm.addEventListener('click', () => {
        // Tampilkan animasi loading pada tombol konfirmasi
        this.btnConfirm.classList.add('is-loading');
        const originalText = this.btnConfirm.textContent;
        this.btnConfirm.innerHTML = `
          <svg class="mosqera-spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
          </svg>
          <span>Memproses...</span>
        `;
        this.btnCancel.style.pointerEvents = 'none';

        setTimeout(() => {
          this.close(true);
        }, 120);
      });

      // Tutup modal jika klik di luar box (backdrop) atau tekan tombol Escape
      backdrop.addEventListener('click', (e) => {
        if (e.target === backdrop) this.close(false);
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && backdrop.classList.contains('is-open')) {
          this.close(false);
        }
      });
    },

    createToastDOM: function() {
      if (document.getElementById('mosqeraToastContainer')) return;
      const tc = document.createElement('div');
      tc.id = 'mosqeraToastContainer';
      tc.className = 'mosqera-toast-container';
      document.body.appendChild(tc);
      this.toastContainer = tc;
    },

    // Menampilkan Modal Konfirmasi (Promise-based)
    confirm: function(options) {
      return new Promise((resolve) => {
        this.currentResolve = resolve;

        const title = options.title || 'Konfirmasi Tindakan';
        const message = options.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
        const confirmText = options.confirmText || 'Lanjutkan';
        const cancelText = options.cancelText || 'Batal';
        const type = options.type || 'primary'; // 'primary' | 'danger' | 'warning' | 'success'

        this.modalTitle.textContent = title;
        this.modalDesc.textContent = message;
        this.btnCancel.textContent = cancelText;
        this.btnCancel.style.pointerEvents = 'auto';

        // Set tombol konfirmasi
        this.btnConfirm.className = `mosqera-modal-btn mosqera-modal-btn-confirm ${type}`;
        this.btnConfirm.innerHTML = `<span>${confirmText}</span>`;

        // Atur ikon dan tema warna
        this.modalIcon.className = `mosqera-modal-icon-wrapper ${type}`;
        this.modalIcon.innerHTML = this.getIconSvg(type, confirmText);

        // Buka modal
        this.modalBackdrop.classList.add('is-open');
        this.modalBackdrop.setAttribute('aria-hidden', 'false');
        this.btnConfirm.focus();
      });
    },

    close: function(result) {
      if (!this.modalBackdrop) return;
      this.modalBackdrop.classList.remove('is-open');
      this.modalBackdrop.setAttribute('aria-hidden', 'true');

      if (this.currentResolve) {
        this.currentResolve(result);
        this.currentResolve = null;
      }
    },

    getIconSvg: function(type, actionText) {
      const lower = (actionText || '').toLowerCase();
      if (type === 'danger' || lower.includes('hapus')) {
        return `
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            <line x1="10" y1="11" x2="10" y2="17"></line>
            <line x1="14" y1="11" x2="14" y2="17"></line>
          </svg>
        `;
      } else if (type === 'warning' || lower.includes('logout') || lower.includes('keluar')) {
        return `
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
          </svg>
        `;
      } else if (lower.includes('reset')) {
        return `
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="1 4 1 10 7 10"></polyline>
            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
          </svg>
        `;
      } else if (lower.includes('kirim') || lower.includes('daftar')) {
        return `
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="22" y1="2" x2="11" y2="13"></line>
            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
          </svg>
        `;
      } else if (lower.includes('verifikasi')) {
        return `
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        `;
      } else {
        // Simpan / Lanjutkan / Primary default
        return `
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
            <polyline points="17 21 17 13 7 13 7 21"></polyline>
            <polyline points="7 3 7 8 15 8"></polyline>
          </svg>
        `;
      }
    },

    // Menampilkan Notifikasi Toast Melayang
    toast: function(message, type = 'info', duration = 4200) {
      if (!this.toastContainer) this.createToastDOM();

      const toast = document.createElement('div');
      toast.className = `mosqera-toast ${type}`;

      let iconSvg = '';
      let titleText = 'Pemberitahuan';

      if (type === 'success') {
        titleText = 'Berhasil';
        iconSvg = `
          <svg class="mosqera-toast-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        `;
      } else if (type === 'error') {
        titleText = 'Perhatian';
        iconSvg = `
          <svg class="mosqera-toast-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
        `;
      } else {
        iconSvg = `
          <svg class="mosqera-toast-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
        `;
      }

      toast.innerHTML = `
        ${iconSvg}
        <div class="mosqera-toast-content">
          <div class="mosqera-toast-title">${titleText}</div>
          <div class="mosqera-toast-message">${message}</div>
        </div>
        <button type="button" class="mosqera-toast-close" aria-label="Tutup">&times;</button>
      `;

      // Event listener tombol tutup toast
      const closeBtn = toast.querySelector('.mosqera-toast-close');
      const dismiss = () => {
        toast.classList.add('is-hiding');
        setTimeout(() => {
          if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 260);
      };

      closeBtn.addEventListener('click', dismiss);
      this.toastContainer.appendChild(toast);

      // Auto dismiss setelah durasi tertentu
      setTimeout(dismiss, duration);
    },

    // Memeriksa dan mempercantik notifikasi alert bawaan PHP yang ada di halaman
    checkPageAlerts: function() {
      const successAlert = document.querySelector('.alert-success');
      if (successAlert && successAlert.textContent.trim()) {
        const text = successAlert.textContent.trim();
        this.toast(text, 'success', 5000);
      }

      const errorAlert = document.querySelector('.alert-error, .alert-danger');
      if (errorAlert && errorAlert.textContent.trim()) {
        const text = errorAlert.textContent.trim();
        this.toast(text, 'error', 6000);
      }
    },

    // Menghubungkan Interaksi Pop-up ke Tombol & Formulir
    bindGlobalEvents: function() {
      // 1. Tangani Seluruh Tautan Logout
      document.addEventListener('click', (e) => {
        const logoutLink = e.target.closest('a[href*="logout.php"], .admin-btn-logout');
        if (logoutLink) {
          e.preventDefault();
          this.confirm({
            title: 'Konfirmasi Keluar',
            message: 'Apakah Anda yakin ingin keluar dari Portal Pengurus MOSQERA?',
            confirmText: 'Logout',
            cancelText: 'Batal',
            type: 'warning'
          }).then((confirmed) => {
            if (confirmed) {
              window.location.href = logoutLink.href;
            }
          });
        }
      });

      // 2. Tangani Tombol Reset Formulir
      document.addEventListener('click', (e) => {
        const resetBtn = e.target.closest('button[type="reset"], input[type="reset"]');
        if (resetBtn) {
          e.preventDefault();
          const form = resetBtn.closest('form');
          this.confirm({
            title: 'Konfirmasi Reset',
            message: 'Apakah Anda yakin ingin mengosongkan dan mereset isian formulir ini?',
            confirmText: 'Reset',
            cancelText: 'Batal',
            type: 'warning'
          }).then((confirmed) => {
            if (confirmed && form) {
              form.reset();
              this.toast('Isian formulir telah direset.', 'info');
            }
          });
        }
      });

      // 3. Tangani Formulir dengan atribut data-confirm
      document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!form || !form.hasAttribute('data-confirm')) return;

        // Jika sudah dikonfirmasi secara programatis, biarkan terkirim
        if (form.__mosqeraConfirmed) return;

        // Cek validitas form HTML5 bawaan browser
        if (!form.checkValidity()) {
          form.reportValidity();
          return;
        }

        e.preventDefault();

        const message = form.getAttribute('data-confirm') || 'Simpan data ini?';
        const title = form.getAttribute('data-confirm-title') || 'Konfirmasi Simpan';
        const confirmBtnText = form.getAttribute('data-confirm-btn') || 'Simpan';
        const confirmType = form.getAttribute('data-confirm-type') || 'primary';

        this.confirm({
          title: title,
          message: message,
          confirmText: confirmBtnText,
          cancelText: 'Batal',
          type: confirmType
        }).then((confirmed) => {
          if (confirmed) {
            form.__mosqeraConfirmed = true;

            // Beri animasi loading pada tombol submit form
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
              submitBtn.classList.add('is-submitting');
              submitBtn.disabled = true;
            }

            form.submit();
          }
        });
      });

      // 4. Tangani Tombol Delete/Aksi yang memiliki data-confirm langsung pada button/link
      document.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-confirm], a[data-confirm]');
        if (!btn) return;

        // Jika button berada di dalam form yang sudah memiliki data-confirm, form handler yang akan menangani
        if (btn.type === 'submit' && btn.form && btn.form.hasAttribute('data-confirm')) return;

        e.preventDefault();
        const message = btn.getAttribute('data-confirm') || 'Lanjutkan tindakan ini?';
        const title = btn.getAttribute('data-confirm-title') || 'Konfirmasi Tindakan';
        const confirmBtnText = btn.getAttribute('data-confirm-btn') || 'Lanjutkan';
        const confirmType = btn.getAttribute('data-confirm-type') || 'primary';

        this.confirm({
          title: title,
          message: message,
          confirmText: confirmBtnText,
          cancelText: 'Batal',
          type: confirmType
        }).then((confirmed) => {
          if (confirmed) {
            if (btn.tagName.toLowerCase() === 'a' && btn.href) {
              window.location.href = btn.href;
            } else if (btn.form) {
              btn.form.submit();
            }
          }
        });
      });
    }
  };

  // Ekspos MosqeraUI ke window
  window.MosqeraUI = MosqeraUI;

  // =========================================================================
  // 2. HITUNG MUNDUR JADWAL SHOLAT REAL-TIME
  // =========================================================================
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
    let schedule = (window.JADWAL && Array.isArray(window.JADWAL) && window.JADWAL.length > 0)
      ? window.JADWAL
      : DEFAULT_JADWAL;

    const now = new Date();
    let next = schedule.find(item => getTodayAt(item.waktu) > now);
    let targetDate;

    if (next) {
      targetDate = getTodayAt(next.waktu);
    } else {
      next = schedule[0];
      targetDate = getTodayAt(next.waktu);
      targetDate.setDate(targetDate.getDate() + 1);
    }

    const diffSeconds = Math.max(0, Math.floor((targetDate.getTime() - now.getTime()) / 1000));
    const hours = Math.floor(diffSeconds / 3600);
    const mins = Math.floor((diffSeconds % 3600) / 60);
    const secs = diffSeconds % 60;
    const countStr = `${formatDigit(hours)}:${formatDigit(mins)}:${formatDigit(secs)}`;

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

  // =========================================================================
  // 3. SISTEM SCROLL REVEAL (MODERN, BERTINGKAT/STAGGER, 60FPS)
  // =========================================================================
  const MosqeraScrollReveal = {
    observer: null,

    init: function() {
      // 1. Aktifkan penanda CSS reveal
      document.documentElement.classList.add('js-reveal-ready');

      // 2. Siapkan dan kelompokkan elemen target
      this.prepareElements();

      // 3. Jalankan IntersectionObserver atau fallback
      if ('IntersectionObserver' in window) {
        this.setupObserver();
      } else {
        // Fallback untuk browser lama
        document.querySelectorAll('.reveal').forEach(el => {
          el.classList.add('is-revealed');
        });
      }

      // 4. Pastikan target anchor hash (seperti #form-transaksi) langsung terlihat
      this.revealHashTarget();
      window.addEventListener('hashchange', () => this.revealHashTarget());

      // 5. Pastikan semua elemen langsung terlihat saat mencetak laporan / mading
      window.addEventListener('beforeprint', () => {
        document.querySelectorAll('.reveal').forEach(el => el.classList.add('is-revealed'));
      });
    },

    prepareElements: function() {
      // Kelompok Grid dengan efek animasi bertahap (stagger)
      const staggerGroups = [
        '.cash-summary-grid',
        '.quick-action-grid',
        '.prayer-times-grid',
        '.campaign-grid',
        '.stats-grid'
      ];

      staggerGroups.forEach(selector => {
        document.querySelectorAll(selector).forEach(group => {
          group.classList.add('reveal-stagger');
          const children = Array.from(group.children);
          children.forEach((child, index) => {
            if (!child.classList.contains('reveal')) {
              child.classList.add('reveal');
              // Jeda bertingkat dinamis (0.05s s/d ~0.55s)
              const delay = Math.min(index * 0.08, 0.56);
              child.style.transitionDelay = `${delay.toFixed(2)}s`;
            }
          });
        });
      });

      // Header Halaman & Judul Section (Slide halus dari kiri)
      document.querySelectorAll('.page-header-row, .page-header').forEach(header => {
        if (!header.classList.contains('reveal')) {
          header.classList.add('reveal', 'reveal-left');
        }
      });

      // Kotak Hero / Hitung Mundur Sholat (Scale halus)
      document.querySelectorAll('.prayer-next-card').forEach(heroCard => {
        if (!heroCard.classList.contains('reveal')) {
          heroCard.classList.add('reveal', 'reveal-scale');
        }
      });

      // Kartu, Bagian Utama, Form, Grafik & Tabel Standalone
      const singleTargets = [
        '.card:not(.mosqera-modal-card)',
        '.table-responsive',
        '.auth-card',
        '.filter-card',
        '.print-mading-card',
        '.section-cash-summary',
        'main > section',
        '.admin-main > section'
      ];

      singleTargets.forEach(selector => {
        document.querySelectorAll(selector).forEach(el => {
          // Jangan timpa jika elemen berada di dalam grid stagger yang sudah dianimasikan per item
          if (!el.classList.contains('reveal') && !el.closest('.reveal-stagger')) {
            el.classList.add('reveal');
          }
        });
      });
    },

    setupObserver: function() {
      const options = {
        root: null,
        rootMargin: '0px 0px -30px 0px', // Aktif ketika elemen mulai mendekati viewport bawah
        threshold: 0.05
      };

      this.observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const el = entry.target;
            el.classList.add('is-revealed');
            // Hentikan pengamatan setelah tampil agar sangat hemat memori (60 FPS)
            obs.unobserve(el);
          }
        });
      }, options);

      const items = document.querySelectorAll('.reveal');
      const windowHeight = window.innerHeight || document.documentElement.clientHeight;

      items.forEach(el => {
        const rect = el.getBoundingClientRect();
        // Bila elemen berada di area layar saat awal load (above the fold)
        if (rect.top < windowHeight && rect.bottom > 0) {
          // Segera tampilkan tanpa lag
          requestAnimationFrame(() => {
            el.classList.add('is-revealed');
          });
        } else {
          // Amati saat pengguna scroll
          this.observer.observe(el);
        }
      });
    },

    revealHashTarget: function() {
      if (window.location.hash) {
        try {
          const hashEl = document.querySelector(window.location.hash);
          if (hashEl) {
            const revealTarget = hashEl.closest('.reveal') || hashEl;
            revealTarget.classList.add('is-revealed');
            revealTarget.querySelectorAll('.reveal').forEach(child => child.classList.add('is-revealed'));
          }
        } catch(e) {}
      }
    }
  };

  // =========================================================================
  // 4. BOOTSTRAP PADA DOCUMENT READY
  // =========================================================================
  function boot() {
    MosqeraUI.init();
    MosqeraScrollReveal.init();
    updatePrayerCountdown();
    setInterval(updatePrayerCountdown, 1000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

})();

