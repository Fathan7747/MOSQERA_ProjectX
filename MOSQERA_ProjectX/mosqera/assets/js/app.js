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
  // 4. SISTEM UPLOAD BUKTI TRANSAKSI, PREVIEW & VALIDASI (MosqeraBukti)
  // =========================================================================
  const MosqeraBukti = {
    maxBytes: 5 * 1024 * 1024, // 5 MB
    allowedExts: ['jpg', 'jpeg', 'png', 'pdf'],
    allowedMimes: ['image/jpeg', 'image/png', 'image/pjpeg', 'image/x-png', 'application/pdf'],

    init: function() {
      // 1. Bind setiap container yang ada di halaman
      const containers = document.querySelectorAll('.bukti-upload-container');
      containers.forEach(container => this.bindContainer(container));

      // 2. Pasang Global Event Delegation pada Document
      // Menjamin bahwa tombol Ganti, Hapus, dan File Change selalu berfungsi di form manapun
      this.bindGlobalDelegation();
    },

    bindGlobalDelegation: function() {
      if (window._mosqeraBuktiDelegated) return;
      window._mosqeraBuktiDelegated = true;

      // Event Global saat input file berganti
      document.addEventListener('change', (e) => {
        const input = e.target.closest('.bukti-file-input');
        if (!input) return;
        const container = input.closest('.bukti-upload-container');
        if (!container) return;

        const file = input.files && input.files[0];
        if (file) {
          this.handleFileSelected(file, container);
        } else {
          this.resetUploader(container);
        }
      });

      // Event Global saat tombol Ganti File diklik
      document.addEventListener('click', (e) => {
        const btnGanti = e.target.closest('.btn-bukti-ganti');
        if (btnGanti) {
          e.preventDefault();
          e.stopPropagation();
          const container = btnGanti.closest('.bukti-upload-container');
          if (container) {
            const fileInput = container.querySelector('.bukti-file-input');
            if (fileInput) fileInput.click();
          }
          return;
        }

        // Event Global saat tombol Hapus File diklik
        const btnHapus = e.target.closest('.btn-bukti-hapus');
        if (btnHapus) {
          e.preventDefault();
          e.stopPropagation();
          const container = btnHapus.closest('.bukti-upload-container');
          if (container) {
            this.resetUploader(container);
          }
          return;
        }
      });
    },

    bindContainer: function(container) {
      if (container.dataset.buktiBound) return;
      container.dataset.buktiBound = 'true';

      const fileInput = container.querySelector('.bukti-file-input');
      const dropzone = container.querySelector('.bukti-dropzone');
      if (!fileInput || !dropzone) return;

      // Jika dropzone bukan tag <label>, sediakan fallback click
      if (dropzone.tagName.toLowerCase() !== 'label') {
        dropzone.addEventListener('click', (e) => {
          if (e.target !== fileInput) {
            fileInput.click();
          }
        });
      }

      // Drag and Drop Interaktif
      ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
          e.preventDefault();
          e.stopPropagation();
          dropzone.classList.add('dragover');
        });
      });

      ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
          e.preventDefault();
          e.stopPropagation();
          dropzone.classList.remove('dragover');
        });
      });

      dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt ? dt.files : null;
        if (files && files.length > 0) {
          try {
            fileInput.files = files;
          } catch(err) {
            // Browser tertentu read-only files
          }
          this.handleFileSelected(files[0], container);
        }
      });
    },

    formatBytes: function(bytes) {
      if (bytes === 0) return '0 Byte';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return (bytes / Math.pow(k, i)).toFixed(1) + ' ' + sizes[i];
    },

    showError: function(container, message) {
      const errorBox = container.querySelector('.bukti-error-box');
      if (errorBox) {
        errorBox.textContent = message;
        errorBox.style.display = 'flex';
      } else {
        alert(message);
      }
    },

    hideError: function(container) {
      const errorBox = container.querySelector('.bukti-error-box');
      if (errorBox) {
        errorBox.style.display = 'none';
        errorBox.textContent = '';
      }
    },

    resetUploader: function(container) {
      const fileInput = container.querySelector('.bukti-file-input');
      const dropzone = container.querySelector('.bukti-dropzone');
      const previewCard = container.querySelector('.bukti-preview-card');
      const previewMedia = container.querySelector('.bukti-preview-media');

      if (fileInput) fileInput.value = '';
      if (dropzone) dropzone.style.display = 'flex';
      if (previewCard) previewCard.style.display = 'none';
      if (previewMedia) previewMedia.innerHTML = '';

      this.hideError(container);
    },

    handleFileSelected: function(file, container) {
      this.hideError(container);

      // 1. Validasi Ukuran File (Maksimal 5 MB)
      if (file.size > this.maxBytes) {
        this.resetUploader(container);
        this.showError(container, 'Ukuran file ' + this.formatBytes(file.size) + ' terlalu besar. Batas maksimal adalah 5 MB.');
        return;
      }

      // 2. Validasi Ekstensi
      const ext = (file.name.split('.').pop() || '').toLowerCase();
      if (!this.allowedExts.includes(ext)) {
        this.resetUploader(container);
        this.showError(container, 'Format file .' + ext + ' tidak didukung. Harap pilih file foto (JPG, PNG) atau dokumen PDF.');
        return;
      }

      // 3. Validasi Tipe Konten MIME jika tersedia
      if (file.type && !this.allowedMimes.includes(file.type.toLowerCase())) {
        this.resetUploader(container);
        this.showError(container, 'Tipe konten file tidak diizinkan. Harap gunakan foto JPG/PNG atau file PDF.');
        return;
      }

      // 4. Render Preview
      const dropzone = container.querySelector('.bukti-dropzone');
      const previewCard = container.querySelector('.bukti-preview-card');
      const previewMedia = container.querySelector('.bukti-preview-media');
      const filenameEl = container.querySelector('.bukti-preview-filename');
      const filesizeEl = container.querySelector('.bukti-preview-size');
      const badgeEl = container.querySelector('.bukti-badge-tag');

      if (filenameEl) filenameEl.textContent = file.name;
      if (filesizeEl) filesizeEl.textContent = this.formatBytes(file.size);

      if (previewMedia) {
        previewMedia.innerHTML = '';
        if (file.type.startsWith('image/')) {
          const img = document.createElement('img');
          img.className = 'bukti-preview-img';
          img.alt = 'Pratinjau Bukti';
          try {
            img.src = URL.createObjectURL(file);
          } catch(e) {
            const reader = new FileReader();
            reader.onload = (re) => { img.src = re.target.result; };
            reader.readAsDataURL(file);
          }
          previewMedia.appendChild(img);

          if (badgeEl) {
            badgeEl.className = 'bukti-badge-tag bukti-badge-img';
            badgeEl.textContent = 'Gambar (' + ext.toUpperCase() + ')';
          }
        } else if (file.type === 'application/pdf' || ext === 'pdf') {
          previewMedia.innerHTML = `
            <svg class="bukti-preview-pdf-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
              <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
          `;

          if (badgeEl) {
            badgeEl.className = 'bukti-badge-tag bukti-badge-pdf';
            badgeEl.textContent = 'Dokumen PDF';
          }
        }
      }

      if (dropzone) dropzone.style.display = 'none';
      if (previewCard) previewCard.style.display = 'flex';
    }
  };

  // =========================================================================
  // 5. SISTEM MODAL DETAIL TRANSAKSI & LIGHTBOX (MosqeraDetailModal)
  // =========================================================================
  const MosqeraDetailModal = {
    backdrop: null,
    dialog: null,

    init: function() {
      this.createDOM();
      this.createLightboxDOM();
      this.bindTriggers();
    },

    createDOM: function() {
      if (document.getElementById('mosqeraDetailModalBackdrop')) return;

      const backdrop = document.createElement('div');
      backdrop.id = 'mosqeraDetailModalBackdrop';
      backdrop.className = 'mosqera-modal-backdrop';
      backdrop.setAttribute('aria-hidden', 'true');

      backdrop.innerHTML = `
        <div class="modal-transaksi-dialog" role="dialog" aria-modal="true">
          <div class="modal-transaksi-header">
            <h3 class="modal-transaksi-title">Detail Transaksi Kas</h3>
            <button type="button" class="modal-transaksi-close" id="mosqeraDetailCloseBtn" aria-label="Tutup Detail">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </div>
          <div class="modal-transaksi-body" id="mosqeraDetailBody">
            <!-- Konten dinamis diisi via JS -->
          </div>
          <div class="modal-transaksi-footer">
            <a href="#" id="mosqeraDetailHalamanLink" class="btn btn-secondary" style="padding:8px 16px; font-size:0.88rem;">Buka Halaman Lengkap</a>
            <button type="button" class="btn btn-primary" id="mosqeraDetailOkBtn" style="padding:8px 20px; font-size:0.88rem;">Tutup</button>
          </div>
        </div>
      `;

      document.body.appendChild(backdrop);
      this.backdrop = backdrop;

      const closeBtn = document.getElementById('mosqeraDetailCloseBtn');
      const okBtn = document.getElementById('mosqeraDetailOkBtn');

      if (closeBtn) closeBtn.addEventListener('click', () => this.close());
      if (okBtn) okBtn.addEventListener('click', () => this.close());

      backdrop.addEventListener('click', (e) => {
        if (e.target === backdrop) this.close();
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && backdrop.classList.contains('is-open')) {
          this.close();
        }
      });
    },

    createLightboxDOM: function() {
      if (document.getElementById('mosqeraLightboxBackdrop')) return;

      const lb = document.createElement('div');
      lb.id = 'mosqeraLightboxBackdrop';
      lb.className = 'mosqera-lightbox-backdrop';
      lb.innerHTML = '<img id="mosqeraLightboxImg" class="mosqera-lightbox-img" src="" alt="Bukti Transaksi">';
      document.body.appendChild(lb);

      lb.addEventListener('click', () => {
        lb.classList.remove('is-open');
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && lb.classList.contains('is-open')) {
          lb.classList.remove('is-open');
        }
      });
    },

    openLightbox: function(src, alt) {
      const lb = document.getElementById('mosqeraLightboxBackdrop');
      const img = document.getElementById('mosqeraLightboxImg');
      if (lb && img) {
        img.src = src;
        img.alt = alt || 'Bukti Transaksi';
        lb.classList.add('is-open');
      }
    },

    bindTriggers: function() {
      document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.js-btn-detail-transaksi');
        if (trigger) {
          e.preventDefault();
          try {
            const raw = trigger.getAttribute('data-transaksi');
            if (raw) {
              const data = JSON.parse(raw);
              this.show(data);
            }
          } catch(err) {
            console.error('Error parsing transaksi data:', err);
          }
        }

        const buktiTrigger = e.target.closest('.js-btn-zoom-bukti');
        if (buktiTrigger) {
          e.preventDefault();
          const src = buktiTrigger.getAttribute('data-src');
          const title = buktiTrigger.getAttribute('data-title');
          if (src) this.openLightbox(src, title);
        }
      });
    },

    formatRupiah: function(num) {
      return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
    },

    show: function(data) {
      this.createDOM();
      const body = document.getElementById('mosqeraDetailBody');
      const halamanLink = document.getElementById('mosqeraDetailHalamanLink');
      if (!body) return;

      const isMasuk = String(data.jenis).toLowerCase() === 'masuk';
      const jenisColor = isMasuk ? 'var(--success)' : 'var(--danger)';
      const jenisBadgeClass = isMasuk ? 'badge-masuk' : 'badge-keluar';
      const jenisText = isMasuk ? 'Uang Masuk (+)' : 'Uang Keluar (-)';
      const nominalFmt = this.formatRupiah(data.nominal);

      let buktiHTML = '';
      if (data.bukti_file && data.bukti_url) {
        const ext = (data.bukti_file.split('.').pop() || '').toLowerCase();
        const isPdf = ext === 'pdf';

        if (isPdf) {
          buktiHTML = `
            <div class="transaksi-bukti-section">
              <div class="transaksi-bukti-title">
                <span>Dokumen Bukti Transaksi</span>
                <span class="bukti-badge-tag bukti-badge-pdf">PDF</span>
              </div>
              <div class="transaksi-bukti-preview-box" style="padding:20px; display:flex; align-items:center; justify-content:center; gap:16px;">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--danger); flex-shrink:0;">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                  <polyline points="14 2 14 8 20 8"></polyline>
                  <line x1="16" y1="13" x2="8" y2="13"></line>
                  <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                <div style="text-align:left;">
                  <strong style="display:block; font-size:0.92rem; color:var(--text-main); word-break:break-all;">${this.escapeHTML(data.bukti_file)}</strong>
                  <span style="font-size:0.8rem; color:var(--text-muted);">Dokumen bukti pembayaran / nota resmi</span>
                </div>
              </div>
              <div class="transaksi-bukti-actions">
                <a href="${data.bukti_url}" target="_blank" class="btn btn-secondary" style="padding:6px 14px; font-size:0.84rem;">
                  Buka Dokumen PDF &rarr;
                </a>
                <a href="${data.bukti_url}" download class="btn btn-primary" style="padding:6px 14px; font-size:0.84rem;">
                  Unduh File
                </a>
              </div>
            </div>
          `;
        } else {
          buktiHTML = `
            <div class="transaksi-bukti-section">
              <div class="transaksi-bukti-title">
                <span>Bukti Fisik Transaksi / Nota</span>
                <span class="bukti-badge-tag bukti-badge-img">FOTO / GAMBAR</span>
              </div>
              <div class="transaksi-bukti-preview-box">
                <img src="${data.bukti_url}" alt="Bukti Transaksi" class="transaksi-bukti-image js-btn-zoom-bukti" data-src="${data.bukti_url}" data-title="${this.escapeHTML(data.judul)}" title="Klik untuk memperbesar gambar">
                <div style="font-size:0.78rem; color:var(--text-muted); margin-top:8px;">
                  Klik gambar untuk memperbesar resolusi penuh (Zoom)
                </div>
              </div>
              <div class="transaksi-bukti-actions">
                <button type="button" class="btn-bukti-action js-btn-zoom-bukti" data-src="${data.bukti_url}" data-title="${this.escapeHTML(data.judul)}">
                  Perbesar Gambar
                </button>
                <a href="${data.bukti_url}" target="_blank" class="btn-bukti-action">
                  Buka di Tab Baru
                </a>
                <a href="${data.bukti_url}" download class="btn-bukti-action" style="background:var(--primary-subtle); color:var(--primary); border-color:#cce5e0;">
                  Unduh Bukti
                </a>
              </div>
            </div>
          `;
        }
      } else {
        buktiHTML = `
          <div class="transaksi-bukti-section" style="text-align:center; padding:24px;">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--text-muted); margin-bottom:8px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="9" x2="15" y2="15"></line><line x1="15" y1="9" x2="9" y2="15"></line></svg>
            <div style="font-weight:600; color:var(--text-muted); font-size:0.92rem;">Tidak Ada Bukti Digital yang Diunggah</div>
            <div style="font-size:0.82rem; color:var(--text-light); margin-top:4px;">Transaksi ini dicatat tanpa lampiran file atau bukti nota fisik tersimpan di map bendahara.</div>
          </div>
        `;
      }

      body.innerHTML = `
        <div class="transaksi-detail-grid">
          <div class="transaksi-detail-item">
            <span class="transaksi-detail-label">Nomor Transaksi</span>
            <span class="transaksi-detail-value">#TRX-${String(data.id || 0).padStart(4, '0')}</span>
          </div>

          <div class="transaksi-detail-item">
            <span class="transaksi-detail-label">Tanggal Transaksi</span>
            <span class="transaksi-detail-value">${this.escapeHTML(data.tanggal_format || data.tanggal || '-')}</span>
          </div>

          <div class="transaksi-detail-item">
            <span class="transaksi-detail-label">Jenis Kas</span>
            <div><span class="badge-tag ${jenisBadgeClass}">${jenisText}</span></div>
          </div>

          <div class="transaksi-detail-item">
            <span class="transaksi-detail-label">Kategori</span>
            <span class="transaksi-detail-value" style="color:var(--primary);">${this.escapeHTML(data.kategori || '-')}</span>
          </div>

          <div class="transaksi-detail-item full">
            <span class="transaksi-detail-label">Uraian / Judul Transaksi</span>
            <span class="transaksi-detail-value" style="font-size:1.1rem; color:var(--text-main);">${this.escapeHTML(data.judul || '-')}</span>
          </div>

          <div class="transaksi-detail-item full">
            <span class="transaksi-detail-label">Nominal Transaksi</span>
            <span class="transaksi-nominal-highlight" style="color:${jenisColor};">
              ${isMasuk ? '+' : '-'} ${nominalFmt}
            </span>
          </div>

          <div class="transaksi-detail-item full">
            <span class="transaksi-detail-label">Keterangan / Catatan Tambahan</span>
            <span class="transaksi-detail-value" style="font-weight:400; color:var(--text-muted); background:var(--bg); padding:10px 14px; border-radius:var(--radius-sm); border:1px solid var(--border-light); line-height:1.5;">
              ${this.escapeHTML(data.keterangan || 'Tidak ada keterangan tambahan.')}
            </span>
          </div>

          <div class="transaksi-detail-item">
            <span class="transaksi-detail-label">Dicatat Oleh</span>
            <span class="transaksi-detail-value" style="font-size:0.88rem;">${this.escapeHTML(data.dibuat_oleh || 'Bendahara')}</span>
          </div>

          <div class="transaksi-detail-item">
            <span class="transaksi-detail-label">Waktu Sistem</span>
            <span class="transaksi-detail-value" style="font-size:0.88rem; font-weight:400; color:var(--text-muted);">${this.escapeHTML(data.created_at || '-')}</span>
          </div>
        </div>

        ${buktiHTML}
      `;

      if (halamanLink) {
        halamanLink.href = data.detail_url || ('detail_transaksi.php?id=' + encodeURIComponent(data.id));
      }

      this.backdrop.classList.add('is-open');
    },

    escapeHTML: function(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    },

    close: function() {
      if (this.backdrop) {
        this.backdrop.classList.remove('is-open');
      }
    }
  };

  // =========================================================================
  // 6. SISTEM TOGGLE LIHAT KATA SANDI / SHOW-HIDE PASSWORD (MosqeraPasswordToggle)
  // =========================================================================
  const MosqeraPasswordToggle = {
    init: function() {
      this.enhanceInputs();
      this.bindEvents();
    },

    enhanceInputs: function() {
      const pwInputs = document.querySelectorAll('input[type="password"]');
      pwInputs.forEach(input => {
        let wrapper = input.closest('.password-wrapper');
        if (!wrapper) {
          wrapper = document.createElement('div');
          wrapper.className = 'password-wrapper';
          input.parentNode.insertBefore(wrapper, input);
          wrapper.appendChild(input);
        }

        if (!wrapper.querySelector('.btn-toggle-password')) {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'btn-toggle-password';
          btn.setAttribute('aria-label', 'Tampilkan kata sandi');
          btn.setAttribute('title', 'Tampilkan / Sembunyikan Kata Sandi');
          btn.innerHTML = `
            <svg class="eye-icon eye-show" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <svg class="eye-icon eye-hide" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
              <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>
          `;
          wrapper.appendChild(btn);
        }
      });
    },

    bindEvents: function() {
      if (window._mosqeraPwToggleBound) return;
      window._mosqeraPwToggleBound = true;

      document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-toggle-password');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        const wrapper = btn.closest('.password-wrapper');
        if (!wrapper) return;

        const input = wrapper.querySelector('input');
        if (!input) return;

        const eyeShow = btn.querySelector('.eye-show');
        const eyeHide = btn.querySelector('.eye-hide');

        if (input.type === 'password') {
          input.type = 'text';
          btn.setAttribute('aria-label', 'Sembunyikan kata sandi');
          if (eyeShow) eyeShow.style.display = 'none';
          if (eyeHide) eyeHide.style.display = 'block';
        } else {
          input.type = 'password';
          btn.setAttribute('aria-label', 'Tampilkan kata sandi');
          if (eyeShow) eyeShow.style.display = 'block';
          if (eyeHide) eyeHide.style.display = 'none';
        }

        input.focus();
      });
    }
  };

  // Expose ke global window
  window.MosqeraBukti = MosqeraBukti;
  window.MosqeraDetailModal = MosqeraDetailModal;
  window.MosqeraPasswordToggle = MosqeraPasswordToggle;
  window.bukaDetailTransaksi = function(data) { MosqeraDetailModal.show(data); };
  window.bukaLightboxBukti = function(src, alt) { MosqeraDetailModal.openLightbox(src, alt); };

  // =========================================================================
  // 7. BOOTSTRAP PADA DOCUMENT READY
  // =========================================================================
  function boot() {
    MosqeraUI.init();
    MosqeraBukti.init();
    MosqeraDetailModal.init();
    MosqeraPasswordToggle.init();
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

