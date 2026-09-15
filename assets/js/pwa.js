/**
 * SafeBadger PWA & iOS Home Screen Integration
 */

(function () {
  'use strict';

  // 1. Service Worker Kaydı
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker
        .register('./service-worker.js')
        .then((reg) => {
          console.log('PWA Service Worker aktif:', reg.scope);
        })
        .catch((err) => {
          console.warn('PWA Service Worker hatası:', err);
        });
    });
  }

  // 2. iOS ve Standalone Algılama
  const isIOS = /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());
  const isInStandaloneMode = () =>
    'standalone' in window.navigator && window.navigator.standalone === true ||
    window.matchMedia('(display-mode: standalone)').matches;

  window.addEventListener('DOMContentLoaded', () => {
    const iosBanner = document.getElementById('iosInstallBanner');
    const dismissBtn = document.getElementById('dismissIosBanner');
    const showGuideBtn = document.getElementById('btnShowIosGuide');
    const iosGuideModal = document.getElementById('iosGuideModal');
    const closeGuideModal = document.getElementById('btnCloseIosGuideModal');

    // Eğer iOS'ta ve henüz ana ekrana eklenmemişse banner göster
    const isDismissed = localStorage.getItem('safebadger_ios_banner_dismissed') === '1';

    if (isIOS && !isInStandaloneMode() && !isDismissed && iosBanner) {
      setTimeout(() => {
        iosBanner.classList.add('visible');
      }, 1500);
    }

    if (dismissBtn && iosBanner) {
      dismissBtn.addEventListener('click', () => {
        iosBanner.classList.remove('visible');
        localStorage.setItem('safebadger_ios_banner_dismissed', '1');
      });
    }

    if (showGuideBtn && iosGuideModal) {
      showGuideBtn.addEventListener('click', () => {
        iosGuideModal.classList.add('active');
      });
    }

    if (closeGuideModal && iosGuideModal) {
      closeGuideModal.addEventListener('click', () => {
        iosGuideModal.classList.remove('active');
      });
    }

    // Android/Desktop PWA Install prompt
    let deferredPrompt;
    const pwaInstallBtn = document.getElementById('pwaInstallBtn');

    window.addEventListener('beforeinstallprompt', (e) => {
      e.preventDefault();
      deferredPrompt = e;
      if (pwaInstallBtn) {
        pwaInstallBtn.style.display = 'inline-flex';
      }
    });

    if (pwaInstallBtn) {
      pwaInstallBtn.addEventListener('click', async () => {
        if (deferredPrompt) {
          deferredPrompt.prompt();
          const { outcome } = await deferredPrompt.userChoice;
          if (outcome === 'accepted') {
            pwaInstallBtn.style.display = 'none';
          }
          deferredPrompt = null;
        } else if (isIOS && iosGuideModal) {
          iosGuideModal.classList.add('active');
        }
      });
    }

    // Online / Offline durumu
    window.addEventListener('online', () => {
      if (window.VaultApp && window.VaultApp.showToast) {
        window.VaultApp.showToast('İnternet bağlantısı yeniden sağlandı.', 'success');
      }
    });

    window.addEventListener('offline', () => {
      if (window.VaultApp && window.VaultApp.showToast) {
        window.VaultApp.showToast('Çevrimdışı moddasınız. Veriler cihazınızda güvende.', 'warning');
      }
    });
  });
})();
