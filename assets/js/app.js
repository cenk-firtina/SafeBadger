/**
 * SafeBadger Application Engine
 * Zero-Knowledge Client-Side Secured Password Manager
 */

(function () {
  'use strict';

  // Uygulama Durumu (State)
  const state = {
    isSetup: false,
    isAuthenticated: false,
    username: null,
    salt: null,
    authKeySalt: null,
    masterKey: null, // SADECE RAM'DE TUTULUR, ASLA LOCALSTORAGE'A YAZILMAZ
    vaultItems: [],
    decryptedMap: new Map(), // id -> decrypted payload
    activeCategory: 'all',
    searchQuery: '',
    autoLockMinutes: 15,
    lastActivity: Date.now()
  };

  // DOM Elemanları
  const dom = {
    authView: document.getElementById('authView'),
    mainView: document.getElementById('mainView'),
    authCardTitle: document.getElementById('authCardTitle'),
    authCardDesc: document.getElementById('authCardDesc'),
    authForm: document.getElementById('authForm'),
    masterPasswordInput: document.getElementById('masterPasswordInput'),
    confirmPasswordGroup: document.getElementById('confirmPasswordGroup'),
    confirmPasswordInput: document.getElementById('confirmPasswordInput'),
    authSubmitBtn: document.getElementById('authSubmitBtn'),
    authError: document.getElementById('authError'),

    // 2FA E-posta Doğrulama Elemanları
    otpForm: document.getElementById('otpForm'),
    otpCodeInput: document.getElementById('otpCodeInput'),
    otpSubmitBtn: document.getElementById('otpSubmitBtn'),
    otpEmailTarget: document.getElementById('otpEmailTarget'),
    btnResendOtp: document.getElementById('btnResendOtp'),
    btnBackToPassword: document.getElementById('btnBackToPassword'),

    // Kasa Listesi
    vaultList: document.getElementById('vaultList'),
    emptyState: document.getElementById('emptyState'),
    searchBar: document.getElementById('searchBar'),
    categoryChips: document.querySelectorAll('.category-chip'),
    itemCountBadge: document.getElementById('itemCountBadge'),

    // Modallar
    itemModal: document.getElementById('itemModal'),
    itemModalTitle: document.getElementById('itemModalTitle'),
    itemForm: document.getElementById('itemForm'),
    itemIdInput: document.getElementById('itemIdInput'),
    itemTitleInput: document.getElementById('itemTitleInput'),
    itemCategorySelect: document.getElementById('itemCategorySelect'),
    itemUsernameInput: document.getElementById('itemUsernameInput'),
    itemPasswordInput: document.getElementById('itemPasswordInput'),
    itemUrlInput: document.getElementById('itemUrlInput'),
    itemNotesInput: document.getElementById('itemNotesInput'),
    btnGenerateInForm: document.getElementById('btnGenerateInForm'),
    btnTogglePasswordVisibility: document.getElementById('btnTogglePasswordVisibility'),
    itemStrengthFill: document.getElementById('itemStrengthFill'),
    itemStrengthText: document.getElementById('itemStrengthText'),
    btnDeleteItem: document.getElementById('btnDeleteItem'),

    // Şifre Üretici Modalı
    generatorModal: document.getElementById('generatorModal'),
    genResultInput: document.getElementById('genResultInput'),
    genLengthSlider: document.getElementById('genLengthSlider'),
    genLengthVal: document.getElementById('genLengthVal'),
    genUpper: document.getElementById('genUpper'),
    genLower: document.getElementById('genLower'),
    genDigits: document.getElementById('genDigits'),
    genSymbols: document.getElementById('genSymbols'),
    btnRefreshGen: document.getElementById('btnRefreshGen'),
    btnCopyGen: document.getElementById('btnCopyGen'),
    btnUseGenPassword: document.getElementById('btnUseGenPassword'),

    // Ayarlar & Yedekleme Modalı
    settingsModal: document.getElementById('settingsModal'),
    btnExportBackup: document.getElementById('btnExportBackup'),
    importFileInput: document.getElementById('importFileInput'),
    btnLogout: document.getElementById('btnLogout'),
    btnLockNow: document.getElementById('btnLockNow'),

    // Alt Nav Butonları
    navVaultBtn: document.getElementById('navVaultBtn'),
    navAddBtn: document.getElementById('navAddBtn'),
    navGenBtn: document.getElementById('navGenBtn'),
    navSettingsBtn: document.getElementById('navSettingsBtn'),

    // Toast Container
    toastContainer: document.getElementById('toastContainer'),

    // Tema & Profil
    btnToggleTheme: document.getElementById('btnToggleTheme'),
    themeIconDark: document.getElementById('themeIconDark'),
    themeIconLight: document.getElementById('themeIconLight'),
    btnSettingsThemeToggle: document.getElementById('btnSettingsThemeToggle'),
    settingsThemeText: document.getElementById('settingsThemeText'),
    btnHeaderProfile: document.getElementById('btnHeaderProfile'),
    headerAvatarThumb: document.getElementById('headerAvatarThumb'),
    headerAvatarInitial: document.getElementById('headerAvatarInitial'),
    settingsAvatarPreview: document.getElementById('settingsAvatarPreview'),
    settingsAvatarInitial: document.getElementById('settingsAvatarInitial'),
    settingsUsernameLabel: document.getElementById('settingsUsernameLabel'),
    userPhotoInput: document.getElementById('userPhotoInput'),
    btnRemovePhoto: document.getElementById('btnRemovePhoto'),

    // Şifre Değiştirme / Sıfırlama
    btnRequestResetLink: document.getElementById('btnRequestResetLink'),
    btnSettingsRequestReset: document.getElementById('btnSettingsRequestReset'),
    passwordResetModal: document.getElementById('passwordResetModal'),
    passwordResetForm: document.getElementById('passwordResetForm'),
    resetTokenInput: document.getElementById('resetTokenInput'),
    resetCurrentPasswordInput: document.getElementById('resetCurrentPasswordInput'),
    resetNewPasswordInput: document.getElementById('resetNewPasswordInput'),
    resetConfirmPasswordInput: document.getElementById('resetConfirmPasswordInput'),
    chkResetForgotOld: document.getElementById('chkResetForgotOld'),
    resetCurrentPassGroup: document.getElementById('resetCurrentPassGroup'),
    resetErrorMsg: document.getElementById('resetErrorMsg'),
    btnSubmitPasswordReset: document.getElementById('btnSubmitPasswordReset')
  };

  // 1. Bildirim (Toast) Sistemi
  function showToast(message, type = 'info') {
    if (!dom.toastContainer) return;
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let iconSvg = `<svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`;
    if (type === 'success') {
      iconSvg = `<svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>`;
    } else if (type === 'error') {
      iconSvg = `<svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`;
    } else if (type === 'warning') {
      iconSvg = `<svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`;
    }

    toast.innerHTML = `<span style="display: flex; align-items: center;">${iconSvg}</span><span>${message}</span>`;
    dom.toastContainer.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(-10px)';
      setTimeout(() => toast.remove(), 300);
    }, 3200);
  }

  // 2. Panoya Güvenli Kopyalama
  async function copyToClipboard(text, label = 'Veri') {
    if (!text) return;
    try {
      await navigator.clipboard.writeText(text);
      showToast(`${label} panoya kopyalandı!`, 'success');
      
      // Güvenlik: 45 saniye sonra panoyu temizleme uyarısı/zamanlayıcısı
      setTimeout(() => {
        // İsteğe bağlı olarak panoyu sıfırlama denenebilir
      }, 45000);
    } catch (err) {
      // Fallback
      const textArea = document.createElement('textarea');
      textArea.value = text;
      document.body.appendChild(textArea);
      textArea.select();
      document.execCommand('copy');
      document.body.removeChild(textArea);
      showToast(`${label} kopyalandı!`, 'success');
    }
  }

  // 2.1. Güvenli JSON Fetch Yardımcısı
  async function safeFetchJson(url, options = {}) {
    try {
      const res = await fetch(url, options);
      const text = await res.text();
      
      if (!text || !text.trim()) {
        if (res.status === 401) return { success: false, message: 'Hatalı parola!' };
        if (res.status === 404) return { success: false, message: 'İstenen işlem bulunamadı (404).' };
        if (res.status >= 500) return { success: false, message: 'Sunucuda geçici bir hata oluştu (HTTP ' + res.status + ').' };
        if (res.ok) return { success: true };
        return { success: false, message: 'Sunucu yanıt vermedi (HTTP ' + res.status + ').' };
      }

      try {
        return JSON.parse(text);
      } catch (e) {
        console.error('Geçersiz JSON yanıtı:', url, text);
        return { success: false, message: 'Sunucu yanıtı okunamadı: ' + text.substring(0, 100) };
      }
    } catch (networkErr) {
      console.warn('Ağ / Bağlantı hatası:', networkErr);
      throw new Error('Sunucuya bağlanılamadı. Lütfen sunucunun (PHP) çalıştığından emin olun.');
    }
  }

  // 3. Durum Kontrolü (Status Check)
  async function checkStatus() {
    try {
      const data = await safeFetchJson('./api/auth.php?action=status');

      if (!data.success) {
        showToast('Sunucu bağlantı hatası.', 'error');
        return;
      }

      state.isSetup = data.is_setup;
      state.isAuthenticated = data.is_authenticated;
      state.username = data.username;
      state.salt = data.salt;
      state.authKeySalt = data.auth_key_salt;

      renderAuthView();
    } catch (err) {
      console.error(err);
      showToast('Bağlantı kurulamadı. cPanel PHP/SQLite yapılandırmasını kontrol edin.', 'error');
    }
  }

  // 4. Giriş / Kurulum Ekranını Render Etme
  function renderAuthView() {
    if (state.masterKey) {
      // Kilit açık, ana görünümü göster
      dom.authView.style.display = 'none';
      dom.mainView.style.display = 'block';
      initProfilePhoto();
      loadVaultItems();
      return;
    }

    // Kilit kapalı, auth view göster
    dom.authView.style.display = 'flex';
    dom.mainView.style.display = 'none';
    dom.authForm.style.display = 'block';
    if (dom.otpForm) dom.otpForm.style.display = 'none';
    dom.masterPasswordInput.value = '';
    dom.confirmPasswordInput.value = '';
    if (dom.authError) dom.authError.textContent = '';

    if (!state.isSetup) {
      // İlk Kurulum
      dom.authCardTitle.textContent = 'SafeBadger Kurulumu';
      dom.authCardDesc.textContent = 'Güvenliğiniz için en az 8 karakterli bir parola belirleyin.';
      dom.confirmPasswordGroup.style.display = 'block';
      dom.authSubmitBtn.innerHTML = `<span>Girişi Oluştur</span>`;
    } else {
      // Giriş / Kilit Açma
      dom.authCardTitle.textContent = 'SafeBadger';
      dom.authCardDesc.textContent = 'Şifrelerinize erişmek için parolanızı girin.';
      dom.confirmPasswordGroup.style.display = 'none';
      dom.authSubmitBtn.innerHTML = `<span>Giriş Yap</span>`;
    }
  }

  // 5. Kurulum & Giriş Form İşlemleri
  async function handleAuthSubmit(e) {
    e.preventDefault();
    const password = dom.masterPasswordInput.value;
    if (dom.authError) dom.authError.textContent = '';

    if (!password || password.length < 8) {
      if (dom.authError) dom.authError.textContent = 'Parola en az 8 karakter olmalıdır.';
      return;
    }

    dom.authSubmitBtn.disabled = true;
    const originalText = dom.authSubmitBtn.innerHTML;
    dom.authSubmitBtn.innerHTML = '<span>Giriş yapılıyor...</span>';

    try {
      if (!state.isSetup) {
        // İLK KURULUM
        const confirmPass = dom.confirmPasswordInput.value;
        if (password !== confirmPass) {
          if (dom.authError) dom.authError.textContent = 'Parolalar eşleşmiyor!';
          dom.authSubmitBtn.disabled = false;
          dom.authSubmitBtn.innerHTML = originalText;
          return;
        }

        const salt = VaultCrypto.generateSalt(16);
        const authKeySalt = VaultCrypto.generateSalt(16);

        // 1) Sunucu için kimlik doğrulama hash'i
        const authHash = await VaultCrypto.deriveAuthHash(password, authKeySalt);

        // 2) İstemcide şifreleme anahtarı (RAM)
        const masterKey = await VaultCrypto.deriveMasterKey(password, salt);

        const data = await safeFetchJson('./api/auth.php?action=setup', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            username: 'Cenk',
            salt: salt,
            auth_key_salt: authKeySalt,
            auth_hash: authHash
          })
        });

        if (data.success) {
          state.isSetup = true;
          state.isAuthenticated = true;
          state.salt = salt;
          state.authKeySalt = authKeySalt;
          state.masterKey = masterKey;
          showToast('Şifre alanınız başarıyla oluşturuldu!', 'success');
          renderAuthView();
        } else {
          if (dom.authError) dom.authError.textContent = data.message || 'Kurulum başarısız oldu.';
        }
      } else {
        // GİRİŞ YAPMA / KİLİT AÇMA
        if (!state.authKeySalt) {
          const statData = await safeFetchJson('./api/auth.php?action=status');
          state.authKeySalt = statData.auth_key_salt;
          state.salt = statData.salt;
        }

        const authHash = await VaultCrypto.deriveAuthHash(password, state.authKeySalt);

        const data = await safeFetchJson('./api/auth.php?action=login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ auth_hash: authHash })
        });
        if (data.success && data.requires_2fa) {
          // 2FA Kodu Gerekli!
          state.tempPassword = password; // Kod onaylandığında masterKey türetmek için RAM'de geçici tut
          dom.authForm.style.display = 'none';
          dom.otpForm.style.display = 'block';
          if (dom.otpEmailTarget) dom.otpEmailTarget.textContent = data.email || 'your-email@example.com';
          dom.otpCodeInput.value = data.local_code || '';
          dom.otpCodeInput.focus();
        } else if (data.success) {
          const masterKey = await VaultCrypto.deriveMasterKey(password, state.salt);
          state.isAuthenticated = true;
          state.masterKey = masterKey;
          showToast('Kilit açıldı, hoş geldiniz.', 'success');
          renderAuthView();
        } else {
          if (dom.authError) dom.authError.textContent = data.message || 'Hatalı parola!';
        }
      }
    } catch (err) {
      console.error(err);
      if (dom.authError) dom.authError.textContent = 'Bir hata oluştu: ' + err.message;
    } finally {
      dom.authSubmitBtn.disabled = false;
      dom.authSubmitBtn.innerHTML = originalText;
    }
  }

  // 5.1. 2FA Kodu Doğrulama İşlemi
  async function handleOtpSubmit(e) {
    e.preventDefault();
    const code = dom.otpCodeInput.value.trim();
    if (dom.authError) dom.authError.textContent = '';

    if (!code || code.length !== 6) {
      if (dom.authError) dom.authError.textContent = 'Lütfen 6 haneli doğrulama kodunu eksiksiz girin.';
      return;
    }

    dom.otpSubmitBtn.disabled = true;
    const originalText = dom.otpSubmitBtn.innerHTML;
    dom.otpSubmitBtn.innerHTML = '<span>Doğrulanıyor...</span>';

    try {
      const res = await fetch('./api/auth.php?action=verify_2fa', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code: code })
      });

      const data = await res.json();
      if (data.success) {
        // Kod doğru! Geçici paroladan Master Key'i türet
        const masterKey = await VaultCrypto.deriveMasterKey(state.tempPassword, data.salt);
        delete state.tempPassword; // RAM'den sil

        state.isAuthenticated = true;
        state.masterKey = masterKey;
        dom.otpForm.style.display = 'none';
        showToast('E-posta doğrulaması başarılı. Hoş geldiniz!', 'success');
        renderAuthView();
      } else {
        if (dom.authError) dom.authError.textContent = data.message || 'Hatalı doğrulama kodu!';
      }
    } catch (err) {
      console.error(err);
      if (dom.authError) dom.authError.textContent = 'Doğrulama hatası: ' + err.message;
    } finally {
      dom.otpSubmitBtn.disabled = false;
      dom.otpSubmitBtn.innerHTML = originalText;
    }
  }

  // 5.2. 2FA Kodu Tekrar Gönder
  async function handleResendOtp() {
    try {
      const res = await fetch('./api/auth.php?action=resend_2fa', { method: 'POST' });
      const data = await res.json();
      if (data.success) {
        showToast('Yeni güvenlik kodu e-postanıza gönderildi.', 'info');
      } else {
        showToast(data.message || 'Kod gönderilemedi.', 'error');
      }
    } catch (e) {
      showToast('Bağlantı hatası.', 'error');
    }
  }

  // 6. Kasa Öğelerini Sunucudan Çekme ve Şifrelerini Çözme
  async function loadVaultItems() {
    if (!state.masterKey) return;

    try {
      const res = await fetch('./api/vault.php?action=list');
      if (res.status === 401) {
        // Oturum düşmüş
        state.masterKey = null;
        renderAuthView();
        return;
      }
      const data = await res.json();
      if (!data.success) return;

      state.vaultItems = data.items || [];
      state.decryptedMap.clear();

      // Her kaydı RAM'de paralel olarak çöz
      await Promise.all(
        state.vaultItems.map(async (item) => {
          try {
            const decrypted = await VaultCrypto.decrypt(item.encrypted_payload, item.iv, state.masterKey);
            state.decryptedMap.set(item.id, decrypted);
          } catch (e) {
            console.error(`Kayıt çözülemedi (ID: ${item.id}):`, e);
          }
        })
      );

      renderVaultList();
    } catch (err) {
      console.error('Kasa yükleme hatası:', err);
      showToast('Kasa kayıtları yüklenirken hata oluştu.', 'error');
    }
  }

  // 7. Kasa Listesini Ekrana Basma (Filtreleme & Arama)
  function renderVaultList() {
    if (!dom.vaultList) return;
    dom.vaultList.innerHTML = '';

    const query = state.searchQuery.toLowerCase().trim();
    const filterCat = state.activeCategory;

    const filtered = state.vaultItems.filter((item) => {
      // Kategori Filtresi
      if (filterCat === 'favorite' && !item.is_favorite) return false;
      if (filterCat !== 'all' && filterCat !== 'favorite' && item.category !== filterCat) return false;

      // Arama Filtresi
      if (query) {
        const decrypted = state.decryptedMap.get(item.id) || {};
        const titleMatch = (item.title_hint || '').toLowerCase().includes(query);
        const userMatch = (decrypted.username || '').toLowerCase().includes(query);
        const urlMatch = (decrypted.url || '').toLowerCase().includes(query);
        return titleMatch || userMatch || urlMatch;
      }

      return true;
    });

    // Sayaç güncelle
    if (dom.itemCountBadge) {
      dom.itemCountBadge.textContent = `${filtered.length} / ${state.vaultItems.length} Kayıt`;
    }

    if (filtered.length === 0) {
      dom.emptyState.style.display = 'block';
      return;
    }
    dom.emptyState.style.display = 'none';

    filtered.forEach((item) => {
      const data = state.decryptedMap.get(item.id) || { username: '', password: '', url: '', notes: '' };
      const card = createVaultCardElement(item, data);
      dom.vaultList.appendChild(card);
    });
  }

  // Popüler Servislerin Tek Renk Minimalist SVG İkon Kütüphanesi
  const BrandIcons = {
    google: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 14.93a5 5 0 1 1 0-9.86v2.43a2.5 2.5 0 1 0 0 5z"/><path d="M13 11.5h6"/></svg>`,
    gmail: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>`,
    outlook: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M10 4v16"/></svg>`,
    apple: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20.94c1.5 0 2.75-.7 3.5-.7s1.9.7 3.3.7c2.1 0 3.8-1.5 4.7-3.2-2.1-1.2-2.5-4.1-.7-5.7-1.1-1.7-2.9-2.7-4.8-2.7-1.6 0-2.8.8-3.6.8s-2.1-.8-3.7-.8c-2.4 0-4.6 1.5-5.7 3.9-1.5 3.3-.4 8.2 1.8 11.4 1 1.5 2.3 3.1 3.9 3.1z"/><path d="M15.5 2c0 1.5-.7 3-1.8 4-1.1 1-2.5 1.6-3.7 1.4.1-1.5.8-3 1.8-3.9C13 2.5 14.4 2 15.5 2z"/></svg>`,
    instagram: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>`,
    x: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4l16 16m0-16L4 20"/></svg>`,
    facebook: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>`,
    github: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>`,
    linkedin: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>`,
    youtube: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg>`,
    spotify: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M7 9.5c3-1 7-1 10 .5M8 12.5c2.5-.8 6-.8 8 .5M9 15.5c2-.5 4.5-.5 6 .5"/></svg>`,
    netflix: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 2v20l7-10 7 10V2"/></svg>`,
    chatgpt: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 3v18M3 12h18M5.5 5.5l13 13M18.5 5.5l-13 13"/></svg>`,
    discord: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 6h-2a14.5 14.5 0 0 0-8 0H6a3 3 0 0 0-3 3v6a3 3 0 0 0 3 3h1l2 2 2-2h2l2 2 2-2h1a3 3 0 0 0 3-3V9a3 3 0 0 0-3-3z"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/></svg>`,
    telegram: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>`,
    whatsapp: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/><path d="M9 10a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2"/></svg>`,
    steam: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><circle cx="15" cy="9" r="2.5"/><circle cx="9" cy="15" r="2.5"/><path d="M10.8 13.5l2.5-3"/></svg>`,
    amazon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 16c4 3 12 3 16 0M17 14l3 2-1 3"/></svg>`,
    bank: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/><circle cx="6" cy="15" r="1"/></svg>`,
    key: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 2l-2 2m-1.5 1.5L10 13l-4 1 1-4 7.5-7.5m1.5-1.5L19 3m-4 11l4 4m-2-6l4 4"/></svg>`
  };

  /**
   * Başlığa veya URL'ye göre Akıllı İkon Eşleştirici
   */
  function detectBrandIcon(title = '', url = '', category = 'personal') {
    const text = (title + ' ' + url).toLowerCase();

    if (text.includes('gmail')) return BrandIcons.gmail;
    if (text.includes('google')) return BrandIcons.google;
    if (text.includes('outlook') || text.includes('hotmail') || text.includes('microsoft') || text.includes('office') || text.includes('live.com')) return BrandIcons.outlook;
    if (text.includes('apple') || text.includes('icloud')) return BrandIcons.apple;
    if (text.includes('instagram') || text.includes('insta')) return BrandIcons.instagram;
    if (text.includes('twitter') || text.includes(' x ') || text.startsWith('x ') || text === 'x') return BrandIcons.x;
    if (text.includes('facebook') || text.includes('fb')) return BrandIcons.facebook;
    if (text.includes('github')) return BrandIcons.github;
    if (text.includes('linkedin')) return BrandIcons.linkedin;
    if (text.includes('youtube') || text.includes('yt')) return BrandIcons.youtube;
    if (text.includes('spotify')) return BrandIcons.spotify;
    if (text.includes('netflix')) return BrandIcons.netflix;
    if (text.includes('chatgpt') || text.includes('openai') || text.includes('claude') || text.includes('gemini')) return BrandIcons.chatgpt;
    if (text.includes('discord')) return BrandIcons.discord;
    if (text.includes('telegram') || text.includes('tg')) return BrandIcons.telegram;
    if (text.includes('whatsapp') || text.includes('wp')) return BrandIcons.whatsapp;
    if (text.includes('steam')) return BrandIcons.steam;
    if (text.includes('amazon') || text.includes('aws')) return BrandIcons.amazon;
    if (text.includes('bank') || text.includes('garanti') || text.includes('isbank') || text.includes('akbank') || text.includes('yapikredi') || text.includes('ziraat') || text.includes('finans') || text.includes('papara')) return BrandIcons.bank;

    // Kategoriye göre yedek ikon
    if (category === 'email') return BrandIcons.gmail;
    if (category === 'finance') return BrandIcons.bank;
    if (category === 'social') return BrandIcons.whatsapp;
    if (category === 'work') return BrandIcons.outlook;

    return BrandIcons.key;
  }

  // 8. Kart DOM Elemanı Oluşturma (Akıllı İkon Destekli)
  function createVaultCardElement(item, data) {
    const card = document.createElement('div');
    card.className = 'vault-item-card';

    const categoryNames = {
      social: 'Sosyal Medya',
      finance: 'Finans',
      email: 'E-Posta',
      work: 'İş',
      personal: 'Kişisel'
    };

    // Otomatik algılanan tek renk minimalist marka/servis ikonu
    const iconSvg = detectBrandIcon(item.title_hint, data.url, item.category);

    card.innerHTML = `
      <div class="item-left-wrap" data-action="edit">
        <div class="item-avatar">${iconSvg}</div>
        <div class="item-info">
          <span class="item-title">${escapeHtml(item.title_hint)}</span>
          <span class="item-subtitle">${escapeHtml(data.username || categoryNames[item.category] || 'Hesap')}</span>
        </div>
      </div>
      <div class="item-right-actions">
        <button class="item-action-btn" data-action="copy-user" title="Kullanıcı adını kopyala">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </button>
        <button class="item-action-btn" data-action="copy-pass" title="Şifreyi kopyala">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </button>
        <button class="item-action-btn fav-btn ${item.is_favorite ? 'active' : ''}" data-action="favorite" title="Favorilere Ekle/Kaldır">
          <svg viewBox="0 0 24 24" fill="${item.is_favorite ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="1.8">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
          </svg>
        </button>
      </div>
    `;

    // Tıklama olayları
    card.querySelector('[data-action="edit"]').addEventListener('click', () => openEditModal(item.id));
    card.querySelector('[data-action="favorite"]').addEventListener('click', (e) => {
      e.stopPropagation();
      toggleFavorite(item.id, !item.is_favorite);
    });
    card.querySelector('[data-action="copy-user"]').addEventListener('click', (e) => {
      e.stopPropagation();
      copyToClipboard(data.username, 'Kullanıcı Adı');
    });
    card.querySelector('[data-action="copy-pass"]').addEventListener('click', (e) => {
      e.stopPropagation();
      copyToClipboard(data.password, 'Şifre');
    });

    return card;
  }

  // 9. Kayıt Ekleme / Düzenleme Modalı
  function openCreateModal() {
    dom.itemModalTitle.textContent = 'Yeni Hesap Ekle';
    dom.itemForm.reset();
    dom.itemIdInput.value = '';
    dom.btnDeleteItem.style.display = 'none';
    updateStrengthBar('');
    dom.itemModal.classList.add('active');
  }

  function openEditModal(id) {
    const item = state.vaultItems.find(i => i.id === id);
    const data = state.decryptedMap.get(id) || {};
    if (!item) return;

    dom.itemModalTitle.textContent = 'Hesabı Düzenle';
    dom.itemIdInput.value = item.id;
    dom.itemTitleInput.value = item.title_hint || '';
    dom.itemCategorySelect.value = item.category || 'personal';
    dom.itemUsernameInput.value = data.username || '';
    dom.itemPasswordInput.value = data.password || '';
    dom.itemUrlInput.value = data.url || '';
    dom.itemNotesInput.value = data.notes || '';

    dom.btnDeleteItem.style.display = 'inline-flex';
    updateStrengthBar(data.password || '');
    dom.itemModal.classList.add('active');
  }

  function closeModal(modal) {
    if (modal) modal.classList.remove('active');
  }

  // 10. Kaydı Şifreleyip Sunucuya Kaydetme
  async function handleSaveItem(e) {
    e.preventDefault();
    if (!state.masterKey) return;

    const id = dom.itemIdInput.value || 'item_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7);
    const titleHint = dom.itemTitleInput.value.trim() || 'İsimsiz Hesap';
    const category = dom.itemCategorySelect.value;
    const username = dom.itemUsernameInput.value.trim();
    const password = dom.itemPasswordInput.value;
    const url = dom.itemUrlInput.value.trim();
    const notes = dom.itemNotesInput.value.trim();

    if (!titleHint) {
      showToast('Lütfen bir hesap başlığı girin.', 'warning');
      return;
    }

    const payload = { username, password, url, notes };

    try {
      // 1) Uçtan uca AES-256-GCM ile şifrele
      const encrypted = await VaultCrypto.encrypt(payload, state.masterKey);

      // 2) Sunucuya şifrelenmiş veriyi gönder
      const res = await fetch('./api/vault.php?action=save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id: id,
          title_hint: titleHint,
          category: category,
          encrypted_payload: encrypted.cipherText,
          iv: encrypted.iv
        })
      });

      const data = await res.json();
      if (data.success) {
        showToast('Hesap başarıyla şifrelendi ve kaydedildi.', 'success');
        closeModal(dom.itemModal);
        loadVaultItems();
      } else {
        showToast(data.message || 'Kayıt kaydedilemedi.', 'error');
      }
    } catch (err) {
      console.error(err);
      showToast('Şifreleme hatası: ' + err.message, 'error');
    }
  }

  // 11. Kayıt Silme
  async function handleDeleteItem() {
    const id = dom.itemIdInput.value;
    if (!id) return;

    if (!confirm('Bu hesabı silmek istediğinize emin misiniz?')) return;

    try {
      const res = await fetch('./api/vault.php?action=delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
      });
      const data = await res.json();
      if (data.success) {
        showToast('Hesap silindi.', 'success');
        closeModal(dom.itemModal);
        loadVaultItems();
      } else {
        showToast(data.message || 'Silinemedi.', 'error');
      }
    } catch (err) {
      showToast('Silme hatası: ' + err.message, 'error');
    }
  }

  // 12. Favori Durumu Değiştirme
  async function toggleFavorite(id, isFav) {
    try {
      const res = await fetch('./api/vault.php?action=toggle_favorite', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, is_favorite: isFav ? 1 : 0 })
      });
      const data = await res.json();
      if (data.success) {
        loadVaultItems();
      }
    } catch (err) {
      console.error(err);
    }
  }

  // 13. Güçlü Şifre Üretici Mekanizması
  function refreshGeneratedPassword() {
    const length = parseInt(dom.genLengthSlider.value, 10);
    dom.genLengthVal.textContent = length;

    const pass = VaultCrypto.generatePassword({
      length: length,
      uppercase: dom.genUpper.checked,
      lowercase: dom.genLower.checked,
      digits: dom.genDigits.checked,
      symbols: dom.genSymbols.checked
    });

    dom.genResultInput.value = pass;
  }

  function updateStrengthBar(password) {
    if (!dom.itemStrengthFill || !dom.itemStrengthText) return;
    const strength = VaultCrypto.calculateStrength(password);
    dom.itemStrengthFill.style.width = strength.percent + '%';
    dom.itemStrengthFill.style.backgroundColor = strength.color;
    dom.itemStrengthText.textContent = 'Güç: ' + strength.label;
    dom.itemStrengthText.style.color = strength.color;
  }

  // 14. Yedekleme & Geri Yükleme
  function exportBackup() {
    window.location.href = './api/backup.php?action=export';
    showToast('Şifreli yedek indiriliyor...', 'info');
  }

  async function importBackup(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = async (ev) => {
      try {
        const json = JSON.parse(ev.target.result);
        if (!json.items || !Array.isArray(json.items)) {
          showToast('Geçersiz SafeBadger yedek dosyası formatı.', 'error');
          return;
        }

        const res = await fetch('./api/backup.php?action=import', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ items: json.items })
        });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, 'success');
          closeModal(dom.settingsModal);
          loadVaultItems();
        } else {
          showToast(data.message || 'İçe aktarılamadı.', 'error');
        }
      } catch (err) {
        showToast('Dosya okuma hatası: ' + err.message, 'error');
      }
    };
    reader.readAsText(file);
  }

  // 15. Çıkış & Kilitleme
  async function handleLogout() {
    try {
      await fetch('./api/auth.php?action=logout', { method: 'POST' });
    } catch (e) {}

    // RAM'deki anahtarı ve verileri güvenle temizle
    state.masterKey = null;
    state.isAuthenticated = false;
    state.vaultItems = [];
    state.decryptedMap.clear();

    closeModal(dom.settingsModal);
    renderAuthView();
    showToast('Oturum kapatıldı ve şifreler kilitlendi.', 'info');
  }

  function handleLockNow() {
    state.masterKey = null;
    state.vaultItems = [];
    state.decryptedMap.clear();
    closeModal(dom.settingsModal);
    renderAuthView();
    showToast('Şifreler kilitlendi.', 'info');
  }

  // HTML Escape Helper
  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // 17. Tema (Karanlık / Aydınlık Mod) Yönetimi
  function initTheme() {
    const savedTheme = localStorage.getItem('safebadger_theme') || 'dark';
    applyTheme(savedTheme);
  }

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('safebadger_theme', theme);

    if (theme === 'light') {
      if (dom.themeIconDark) dom.themeIconDark.style.display = 'none';
      if (dom.themeIconLight) dom.themeIconLight.style.display = 'block';
      if (dom.settingsThemeText) dom.settingsThemeText.textContent = '☀️ Açık Mod';
    } else {
      if (dom.themeIconDark) dom.themeIconDark.style.display = 'block';
      if (dom.themeIconLight) dom.themeIconLight.style.display = 'none';
      if (dom.settingsThemeText) dom.settingsThemeText.textContent = '🌙 Koyu Mod';
    }
  }

  function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
    const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
    applyTheme(nextTheme);
    showToast(nextTheme === 'light' ? 'Aydınlık mod etkinleştirildi.' : 'Karanlık mod etkinleştirildi.', 'info');
  }

  // 18. Profil Fotoğrafı & Avatar Yönetimi
  function initProfilePhoto() {
    const savedPhoto = localStorage.getItem('safebadger_user_photo');
    const username = state.username || 'Cenk';
    const initial = username.charAt(0).toUpperCase() || 'C';

    if (dom.settingsUsernameLabel) dom.settingsUsernameLabel.textContent = username;

    if (savedPhoto) {
      setAvatarImage(savedPhoto);
      if (dom.btnRemovePhoto) dom.btnRemovePhoto.style.display = 'inline-flex';
    } else {
      resetAvatarImage(initial);
      if (dom.btnRemovePhoto) dom.btnRemovePhoto.style.display = 'none';
    }
  }

  function setAvatarImage(dataUrl) {
    if (dom.headerAvatarThumb) {
      dom.headerAvatarThumb.innerHTML = `<img src="${dataUrl}" alt="Avatar">`;
    }
    if (dom.settingsAvatarPreview) {
      dom.settingsAvatarPreview.innerHTML = `<img src="${dataUrl}" alt="Avatar">`;
    }
  }

  function resetAvatarImage(initial = 'C') {
    if (dom.headerAvatarThumb) {
      dom.headerAvatarThumb.innerHTML = `<span>${initial}</span>`;
    }
    if (dom.settingsAvatarPreview) {
      dom.settingsAvatarPreview.innerHTML = `<span>${initial}</span>`;
    }
  }

  function handlePhotoUpload(e) {
    const file = e.target.files[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
      showToast('Lütfen geçerli bir resim dosyası seçin.', 'warning');
      return;
    }

    const reader = new FileReader();
    reader.onload = (event) => {
      const img = new Image();
      img.onload = () => {
        // Resmi kare ve maksimum 256x256 boyutuna boyutlandır
        const canvas = document.createElement('canvas');
        const size = Math.min(img.width, img.height);
        const targetSize = Math.min(size, 256);

        canvas.width = targetSize;
        canvas.height = targetSize;
        const ctx = canvas.getContext('2d');

        // Merkeze ortalayarak kırp
        const startX = (img.width - size) / 2;
        const startY = (img.height - size) / 2;
        ctx.drawImage(img, startX, startY, size, size, 0, 0, targetSize, targetSize);

        const resizedBase64 = canvas.toDataURL('image/jpeg', 0.85);
        localStorage.setItem('safebadger_user_photo', resizedBase64);
        setAvatarImage(resizedBase64);
        if (dom.btnRemovePhoto) dom.btnRemovePhoto.style.display = 'inline-flex';
        showToast('Profil fotoğrafınız güncellendi!', 'success');
      };
      img.src = event.target.result;
    };
    reader.readAsDataURL(file);
  }

  function handleRemovePhoto() {
    localStorage.removeItem('safebadger_user_photo');
    const initial = (state.username || 'C').charAt(0).toUpperCase();
    resetAvatarImage(initial);
    if (dom.btnRemovePhoto) dom.btnRemovePhoto.style.display = 'none';
    if (dom.userPhotoInput) dom.userPhotoInput.value = '';
    showToast('Profil fotoğrafı kaldırıldı.', 'info');
  }

  // 19. Şifre Değiştirme / Sıfırlama E-posta Talebi
  async function requestPasswordResetLink() {
    try {
      showToast('Şifre değiştirme bağlantısı hazırlanıyor...', 'info');
      const data = await safeFetchJson('./api/auth.php?action=request_password_reset', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
      });
      if (data.success) {
        showToast('Şifre değiştirme bağlantısı ' + (data.email || '') + ' adresinize gönderildi! Lütfen gelen kutunuzu kontrol edin.', 'success');
        if (data.reset_url) {
          console.log('[SafeBadger Reset Link]:', data.reset_url);
          if (confirm('Şifre değiştirme bağlantısı your-email@example.com adresinize gönderildi!\n\nYerel test için hemen şifre yenileme ekranını açmak ister misiniz?')) {
            window.location.href = data.reset_url;
          }
        }
      } else {
        showToast(data.message || 'Bağlantı gönderilemedi.', 'error');
      }
    } catch (err) {
      showToast(err.message || 'Bağlantı isteği gönderilirken hata oluştu.', 'error');
    }
  }

  // 20. URL Token Kontrolü (Sayfa Açılışında)
  async function checkResetToken() {
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('reset_token');
    if (!token) return;

    try {
      const data = await safeFetchJson(`./api/auth.php?action=verify_reset_token&token=${encodeURIComponent(token)}`);
      if (data.success && data.valid) {
        if (dom.resetTokenInput) dom.resetTokenInput.value = token;
        if (data.salt) state.salt = data.salt;
        if (data.auth_key_salt) state.authKeySalt = data.auth_key_salt;

        if (dom.passwordResetModal) dom.passwordResetModal.classList.add('active');
        showToast('Şifre güncelleme bağlantısı doğrulandı. Yeni ana parolanızı belirleyin.', 'info');
      } else {
        showToast(data.message || 'Şifre sıfırlama bağlantısının süresi dolmuş veya geçersiz.', 'error');
        window.history.replaceState({}, '', window.location.pathname);
      }
    } catch (err) {
      console.error('Reset token hatası:', err);
    }
  }

  // 21. Yeni Şifreyi Kaydetme ve Kasayı Güncelleme
  async function handlePasswordResetSubmit(e) {
    e.preventDefault();
    if (dom.resetErrorMsg) {
      dom.resetErrorMsg.style.display = 'none';
      dom.resetErrorMsg.textContent = '';
    }

    const token = dom.resetTokenInput ? dom.resetTokenInput.value : '';
    const currentPass = dom.resetCurrentPasswordInput ? dom.resetCurrentPasswordInput.value : '';
    const newPass = dom.resetNewPasswordInput ? dom.resetNewPasswordInput.value : '';
    const confirmPass = dom.resetConfirmPasswordInput ? dom.resetConfirmPasswordInput.value : '';
    const forgotOld = dom.chkResetForgotOld ? dom.chkResetForgotOld.checked : false;

    if (!newPass || newPass.length < 8) {
      if (dom.resetErrorMsg) {
        dom.resetErrorMsg.textContent = 'Yeni ana parola en az 8 karakter olmalıdır.';
        dom.resetErrorMsg.style.display = 'block';
      }
      return;
    }

    if (newPass !== confirmPass) {
      if (dom.resetErrorMsg) {
        dom.resetErrorMsg.textContent = 'Yeni parolalar birbiriyle eşleşmiyor!';
        dom.resetErrorMsg.style.display = 'block';
      }
      return;
    }

    if (!token) {
      showToast('Sıfırlama anahtarı eksik. Lütfen mailinizdeki bağlantıyı tekrar açın.', 'error');
      return;
    }

    dom.btnSubmitPasswordReset.disabled = true;
    const originalBtnText = dom.btnSubmitPasswordReset.innerHTML;
    dom.btnSubmitPasswordReset.innerHTML = '<span>Güvenle işleniyor, lütfen bekleyin...</span>';

    try {
      const newSalt = VaultCrypto.generateSalt(16);
      const newAuthKeySalt = VaultCrypto.generateSalt(16);
      const newAuthHash = await VaultCrypto.deriveAuthHash(newPass, newAuthKeySalt);
      const newMasterKey = await VaultCrypto.deriveMasterKey(newPass, newSalt);

      // Durum A: Eski parolayı unuttuysa (Kasayı sıfırla)
      if (forgotOld) {
        const data = await safeFetchJson('./api/auth.php?action=complete_password_change', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            token,
            new_auth_hash: newAuthHash,
            new_salt: newSalt,
            new_auth_key_salt: newAuthKeySalt,
            reset_vault: true
          })
        });
        if (data.success) {
          state.masterKey = newMasterKey;
          state.salt = newSalt;
          state.authKeySalt = newAuthKeySalt;
          state.isAuthenticated = true;
          state.isSetup = true;
          state.items = [];
          if (dom.passwordResetModal) dom.passwordResetModal.classList.remove('active');
          window.history.replaceState({}, '', window.location.pathname);
          showToast('Ana parolanız sıfırlandı ve yeni kasanız açıldı.', 'success');
          checkStatus();
        } else {
          if (dom.resetErrorMsg) {
            dom.resetErrorMsg.textContent = data.message || 'Şifre güncellenemedi.';
            dom.resetErrorMsg.style.display = 'block';
          }
        }
        return;
      }

      // Durum B: Mevcut parolayı biliyorsa (Sıfır Veri Kaybı ile Yeniden Şifrele)
      if (!currentPass) {
        if (dom.resetErrorMsg) {
          dom.resetErrorMsg.textContent = 'Kayıtlı şifrelerinizi korumak için lütfen mevcut ana parolanızı girin (veya alttaki sıfırlama seçeneğini işaretleyin).';
          dom.resetErrorMsg.style.display = 'block';
        }
        return;
      }

      if (!state.salt) {
        const statData = await safeFetchJson('./api/auth.php?action=status');
        state.salt = statData.salt;
        state.authKeySalt = statData.auth_key_salt;
      }

      const oldMasterKey = await VaultCrypto.deriveMasterKey(currentPass, state.salt);

      // Kasa öğelerini çek (token ile)
      const listData = await safeFetchJson(`./api/auth.php?action=get_reset_vault_items&token=${encodeURIComponent(token)}`);
      const existingItems = (listData.success && Array.isArray(listData.items)) ? listData.items : [];

      const reencryptedItems = [];
      for (const item of existingItems) {
        try {
          const decrypted = await VaultCrypto.decryptData(item.encrypted_payload, item.iv, oldMasterKey);
          const reencrypted = await VaultCrypto.encryptData(decrypted, newMasterKey);
          reencryptedItems.push({
            id: item.id,
            title_hint: item.title_hint,
            category: item.category,
            encrypted_payload: reencrypted.ciphertext,
            iv: reencrypted.iv,
            is_favorite: item.is_favorite
          });
        } catch (decErr) {
          if (dom.resetErrorMsg) {
            dom.resetErrorMsg.textContent = 'Girdiğiniz mevcut ana parola hatalı! Veriler çözülemedi.';
            dom.resetErrorMsg.style.display = 'block';
          }
          return;
        }
      }

      // Sunucuya kaydet
      const data = await safeFetchJson('./api/auth.php?action=complete_password_change', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          token,
          new_auth_hash: newAuthHash,
          new_salt: newSalt,
          new_auth_key_salt: newAuthKeySalt,
          reencrypted_items: reencryptedItems
        })
      });

      if (data.success) {
        state.masterKey = newMasterKey;
        state.salt = newSalt;
        state.authKeySalt = newAuthKeySalt;
        state.isAuthenticated = true;
        state.isSetup = true;
        if (dom.passwordResetModal) dom.passwordResetModal.classList.remove('active');
        window.history.replaceState({}, '', window.location.pathname);
        showToast('Ana parolanız ve kayıtlarınız başarıyla güncellendi!', 'success');
        checkStatus();
      } else {
        if (dom.resetErrorMsg) {
          dom.resetErrorMsg.textContent = data.message || 'Şifre güncellenemedi.';
          dom.resetErrorMsg.style.display = 'block';
        }
      }
    } catch (err) {
      console.error(err);
      if (dom.resetErrorMsg) {
        dom.resetErrorMsg.textContent = 'Hata: ' + (err.message || 'İşlem gerçekleştirilemedi.');
        dom.resetErrorMsg.style.display = 'block';
      }
    } finally {
      dom.btnSubmitPasswordReset.disabled = false;
      dom.btnSubmitPasswordReset.innerHTML = originalBtnText;
    }
  }

  // 16. Olay Dinleyicileri (Event Listeners)
  function initEvents() {
    // Tema Olayları
    if (dom.btnToggleTheme) dom.btnToggleTheme.addEventListener('click', toggleTheme);
    if (dom.btnSettingsThemeToggle) dom.btnSettingsThemeToggle.addEventListener('click', toggleTheme);

    // Profil Fotoğrafı Olayları
    if (dom.userPhotoInput) dom.userPhotoInput.addEventListener('change', handlePhotoUpload);
    if (dom.btnRemovePhoto) dom.btnRemovePhoto.addEventListener('click', handleRemovePhoto);
    if (dom.btnHeaderProfile) {
      dom.btnHeaderProfile.addEventListener('click', () => {
        initProfilePhoto();
        dom.settingsModal.classList.add('active');
      });
    }

    // Auth Form & Şifre Göster/Gizle
    dom.authForm.addEventListener('submit', handleAuthSubmit);

    // 2FA OTP Olayları
    if (dom.otpForm) dom.otpForm.addEventListener('submit', handleOtpSubmit);
    if (dom.btnResendOtp) dom.btnResendOtp.addEventListener('click', handleResendOtp);
    if (dom.btnBackToPassword) {
      dom.btnBackToPassword.addEventListener('click', () => {
        dom.otpForm.style.display = 'none';
        dom.authForm.style.display = 'block';
        if (dom.authError) dom.authError.textContent = '';
      });
    }

    // Telefon Otomatik Kod Doldurma (Auto-Fill / One-Time-Code) Tetikleyicisi
    if (dom.otpCodeInput) {
      dom.otpCodeInput.addEventListener('input', (e) => {
        // Yalnızca rakamları al
        const cleanVal = e.target.value.replace(/[^0-9]/g, '');
        e.target.value = cleanVal;

        // 6 hane tamamlandığında (iOS klavyeden koda dokununca veya yapıştırınca) butona basmadan anında giriş yap
        if (cleanVal.length === 6) {
          if (typeof dom.otpForm.requestSubmit === 'function') {
            dom.otpForm.requestSubmit();
          } else {
            dom.otpSubmitBtn.click();
          }
        }
      });
    }

    const btnToggleAuthPass = document.getElementById('btnToggleAuthPass');
    if (btnToggleAuthPass) {
      btnToggleAuthPass.addEventListener('click', () => {
        const type = dom.masterPasswordInput.type === 'password' ? 'text' : 'password';
        dom.masterPasswordInput.type = type;
        if (dom.confirmPasswordInput) dom.confirmPasswordInput.type = type;
      });
    }

    // Arama
    dom.searchBar.addEventListener('input', (e) => {
      state.searchQuery = e.target.value;
      renderVaultList();
    });

    // Kategori Filtresi
    dom.categoryChips.forEach((chip) => {
      chip.addEventListener('click', () => {
        dom.categoryChips.forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        state.activeCategory = chip.dataset.category;
        renderVaultList();
      });
    });

    // Alt Navigasyon
    dom.navVaultBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    dom.navAddBtn.addEventListener('click', openCreateModal);

    if (dom.navGenBtn) {
      dom.navGenBtn.addEventListener('click', () => {
        refreshGeneratedPassword();
        dom.generatorModal.classList.add('active');
      });
    }

    dom.navSettingsBtn.addEventListener('click', () => {
      dom.settingsModal.classList.add('active');
    });

    // Hızlı Şablon Çipleri (Preset Chips)
    document.querySelectorAll('.quick-preset-chip').forEach((btn) => {
      btn.addEventListener('click', () => {
        if (dom.itemTitleInput) dom.itemTitleInput.value = btn.dataset.title || '';
        if (dom.itemCategorySelect) dom.itemCategorySelect.value = btn.dataset.cat || 'personal';
        if (dom.itemUrlInput && btn.dataset.url) dom.itemUrlInput.value = btn.dataset.url;
        if (dom.itemUsernameInput) dom.itemUsernameInput.focus();
      });
    });

    // Modalları kapatma (Overlay ve close butonları)
    document.querySelectorAll('.modal-overlay').forEach((overlay) => {
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay) closeModal(overlay);
      });
    });

    document.querySelectorAll('[data-close-modal]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const modal = btn.closest('.modal-overlay');
        closeModal(modal);
      });
    });

    // Kayıt Formu
    dom.itemForm.addEventListener('submit', handleSaveItem);
    dom.btnDeleteItem.addEventListener('click', handleDeleteItem);

    dom.itemPasswordInput.addEventListener('input', (e) => {
      updateStrengthBar(e.target.value);
    });

    dom.btnTogglePasswordVisibility.addEventListener('click', () => {
      const type = dom.itemPasswordInput.type === 'password' ? 'text' : 'password';
      dom.itemPasswordInput.type = type;
    });

    dom.btnGenerateInForm.addEventListener('click', () => {
      const generated = VaultCrypto.generatePassword({ length: 18 });
      dom.itemPasswordInput.value = generated;
      dom.itemPasswordInput.type = 'text';
      updateStrengthBar(generated);
      showToast('Güçlü parola oluşturuldu!', 'success');
    });

    // Şifre Üretici Modal Olayları
    dom.genLengthSlider.addEventListener('input', refreshGeneratedPassword);
    [dom.genUpper, dom.genLower, dom.genDigits, dom.genSymbols].forEach((el) => {
      el.addEventListener('change', refreshGeneratedPassword);
    });
    dom.btnRefreshGen.addEventListener('click', refreshGeneratedPassword);
    dom.btnCopyGen.addEventListener('click', () => {
      copyToClipboard(dom.genResultInput.value, 'Oluşturulan Parola');
    });
    dom.btnUseGenPassword.addEventListener('click', () => {
      const pass = dom.genResultInput.value;
      closeModal(dom.generatorModal);
      openCreateModal();
      dom.itemPasswordInput.value = pass;
      updateStrengthBar(pass);
    });

    // Ayarlar & Yedek
    dom.btnExportBackup.addEventListener('click', exportBackup);
    dom.importFileInput.addEventListener('change', importBackup);
    dom.btnLogout.addEventListener('click', handleLogout);
    dom.btnLockNow.addEventListener('click', handleLockNow);

    // Şifre Sıfırlama / Değiştirme
    if (dom.btnRequestResetLink) dom.btnRequestResetLink.addEventListener('click', requestPasswordResetLink);
    if (dom.btnSettingsRequestReset) dom.btnSettingsRequestReset.addEventListener('click', requestPasswordResetLink);
    if (dom.passwordResetForm) dom.passwordResetForm.addEventListener('submit', handlePasswordResetSubmit);
    if (dom.chkResetForgotOld) {
      dom.chkResetForgotOld.addEventListener('change', (e) => {
        if (dom.resetCurrentPassGroup) {
          dom.resetCurrentPassGroup.style.display = e.target.checked ? 'none' : 'block';
        }
      });
    }

    // Otomatik Kilitlenme için Etkinlik Takibi
    const resetActivity = () => { state.lastActivity = Date.now(); };
    window.addEventListener('mousemove', resetActivity);
    window.addEventListener('keydown', resetActivity);
    window.addEventListener('touchstart', resetActivity);

    // Her dakika kontrol et
    setInterval(() => {
      if (state.masterKey) {
        const diffMinutes = (Date.now() - state.lastActivity) / 1000 / 60;
        if (diffMinutes >= state.autoLockMinutes) {
          handleLockNow();
          showToast('Hareketsizlik nedeniyle şifreleriniz otomatik kilitlendi.', 'warning');
        }
      }
    }, 30000);
  }

  // Başlangıç
  window.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initEvents();
    initProfilePhoto();
    checkStatus();
    checkResetToken();
  });

  // Global API
  window.VaultApp = {
    showToast,
    openCreateModal,
    checkStatus
  };
})();
