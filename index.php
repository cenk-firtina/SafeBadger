<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>SafeBadger — Kişisel Şifre Saklama</title>
  <meta name="description" content="cPanel ve iOS uyumlu, uçtan uca AES-256 şifreli kişisel şifre saklama yöneticisi.">

  <!-- PWA & iOS Safari Meta Etiketleri -->
  <link rel="manifest" href="./manifest.webmanifest">
  <meta name="theme-color" content="#090d16">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="SafeBadger">

  <!-- iOS İkonları -->
  <link rel="apple-touch-icon" href="./assets/icons/apple-touch-icon.png">
  <link rel="apple-touch-icon" sizes="180x180" href="./assets/icons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="192x192" href="./assets/icons/icon-192.png">
  <link rel="icon" type="image/svg+xml" href="./assets/icons/icon.svg">

  <!-- Modern Tipografi (Google Fonts) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Özel Lüks Apple Glassmorphism CSS -->
  <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body>

  <!-- Toast Bildirim Alanı -->
  <div id="toastContainer" class="toast-container"></div>

  <!-- 1. GİRİŞ / KİLİT / İLK KURULUM EKRANI (Auth View) -->
  <div id="authView" class="lock-screen-wrapper">
    <div class="lock-card">
      <div class="lock-shield-anim">
        <img src="./assets/icons/icon.svg" alt="SafeBadger" style="width: 52px; height: 52px; border-radius: 12px;">
      </div>
      <h2 id="authCardTitle" class="lock-title">SafeBadger</h2>
      <p id="authCardDesc" class="lock-desc">Kayıtlı şifrelerinize erişmek ve verileri çözmek için Ana Parolanızı girin.</p>

      <div id="authError" style="color: var(--accent-rose); font-size: 0.85rem; margin-bottom: 14px; min-height: 20px; font-weight: 600;"></div>

      <!-- Parola Giriş Adımı -->
      <form id="authForm" autocomplete="off">
        <div class="lock-input-group">
          <label class="form-label" for="masterPasswordInput">Parola</label>
          <input type="password" id="masterPasswordInput" class="form-input font-mono" placeholder="••••••••••••" required autofocus>
          <button type="button" id="btnToggleAuthPass" class="lock-toggle-pass-btn" title="Şifreyi Göster/Gizle">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>

        <div id="confirmPasswordGroup" class="lock-input-group" style="display: none;">
          <label class="form-label" for="confirmPasswordInput">Parolayı Onaylayın</label>
          <input type="password" id="confirmPasswordInput" class="form-input font-mono" placeholder="••••••••••••">
        </div>

        <button type="submit" id="authSubmitBtn" class="btn-primary" style="width: 100%; padding: 14px 20px; font-size: 1rem; border-radius: var(--radius-sm); margin-top: 6px;">
          <span>Giriş Yap</span>
        </button>

        <div id="forgotPassWrapper" style="margin-top: 14px; text-align: center;">
          <button type="button" id="btnRequestResetLink" class="input-btn" style="color: var(--accent-primary); font-size: 0.84rem; font-weight: 600; padding: 4px 8px; margin: 0 auto;">
            Şifremi Unuttum / Mail ile Değiştir
          </button>
        </div>
      </form>

      <!-- 2FA E-posta Doğrulama Kodu Adımı -->
      <form id="otpForm" autocomplete="off" style="display: none;">
        <div style="background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: var(--radius-md); padding: 14px; margin-bottom: 18px; text-align: left;">
          <div style="display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 0.9rem; color: var(--accent-cyan); margin-bottom: 4px;">
            <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <span>E-Posta Doğrulaması</span>
          </div>
          <div style="font-size: 0.8rem; color: var(--text-secondary); line-height: 1.4;">
            <strong id="otpEmailTarget" style="color: var(--text-primary);">cenkfirtna@gmail.com</strong> adresine 6 haneli güvenlik kodu gönderildi.
          </div>
        </div>

        <div class="lock-input-group">
          <label class="form-label" for="otpCodeInput" style="text-align: center;">6 Haneli Güvenlik Kodu</label>
          <input type="text" id="otpCodeInput" class="form-input font-mono" maxlength="6" pattern="[0-9]*" inputmode="numeric" autocomplete="one-time-code" placeholder="••••••" style="text-align: center; font-size: 1.6rem; letter-spacing: 10px; font-weight: 800; padding: 14px;" required>
        </div>

        <button type="submit" id="otpSubmitBtn" class="btn-primary" style="width: 100%; padding: 14px 20px; font-size: 1rem; border-radius: var(--radius-sm); margin-top: 6px;">
          <span>Doğrula ve Giriş Yap</span>
        </button>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; font-size: 0.82rem;">
          <button type="button" id="btnBackToPassword" class="input-btn" style="color: var(--text-muted); font-size: 0.82rem; padding: 4px;">
            <span>← Geri Dön</span>
          </button>
          <button type="button" id="btnResendOtp" class="input-btn" style="color: var(--accent-cyan); font-weight: 600; font-size: 0.82rem; padding: 4px;">
            <span>Kodu Tekrar Gönder</span>
          </button>
        </div>
      </form>

      <div style="margin-top: 28px; padding-top: 16px; border-top: 1px solid var(--border-subtle); display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 0.78rem; color: var(--text-muted);">
        <svg style="width: 14px; height: 14px; color: var(--accent-emerald);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        <span>Sıfır-Bilgi AES-256 İstemci Şifrelemesi</span>
      </div>
    </div>
  </div>

  <!-- 2. ANA KASA EKRANI (Main View) -->
  <div id="mainView" class="app-container" style="display: none;">
    <!-- Üst Başlık -->
    <header class="app-header">
      <div class="brand">
        <div class="brand-icon">
          <img src="./assets/icons/icon.svg" alt="SafeBadger">
        </div>
        <div class="brand-info">
          <h1>SafeBadger</h1>
          <div class="brand-badge">
            <span class="badge-dot"></span>
            <span>Uçtan Uca Şifreli</span>
          </div>
        </div>
      </div>

      <div class="header-actions">
        <!-- Profil Avatar Butonu -->
        <div id="btnHeaderProfile" class="user-profile-btn" title="Profil ve Ayarlar">
          <div id="headerAvatarThumb" class="profile-avatar-thumb">
            <span id="headerAvatarInitial">C</span>
          </div>
        </div>
      </div>
    </header>

    <!-- Arama ve Filtreleme -->
    <section class="filter-bar">
      <div class="search-box">
        <span class="search-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </span>
        <input type="text" id="searchBar" class="search-input" placeholder="Hesap, e-posta veya bağlantı ara..." autocomplete="off">
      </div>

      <div class="categories-scroll">
        <button class="category-chip active" data-category="all">
          <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          <span>Tümü</span>
        </button>
        <button class="category-chip" data-category="favorite">
          <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <span>Favoriler</span>
        </button>
        <button class="category-chip" data-category="social">
          <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
          <span>Sosyal</span>
        </button>
        <button class="category-chip" data-category="finance">
          <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          <span>Finans</span>
        </button>
        <button class="category-chip" data-category="email">
          <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          <span>E-Posta</span>
        </button>
        <button class="category-chip" data-category="work">
          <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
          <span>İş</span>
        </button>
        <button class="category-chip" data-category="personal">
          <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2l-2 2m-1.5 1.5L10 13l-4 1 1-4 7.5-7.5m1.5-1.5L19 3m-4 11l4 4m-2-6l4 4"/></svg>
          <span>Kişisel</span>
        </button>
      </div>
    </section>

    <!-- İstatistik ve Sayaç Çubuğu -->
    <div class="vault-metrics">
      <div class="metrics-count">
        <span id="itemCountBadge">0 Kayıt</span>
      </div>
    </div>

    <!-- Şifre Kartları Listesi -->
    <main id="vaultList" class="vault-list"></main>

    <!-- Boş Durum (Empty State) -->
    <div id="emptyState" class="empty-state" style="display: none;">
      <div class="empty-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
          <polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
        </svg>
      </div>
      <h3 class="empty-title">Henüz Kayıt Yok</h3>
      <p class="empty-desc">Bu kategoride veya aramada kayıt bulunamadı. İlk hesabınızı ekleyerek şifrelerinizi saklamaya başlayın.</p>
      <button class="btn-primary" onclick="window.VaultApp.openCreateModal()">
        <span>+ Yeni Hesap Ekle</span>
      </button>
    </div>

    <!-- Alt iOS / Mobil Navigasyon Barı (3'lü Simetrik Düzen) -->
    <nav class="bottom-nav">
      <div class="bottom-nav-inner">
        <!-- Sol: Şifrelerim -->
        <button id="navVaultBtn" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2l-2 2m-1.5 1.5L10 13l-4 1 1-4 7.5-7.5m1.5-1.5L19 3m-4 11l4 4m-2-6l4 4"/></svg>
          <span>Şifrelerim</span>
        </button>

        <!-- Orta: Yeni Ekle Butonu -->
        <button id="navAddBtn" class="nav-fab" title="Yeni Kayıt Ekle">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </button>

        <!-- Sağ: Ayarlar -->
        <button id="navSettingsBtn" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          <span>Ayarlar</span>
        </button>
      </div>
    </nav>
  </div>

  <!-- 3. YENİ KAYIT / DÜZENLEME MODALI (Item Modal) -->
  <div id="itemModal" class="modal-overlay">
    <div class="modal-sheet">
      <div class="sheet-handle"></div>
      <div class="modal-header">
        <h3 id="itemModalTitle" class="modal-title">Yeni Hesap Ekle</h3>
        <button class="btn-icon" data-close-modal title="Kapat">✕</button>
      </div>

      <form id="itemForm" autocomplete="off">
        <input type="hidden" id="itemIdInput">

        <!-- Hızlı Servis Seçimi (Quick Preset Chips) -->
        <div style="margin-bottom: 16px;">
          <label class="form-label" style="font-size: 0.78rem; margin-bottom: 6px;">Hızlı Şablonlar</label>
          <div class="categories-scroll" style="gap: 6px;">
            <button type="button" class="quick-preset-chip" data-title="Google" data-cat="personal" data-url="https://accounts.google.com">Google</button>
            <button type="button" class="quick-preset-chip" data-title="Gmail" data-cat="email" data-url="https://mail.google.com">Gmail</button>
            <button type="button" class="quick-preset-chip" data-title="Outlook" data-cat="email" data-url="https://outlook.live.com">Outlook</button>
            <button type="button" class="quick-preset-chip" data-title="Instagram" data-cat="social" data-url="https://instagram.com">Instagram</button>
            <button type="button" class="quick-preset-chip" data-title="Apple" data-cat="personal" data-url="https://appleid.apple.com">Apple</button>
            <button type="button" class="quick-preset-chip" data-title="X / Twitter" data-cat="social" data-url="https://x.com">X</button>
            <button type="button" class="quick-preset-chip" data-title="GitHub" data-cat="work" data-url="https://github.com">GitHub</button>
            <button type="button" class="quick-preset-chip" data-title="Netflix" data-cat="personal" data-url="https://netflix.com">Netflix</button>
            <button type="button" class="quick-preset-chip" data-title="Spotify" data-cat="personal" data-url="https://spotify.com">Spotify</button>
            <button type="button" class="quick-preset-chip" data-title="Banka" data-cat="finance" data-url="">Banka</button>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="itemTitleInput">Hesap / Servis Adı *</label>
          <input type="text" id="itemTitleInput" class="form-input" placeholder="ör. Google, Instagram, Garanti Bankası" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="itemCategorySelect">Kategori</label>
          <select id="itemCategorySelect" class="form-select">
            <option value="social">Sosyal Medya</option>
            <option value="finance">Finans & Banka</option>
            <option value="email">E-Posta</option>
            <option value="work">İş & Ofis</option>
            <option value="personal" selected>Kişisel</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="itemUsernameInput">Kullanıcı Adı / E-Posta</label>
          <input type="text" id="itemUsernameInput" class="form-input" placeholder="ör. cenk@gmail.com veya kullanici_adi">
        </div>

        <div class="form-group">
          <label class="form-label" for="itemPasswordInput">Şifre</label>
          <div class="input-with-action">
            <input type="password" id="itemPasswordInput" class="form-input font-mono" placeholder="••••••••••••">
            <div class="input-actions-inner">
              <button type="button" id="btnGenerateInForm" class="input-btn" title="Rastgele Güçlü Şifre Üret">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
              </button>
              <button type="button" id="btnTogglePasswordVisibility" class="input-btn" title="Şifreyi Göster/Gizle">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
          </div>
          <div class="strength-container">
            <div class="strength-bar-bg">
              <div id="itemStrengthFill" class="strength-bar-fill"></div>
            </div>
            <div class="strength-text">
              <span id="itemStrengthText">Güç: Bekleniyor</span>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="itemUrlInput">Web Sitesi Adresi (URL)</label>
          <input type="url" id="itemUrlInput" class="form-input" placeholder="https://example.com">
        </div>

        <div class="form-group">
          <label class="form-label" for="itemNotesInput">Güvenli Notlar (PIN, kurtarma kodları vb.)</label>
          <textarea id="itemNotesInput" class="form-textarea" rows="3" placeholder="Yalnızca size özel şifrelenmiş notlar..."></textarea>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 24px;">
          <button type="button" id="btnDeleteItem" class="btn-danger" style="display: none;">
            <span>Sil</span>
          </button>
          <button type="submit" class="btn-primary" style="flex: 1;">
            <span>Kaydet & Şifrele</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- 4. GÜÇLÜ ŞİFRE ÜRETİCİ MODALI (Generator Modal) -->
  <div id="generatorModal" class="modal-overlay">
    <div class="modal-sheet">
      <div class="sheet-handle"></div>
      <div class="modal-header">
        <h3 class="modal-title">Güçlü Şifre Üretici</h3>
        <button class="btn-icon" data-close-modal title="Kapat">✕</button>
      </div>

      <div class="generator-box">
        <div class="input-with-action">
          <input type="text" id="genResultInput" class="form-input font-mono" readonly style="font-size: 1.05rem; font-weight: 700; color: var(--accent-cyan);">
          <div class="input-actions-inner">
            <button type="button" id="btnRefreshGen" class="input-btn" title="Yeniden Üret">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
            </button>
            <button type="button" id="btnCopyGen" class="input-btn" title="Kopyala">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            </button>
          </div>
        </div>

        <div class="gen-slider-group">
          <span style="font-size: 0.85rem; color: var(--text-secondary);">Uzunluk</span>
          <input type="range" id="genLengthSlider" class="gen-slider" min="8" max="36" value="18">
          <span id="genLengthVal" class="gen-length-badge">18</span>
        </div>

        <div class="gen-options-grid">
          <label class="gen-toggle">
            <input type="checkbox" id="genUpper" checked>
            <span>Büyük Harf (A-Z)</span>
          </label>
          <label class="gen-toggle">
            <input type="checkbox" id="genLower" checked>
            <span>Küçük Harf (a-z)</span>
          </label>
          <label class="gen-toggle">
            <input type="checkbox" id="genDigits" checked>
            <span>Rakamlar (0-9)</span>
          </label>
          <label class="gen-toggle">
            <input type="checkbox" id="genSymbols" checked>
            <span>Semboller (!@#$)</span>
          </label>
        </div>
      </div>

      <button type="button" id="btnUseGenPassword" class="btn-primary" style="width: 100%;">
        <span>Bu Şifreyi Yeni Hesaba Aktar</span>
      </button>
    </div>
  </div>

  <!-- 5. AYARLAR & YEDEKLEME MODALI (Settings Modal) -->
  <div id="settingsModal" class="modal-overlay">
    <div class="modal-sheet">
      <div class="sheet-handle"></div>
      <div class="modal-header">
        <h3 class="modal-title">Güvenlik & Ayarlar</h3>
        <button class="btn-icon" data-close-modal title="Kapat">✕</button>
      </div>

      <div style="display: flex; flex-direction: column; gap: 16px;">
        <!-- Profil Fotoğrafı ve Kullanıcı Bilgisi -->
        <div class="profile-upload-card settings-card">
          <div id="settingsAvatarPreview" class="profile-upload-preview">
            <span id="settingsAvatarInitial">C</span>
          </div>
          <div style="flex: 1;">
            <div style="font-weight: 700; font-size: 1rem; color: var(--text-primary); margin-bottom: 2px;" id="settingsUsernameLabel">Cenk</div>
            <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 8px;">Kişisel profil fotoğrafınızı belirleyin</div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
              <label class="btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; cursor: pointer;">
                <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                <span>Resim Yükle</span>
                <input type="file" id="userPhotoInput" accept="image/*" style="display: none;">
              </label>
              <button type="button" id="btnRemovePhoto" class="btn-secondary" style="padding: 6px 10px; font-size: 0.8rem; display: none;">
                <span>Kaldır</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Görünüm / Tema Seçimi (Dark / Light) -->
        <div class="theme-toggle-wrapper settings-card">
          <div>
            <div style="font-weight: 600; font-size: 0.92rem; color: var(--text-primary); margin-bottom: 2px;">Görünüm Teması</div>
            <div style="font-size: 0.78rem; color: var(--text-muted);">Karanlık (Dark) veya Aydınlık (Light) mod</div>
          </div>
          <button type="button" id="btnSettingsThemeToggle" class="theme-switch-btn">
            <span id="settingsThemeText">🌙 Koyu Mod</span>
          </button>
        </div>

        <!-- Ana Parola & Şifre Değiştir -->
        <div class="settings-card">
          <h4 style="font-size: 0.95rem; margin-bottom: 6px; color: var(--text-primary);">Ana Parola & Şifre Değiştir</h4>
          <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 14px;">Şifrenizi güvenle güncellemek için kayıtlı e-posta adresinize (<strong>cenkfirtna@gmail.com</strong>) tek kullanımlık güvenli bağlantı gönderilir.</p>
          <button type="button" id="btnSettingsRequestReset" class="btn-secondary" style="width: 100%;">
            <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <span>Mailime Şifre Değiştirme Linki Gönder</span>
          </button>
        </div>

        <!-- Şifreli Yedekleme -->
        <div class="settings-card">
          <h4 style="font-size: 0.95rem; margin-bottom: 6px; color: var(--text-primary);">Şifreli Yedekleme (Backup)</h4>
          <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 14px;">Tüm kayıtlarınız şifreli (AES-256) JSON dosyası olarak indirilir. Dosyayı başka bir sunucuya veya yeni kuruluma anında aktarabilirsiniz.</p>
          <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" id="btnExportBackup" class="btn-secondary" style="flex: 1;">
              <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              <span>Yedeği İndir</span>
            </button>
            <label class="btn-secondary" style="flex: 1; cursor: pointer;">
              <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              <span>Yedekten Yükle</span>
              <input type="file" id="importFileInput" accept=".json" style="display: none;">
            </label>
          </div>
        </div>

        <!-- Oturum & Güvenlik -->
        <div class="settings-card">
          <h4 style="font-size: 0.95rem; margin-bottom: 6px; color: var(--text-primary);">Oturum & Güvenlik</h4>
          <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 14px;">Şifrelerinizi hemen kilitleyebilir veya oturumu kapatabilirsiniz.</p>
          <div style="display: flex; gap: 10px;">
            <button type="button" id="btnLockNow" class="btn-secondary" style="flex: 1;">
              <span>Şifreleri Kilitle</span>
            </button>
            <button type="button" id="btnLogout" class="btn-danger" style="flex: 1;">
              <span>Oturumu Kapat</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 6. ŞİFRE DEĞİŞTİRME / SIFIRLAMA MODALI (Password Reset Modal) -->
  <div id="passwordResetModal" class="modal-overlay">
    <div class="modal-sheet">
      <div class="sheet-handle"></div>
      <div class="modal-header">
        <h3 class="modal-title">Ana Parolayı Güncelle</h3>
        <button class="btn-icon" data-close-modal title="Kapat">✕</button>
      </div>

      <form id="passwordResetForm" autocomplete="off">
        <input type="hidden" id="resetTokenInput">
        <div style="background: rgba(99, 102, 241, 0.08); border: 1px solid rgba(99, 102, 241, 0.2); border-radius: var(--radius-sm); padding: 12px; margin-bottom: 16px; font-size: 0.82rem; color: var(--text-secondary); line-height: 1.5;">
          🛡️ E-posta doğrulamanız sağlandı. Yeni ana parolanızı aşağıdan belirleyebilirsiniz.
        </div>

        <div id="resetCurrentPassGroup" class="form-group">
          <label class="form-label" for="resetCurrentPasswordInput">Mevcut Ana Parola <span style="font-size: 0.74rem; color: var(--accent-emerald); font-weight: 500;">(Kayıtları Korumak İçin)</span></label>
          <input type="password" id="resetCurrentPasswordInput" class="form-input font-mono" placeholder="Mevcut parolanız">
        </div>

        <div class="form-group">
          <label class="form-label" for="resetNewPasswordInput">Yeni Ana Parola *</label>
          <input type="password" id="resetNewPasswordInput" class="form-input font-mono" placeholder="••••••••••••" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="resetConfirmPasswordInput">Yeni Ana Parola (Tekrar) *</label>
          <input type="password" id="resetConfirmPasswordInput" class="form-input font-mono" placeholder="••••••••••••" required>
        </div>

        <div id="resetForgotCheckboxWrap" style="margin: 14px 0 18px 0; padding: 12px; border-radius: var(--radius-sm); background: rgba(244, 63, 94, 0.06); border: 1px solid rgba(244, 63, 94, 0.18);">
          <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.78rem; color: var(--text-muted); cursor: pointer; line-height: 1.4;">
            <input type="checkbox" id="chkResetForgotOld" style="margin-top: 2px;">
            <span>Eski ana parolamı hiç hatırlamıyorum (Sıfır bilgi koruması gereği eski kayıtlar sıfırlanacak, yeni temiz bir kasa oluşturulacaktır).</span>
          </label>
        </div>

        <div id="resetErrorMsg" style="display: none; color: var(--accent-rose); font-size: 0.85rem; margin-bottom: 14px; font-weight: 600;"></div>

        <button type="submit" id="btnSubmitPasswordReset" class="btn-primary" style="width: 100%; padding: 14px; font-size: 0.95rem;">
          <span>Parolayı Değiştir ve Kasayı Aç</span>
        </button>
      </form>
    </div>
  </div>

  <!-- 7. iOS SAFARI "ANA EKRANA EKLE" REHBER MODALI (iOS Guide Modal) -->
  <div id="iosGuideModal" class="modal-overlay">
    <div class="modal-sheet">
      <div class="sheet-handle"></div>
      <div class="modal-header">
        <h3 class="modal-title">iOS'ta Mobil Uygulama Olarak Kullanın</h3>
        <button id="btnCloseIosGuideModal" class="btn-icon" title="Kapat">✕</button>
      </div>

      <div style="display: flex; flex-direction: column; gap: 16px; font-size: 0.9rem; color: var(--text-secondary);">
        <p>SafeBadger'i tıpkı App Store'dan indirilmiş yerel bir iOS uygulaması gibi tam ekran çalıştırmak için 3 kolay adım:</p>

        <div style="display: flex; align-items: flex-start; gap: 14px; background: rgba(0,0,0,0.3); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
          <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--accent-primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">1</div>
          <div>
            <strong style="color: var(--text-primary); display: block; margin-bottom: 2px;">Safari Paylaş Butonuna Dokunun</strong>
            <span>Safari'nin en alt çubuğunda yer alan <strong>Paylaş (Share)</strong> simgesine dokunun.</span>
          </div>
        </div>

        <div style="display: flex; align-items: flex-start; gap: 14px; background: rgba(0,0,0,0.3); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
          <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--accent-cyan); color: #020617; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">2</div>
          <div>
            <strong style="color: var(--text-primary); display: block; margin-bottom: 2px;">"Ana Ekrana Ekle"yi Seçin</strong>
            <span>Açılan menüyü aşağı kaydırıp <strong>"Ana Ekrana Ekle" (Add to Home Screen)</strong> seçeneğine tıklayın.</span>
          </div>
        </div>

        <div style="display: flex; align-items: flex-start; gap: 14px; background: rgba(0,0,0,0.3); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
          <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--accent-emerald); color: #020617; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">3</div>
          <div>
            <strong style="color: var(--text-primary); display: block; margin-bottom: 2px;">"Ekle"ye Basın</strong>
            <span>Sağ üstteki <strong>Ekle</strong> butonuna basın. SafeBadger artık ana ekranınızda özel ikonlu bir uygulama olarak açılacaktır!</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- iOS Safari Alt Banner (İpucu) -->
  <div id="iosInstallBanner" class="ios-banner">
    <div class="ios-banner-icon">
      <img src="./assets/icons/apple-touch-icon.png" alt="SafeBadger">
    </div>
    <div class="ios-banner-text">
      <div class="ios-banner-title">Ana Ekrana Ekleyin</div>
      <div class="ios-banner-desc">Tam ekran uygulama deneyimi için Paylaş ➔ Ana Ekrana Ekle</div>
    </div>
    <button id="dismissIosBanner" class="btn-icon" style="width: 30px; height: 30px; font-size: 0.8rem;">✕</button>
  </div>

  <!-- JS Kütüphaneleri -->
  <script src="./assets/js/crypto.js?v=4"></script>
  <script src="./assets/js/app.js?v=4"></script>
  <script src="./assets/js/pwa.js?v=4"></script>
</body>
</html>
