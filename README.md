# 🛡️ SafeBadger — Kişisel Şifre Yöneticisi (cPanel & iOS PWA)

> **Kendi sunucunda barındırdığın, sıfır-bilgi mimarili, uçtan uca şifreli kişisel şifre kasası.**

SafeBadger, cPanel hosting ortamlarında **sıfır konfigürasyonla** çalışan, **Zero-Knowledge (Sıfır Bilgi)** uçtan uca **AES-256-GCM** şifrelemeli, iOS Safari üzerinden ana ekrana eklendiğinde tam bir mobil uygulama gibi çalışan bireysel bir şifre saklama projesidir.

## 📖 Proje Hakkında

SafeBadger; parolalarını üçüncü taraf bir buluta emanet etmek istemeyen, verisinin kontrolünü tamamen kendi elinde tutmak isteyen kullanıcılar için tasarlanmıştır. Uygulama saf **PHP + SQLite** ile yazıldığından, çalışması için ne harici bir veritabanı sunucusuna ne de karmaşık bir kuruluma ihtiyaç duyar — dosyaları cPanel'e atıp bir ana parola belirlemek yeterlidir.

Projenin temel felsefesi **"sunucu senin verini asla göremez"** ilkesidir: tüm şifreleme ve şifre çözme işlemleri tarayıcının içinde, **Web Crypto API** kullanılarak yapılır. Sunucuya yalnızca anlamsız, şifreli veri gider. Buna ek olarak **e-posta tabanlı 2FA (OTP)**, otomatik kilitlenme, şifreli yedekleme ve tam **PWA** desteği ile hem güvenli hem de günlük kullanımda pratik bir deneyim sunar.

**Teknoloji Yığını:** PHP 7.4+ · SQLite (opsiyonel MySQL) · Vanilla JavaScript · Web Crypto API (PBKDF2 + AES-256-GCM) · PWA (Service Worker + Manifest) · Apple Glassmorphism CSS

---

## 🌟 Öne Çıkan Özellikler

- **🔒 Sıfır-Bilgi (Zero-Knowledge) İstemci Şifreleme**:
  - Şifreler ve hesap bilgileri Web Crypto API (PBKDF2 + AES-256-GCM) ile sunucuya gitmeden önce tarayıcınızda şifrelenir.
  - Sunucu ve veritabanı şifrelerinizi asla düz metin olarak görmez; sunucu sahibi veya hosting sağlayıcısı veritabanını açsa bile sadece anlamsız şifreli metinler görür.
- **📧 İki Aşamalı E-Posta Doğrulama (2FA OTP)**:
  - Giriş yapılırken parolanız doğrulandıktan sonra **cenkfirtna@gmail.com** adresinize 6 haneli tek kullanımlık güvenlik kodu gönderilir.
  - Kod doğru girilmeden kasanın kilidi açılmaz, tam koruma sağlanır.
- **📱 iOS PWA (Mobil Uygulama Hissi)**:
  - iOS Safari'de "Paylaş ➔ Ana Ekrana Ekle" yaparak tam ekran, Safari çubuğu olmadan yerel bir iOS uygulaması gibi kullanın.
  - Apple Glassmorphism tasarım, akıcı animasyonlar, iOS güvenli alan (safe-area) uyumu.
- **🌗 Karanlık & Aydınlık Tema (Dark / Light Mode)**:
  - Kullanıcı istediği zaman üst bardaki veya ayarlar menüsündeki tek dokunuşla Koyu Mod ile Açık Mod arasında geçiş yapabilir.
- **👤 Kişisel Profil Fotoğrafı (Avatar)**:
  - İstediğiniz kişi veya profil fotoğrafını yükleyebilir, dilediğiniz zaman değiştirebilir veya kaldırabilirsiniz.
- **⚡ Hızlı Kopyalama & Güvenlik**:
  - Kullanıcı adı ve şifreyi tek dokunuşla panoya kopyalama.
  - Güçlü Şifre Üretici (Password Generator - uzunluk, harf, rakam, sembol ve şifre gücü ölçer).
  - 15 dakika hareketsizlik durumunda otomatik kilitlenme.
- **💾 Şifreli Yedekleme (Backup & Restore)**:
  - Tüm kayıtlarınızı tek tıkla şifreli JSON dosyası olarak bilgisayarınıza veya telefonunuza indirin ve geri yükleyin.

---

## 🚀 cPanel Kurulum Kılavuzu (3 Kolay Adım)

Bu proje SQLite veritabanı kullandığı için cPanel'de **veritabanı oluşturmanıza veya kullanıcı yetkilendirmenize gerek yoktur.**

1. **Dosyaları Yükleyin**:
   - Bu proje klasöründeki tüm dosyaları `.zip` haline getirin.
   - cPanel'inize giriş yapın ve **Dosya Yöneticisi (File Manager)**'ı açın.
   - `public_html` klasörüne (veya `sifreler.alanadiniz.com` gibi oluşturduğunuz bir alt etki alanının klasörüne) girip zip dosyasını yükleyin ve **Extract (Çıkar)** deyin.
2. **İzinleri Kontrol Edin**:
   - `data/` klasörünün yazma izninin (`755` veya `777`) olduğundan emin olun (SQLite veritabanı bu klasörün içinde otomatik oluşur).
   - `data/.htaccess` dosyası veritabanınızın dışarıdan indirilmesini otomatik olarak engeller.
3. **Kasayı Başlatın**:
   - Tarayıcınızdan sitenize gidin (ör. `https://sifreler.alanadiniz.com`).
   - Karşınıza gelen ilk ekranda güçlü bir **Ana Parola (Master Password)** belirleyin ve kasanızı başlatın!

*(İsteğe bağlı: MySQL kullanmak isterseniz `config.php` dosyasındaki `DB_TYPE` değerini `'mysql'` yapıp veritabanı bilgilerinizi yazabilirsiniz).*

---

## 📲 iOS Cihazınızda Mobil Uygulama Olarak Kullanma

1. iPhone veya iPad'inizde **Safari** tarayıcısını açın ve sitenize gidin.
2. Ekranın en altındaki **Paylaş (Share)** simgesine (kare içinden yukarı çıkan ok) dokunun.
3. Menüyü aşağı kaydırıp **"Ana Ekrana Ekle" (Add to Home Screen)** seçeneğini seçin.
4. Sağ üstteki **"Ekle"** butonuna dokunun.
5. Artık ana ekranınızda özel SafeBadger ikonuyla bağımsız bir mobil uygulama olarak çalışacaktır!

---

## 🛠️ Dosya Yapısı

```
├── index.php                 # Ana uygulama ve PWA arayüzü
├── config.php                # Veritabanı ve güvenlik yapılandırması
├── manifest.webmanifest      # PWA Manifest (iOS / Android)
├── service-worker.js         # Çevrimdışı önbellek ve servis çalışanı
├── .htaccess                 # Apache / cPanel güvenlik ve sıkıştırma kuralları
├── api/
│   ├── auth.php              # Durum, kurulum ve kimlik doğrulama API'si
│   ├── vault.php             # Şifreli kayıt ekleme/düzenleme/silme API'si
│   └── backup.php            # Şifreli JSON dışa ve içe aktarma
├── data/
│   ├── .htaccess             # Veritabanı koruma kuralı
│   └── vault.db              # SQLite veritabanı (otomatik oluşur)
└── assets/
    ├── css/style.css         # Apple Glassmorphic Dark & Light CSS
    ├── js/
    │   ├── crypto.js         # PBKDF2 & AES-256-GCM istemci şifreleme motoru
    │   ├── app.js            # Kasa yönetimi, tema ve profil fotoğrafı
    │   └── pwa.js            # iOS ana ekrana ekleme rehberi
    └── icons/                # iOS ve PWA yüksek çözünürlüklü ikonları
```

---

## ⚙️ Yapılandırma & Güvenlik Notları

- **SMTP parolası:** Depoda `config.php` içindeki `SMTP_PASS` değeri güvenlik gereği `YOUR_SMTP_PASSWORD` placeholder'ı ile bırakılmıştır. Canlıya alırken kendi cPanel e-posta hesabınızın parolasını buraya yazın.
- **E-posta adresi:** 2FA kodları ve şifre sıfırlama bağlantıları `config.php` içindeki `AUTH_EMAIL` adresine gönderilir; kendi adresinizle değiştirin.
- **Hassas dosyalar depoya dahil değildir:** `data/vault.db` (kasa veritabanı), tek kullanımlık OTP ve şifre sıfırlama log dosyaları `.gitignore` ile hariç tutulmuştur. Veritabanı ilk çalıştırmada otomatik oluşur.
- **Ana Parola:** Kasanızın tek anahtarı belirlediğiniz ana paroladır. Zero-Knowledge mimarisi gereği **unutulan ana parola sıfırlanamaz veya kurtarılamaz** — güçlü ve hatırlanabilir bir parola seçin.

---

## 📄 Lisans

Kişisel kullanım için geliştirilmiştir. Dilediğiniz gibi kullanabilir ve özelleştirebilirsiniz.
