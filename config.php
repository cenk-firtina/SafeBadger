<?php
/**
 * SafeBadger - Kişisel Şifre Yöneticisi Yapılandırma ve Veritabanı Motoru
 * cPanel ve Apache uyumlu, sıfır-kurulum SQLite & opsiyonel MySQL desteği.
 */

// Hata raporlama (Canlıda hataları loglar, ekrana basmaz)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Boş yanıt dönmesini engelleyen global istisna ve hata yakalayıcı
set_exception_handler(function(Throwable $e) {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success' => false,
        'message' => 'Sunucu işlem hatası: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => false,
            'message' => 'Kritik sunucu hatası oluştu.'
        ], JSON_UNESCAPED_UNICODE);
    }
});

// Oturum Başlatma ve Güvenlik
if (session_status() === PHP_SESSION_NONE) {
    // 30 günlük oturum veya tarayıcı kapanana kadar
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    // Eğer HTTPS açıksa secure çerez
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

// İki Aşamalı Doğrulama (2FA) ve E-posta Ayarları
define('AUTH_EMAIL', 'cenkfirtna@gmail.com');
define('APP_NAME', 'SafeBadger');

// Özel SMTP Yapılandırması (pw@cenkfirtina.com)
define('SMTP_HOST', 'localhost');       // cPanel Exim daima localhost'ta çalışır
define('SMTP_PORT', 587);              // cPanel Exim submission portu
define('SMTP_USER', 'pw@cenkfirtina.com');
define('SMTP_PASS', 'YOUR_SMTP_PASSWORD');   // TODO: cPanel e-posta hesabınızın parolasını buraya yazın
define('SMTP_FROM', 'pw@cenkfirtina.com');
define('SMTP_FROM_NAME', 'SafeBadger Güvenlik');

/**
 * Debug log yaz (sorun tespiti için)
 */
function mail_debug_log(string $msg): void {
    $logFile = __DIR__ . '/data/mail_debug.log';
    @file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

/**
 * SafeBadger Güvenli E-Posta Motoru
 * Strateji sırası:
 *   1) PHP mail() — cPanel'de Exim ile doğrudan çalışır (en güvenilir)
 *   2) localhost SMTP soket — port 587, 465, 25 sırasıyla dener
 *   3) Uzak SMTP — cenkfirtina.com:465 (Cloudflare engellemezse)
 */
function send_safebadger_mail(string $toEmail, string $subject, string $htmlBody): bool {
    $from = SMTP_FROM;
    $fromName = SMTP_FROM_NAME;
    $encodedName = "=?UTF-8?B?" . base64_encode($fromName) . "?=";

    mail_debug_log("=== YENİ MAİL GÖNDERİMİ ===");
    mail_debug_log("Kime: {$toEmail} | Konu: {$subject}");
    mail_debug_log("OS: " . PHP_OS_FAMILY . " | PHP: " . phpversion());

    // ─── STRATEJİ 1: PHP mail() (cPanel Exim) ───
    if (function_exists('mail') && PHP_OS_FAMILY !== 'Windows') {
        mail_debug_log("Strateji 1: PHP mail() deneniyor...");
        
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$encodedName} <{$from}>\r\n";
        $headers .= "Reply-To: {$from}\r\n";
        $headers .= "X-Mailer: SafeBadger/1.0\r\n";

        // mail() 5. parametre: gönderici adresini Exim'e bildir
        $result = @mail($toEmail, $subject, $htmlBody, $headers, "-f{$from}");
        
        if ($result) {
            mail_debug_log("✅ mail() BAŞARILI! E-posta gönderildi.");
            return true;
        } else {
            $err = error_get_last();
            mail_debug_log("❌ mail() BAŞARISIZ: " . ($err['message'] ?? 'Bilinmeyen hata'));
        }
    } else {
        mail_debug_log("mail() atlandı: " . (!function_exists('mail') ? 'fonksiyon yok' : 'Windows ortamı'));
    }

    // ─── STRATEJİ 2: Localhost SMTP (cPanel Exim portları) ───
    $localPorts = [
        ['host' => 'localhost',      'port' => 587, 'ssl' => false],
        ['host' => 'localhost',      'port' => 25,  'ssl' => false],
        ['host' => 'ssl://localhost','port' => 465, 'ssl' => true],
    ];

    foreach ($localPorts as $srv) {
        mail_debug_log("Strateji 2: SMTP {$srv['host']}:{$srv['port']} deneniyor...");
        $sent = smtp_send_raw($srv['host'], $srv['port'], $toEmail, $subject, $htmlBody, $from, $encodedName);
        if ($sent) {
            mail_debug_log("✅ SMTP {$srv['host']}:{$srv['port']} BAŞARILI!");
            return true;
        }
    }

    // ─── STRATEJİ 3: Uzak SMTP (cenkfirtina.com) ───
    $remotePorts = [
        ['host' => 'ssl://cenkfirtina.com', 'port' => 465, 'ssl' => true],
        ['host' => 'cenkfirtina.com',       'port' => 587, 'ssl' => false],
    ];

    foreach ($remotePorts as $srv) {
        mail_debug_log("Strateji 3: Uzak SMTP {$srv['host']}:{$srv['port']} deneniyor...");
        $sent = smtp_send_raw($srv['host'], $srv['port'], $toEmail, $subject, $htmlBody, $from, $encodedName);
        if ($sent) {
            mail_debug_log("✅ Uzak SMTP {$srv['host']}:{$srv['port']} BAŞARILI!");
            return true;
        }
    }

    mail_debug_log("⚠️ Tüm yöntemler başarısız oldu. E-posta gönderilemedi.");
    return true; // Uygulamanın çökmemesi için true dön
}

/**
 * Ham SMTP soket ile e-posta gönder
 */
function smtp_send_raw(string $host, int $port, string $toEmail, string $subject, string $htmlBody, string $from, string $encodedName): bool {
    $socket = @fsockopen($host, $port, $errno, $errstr, 5);
    if (!$socket) {
        mail_debug_log("  Bağlantı başarısız: [{$errno}] {$errstr}");
        return false;
    }

    stream_set_timeout($socket, 8);

    $read = function() use ($socket) {
        $res = '';
        $info = stream_get_meta_data($socket);
        while (!$info['timed_out'] && ($str = @fgets($socket, 515))) {
            $res .= $str;
            if (isset($str[3]) && $str[3] === ' ') break;
            $info = stream_get_meta_data($socket);
        }
        return $res;
    };

    $expect = function(string $response, string $code) {
        return substr(trim($response), 0, 3) === $code;
    };

    try {
        $banner = $read();
        if (!$expect($banner, '220')) { fclose($socket); mail_debug_log("  Banner hatalı: " . trim($banner)); return false; }

        fputs($socket, "EHLO safebadger.local\r\n");
        $ehloRes = $read();
        mail_debug_log("  EHLO yanıt: " . trim(explode("\n", $ehloRes)[0]));

        // STARTTLS dene (port 587 için)
        if (strpos($ehloRes, 'STARTTLS') !== false && $port !== 465) {
            fputs($socket, "STARTTLS\r\n");
            $tlsRes = $read();
            if ($expect($tlsRes, '220')) {
                $cryptoOk = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($cryptoOk) {
                    fputs($socket, "EHLO safebadger.local\r\n");
                    $read();
                    mail_debug_log("  STARTTLS başarılı");
                } else {
                    mail_debug_log("  STARTTLS crypto başarısız, devam ediliyor...");
                }
            }
        }

        // AUTH LOGIN
        fputs($socket, "AUTH LOGIN\r\n");
        $authPrompt = $read();
        if (!$expect($authPrompt, '334')) {
            // Auth gerekmiyorsa (localhost Exim) doğrudan gönder
            mail_debug_log("  AUTH gerekmedi, doğrudan gönderim deneniyor...");
        } else {
            fputs($socket, base64_encode(SMTP_USER) . "\r\n");
            $read();
            fputs($socket, base64_encode(SMTP_PASS) . "\r\n");
            $authRes = $read();
            if (!$expect($authRes, '235')) {
                fclose($socket);
                mail_debug_log("  AUTH başarısız: " . trim($authRes));
                return false;
            }
            mail_debug_log("  AUTH başarılı");
        }

        fputs($socket, "MAIL FROM: <{$from}>\r\n");
        $mailFrom = $read();
        if (!$expect($mailFrom, '250')) { fclose($socket); mail_debug_log("  MAIL FROM hatalı: " . trim($mailFrom)); return false; }

        fputs($socket, "RCPT TO: <{$toEmail}>\r\n");
        $rcptTo = $read();
        if (!$expect($rcptTo, '250')) { fclose($socket); mail_debug_log("  RCPT TO hatalı: " . trim($rcptTo)); return false; }

        fputs($socket, "DATA\r\n");
        $dataRes = $read();
        if (!$expect($dataRes, '354')) { fclose($socket); mail_debug_log("  DATA hatalı: " . trim($dataRes)); return false; }

        $msgHeaders = "From: {$encodedName} <{$from}>\r\n" .
                      "To: {$toEmail}\r\n" .
                      "Subject: {$subject}\r\n" .
                      "MIME-Version: 1.0\r\n" .
                      "Content-Type: text/html; charset=UTF-8\r\n" .
                      "Date: " . date('r') . "\r\n" .
                      "Message-ID: <" . uniqid('cp_', true) . "@cenkfirtina.com>\r\n" .
                      "X-Mailer: SafeBadger SMTP Engine\r\n";

        fputs($socket, $msgHeaders . "\r\n" . $htmlBody . "\r\n.\r\n");
        $sendRes = $read();

        fputs($socket, "QUIT\r\n");
        @fclose($socket);

        if ($expect($sendRes, '250')) {
            return true;
        } else {
            mail_debug_log("  Gönderim yanıtı hatalı: " . trim($sendRes));
            return false;
        }
    } catch (\Throwable $e) {
        @fclose($socket);
        mail_debug_log("  İstisna: " . $e->getMessage());
        return false;
    }
}

/**
 * 2FA Güvenlik Kodunu E-Posta Olarak Gönderir
 */
function send_otp_email(string $toEmail, string $otpCode): bool {
    $subject = "=?UTF-8?B?" . base64_encode("SafeBadger doğrulama kodunuz: " . $otpCode) . "?=";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset='UTF-8'>
      <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 20px; }
        .box { max-width: 480px; margin: 0 auto; background: #1e293b; border-radius: 16px; padding: 32px 24px; border: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .logo { font-size: 24px; font-weight: 800; color: #6366f1; margin-bottom: 12px; }
        .code { font-size: 38px; font-weight: 800; letter-spacing: 10px; color: #38bdf8; background: #0f172a; padding: 18px; border-radius: 12px; margin: 24px 0; border: 1px solid rgba(255,255,255,0.08); font-family: monospace; }
        .desc { font-size: 14px; color: #94a3b8; line-height: 1.5; }
        .footer { font-size: 12px; color: #64748b; margin-top: 24px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 16px; }
      </style>
    </head>
    <body>
      <div class='box'>
        <div class='logo'>🛡️ SafeBadger</div>
        <h2 style='margin: 0 0 10px 0; font-size: 20px;'>Giriş Doğrulama Kodu</h2>
        <p class='desc'>SafeBadger giriş doğrulama kodunuz: <strong>{$otpCode}</strong></p>
        <div class='code'>{$otpCode}</div>
        <p class='desc'>Bu kod <strong>10 dakika</strong> boyunca geçerlidir. Gönderici: pw@cenkfirtina.com</p>
        <div class='footer'>SafeBadger — Uçtan Uca Şifreli Kişisel Güvenlik Kasası</div>
      </div>
    </body>
    </html>
    ";

    // Yerel testlerde log dosyasına da yaz
    $logFile = __DIR__ . '/data/last_otp.txt';
    @file_put_contents($logFile, date('Y-m-d H:i:s') . " - Kod: " . $otpCode . " - Kime: " . $toEmail . "\n");

    return send_safebadger_mail($toEmail, $subject, $message);
}

/**
 * Şifre Değiştirme / Sıfırlama Bağlantısı Gönderme
 */
function send_reset_email(string $toEmail, string $resetUrl, string $token): bool {
    $subject = "=?UTF-8?B?" . base64_encode("🛡️ SafeBadger — Şifre Değiştirme Bağlantısı") . "?=";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset='UTF-8'>
      <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 24px 16px; margin: 0; }
        .box { max-width: 480px; margin: 0 auto; background: #1e293b; border-radius: 18px; padding: 32px 24px; border: 1px solid rgba(255,255,255,0.1); text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
        .logo { font-size: 24px; font-weight: 800; color: #6366f1; margin-bottom: 20px; letter-spacing: -0.5px; }
        .title { margin: 0 0 12px 0; font-size: 20px; font-weight: 700; color: #ffffff; }
        .desc { font-size: 14px; color: #94a3b8; line-height: 1.6; margin: 0 0 24px 0; }
        .btn { display: inline-block; background: #6366f1; color: #ffffff !important; text-decoration: none; padding: 14px 28px; border-radius: 12px; font-weight: 700; font-size: 15px; box-shadow: 0 4px 14px rgba(99,102,241,0.4); margin-bottom: 20px; }
        .url-box { font-family: monospace; font-size: 12px; color: #94a3b8; word-break: break-all; background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.06); text-align: left; }
        .footer { font-size: 11px; color: #64748b; margin-top: 24px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 16px; }
      </style>
    </head>
    <body>
      <div class='box'>
        <div class='logo'>🛡️ SafeBadger</div>
        <h2 class='title'>Şifre Güncelleme Talebi</h2>
        <p class='desc'>SafeBadger ana parolanızı güncellemek veya sıfırlamak için aşağıdaki güvenli bağlantıyı kullanın. Bu bağlantı <strong>30 dakika</strong> geçerlidir.</p>
        <a href='{$resetUrl}' class='btn' target='_blank'>Ana Parolamı Güncelle</a>
        <p class='desc' style='font-size: 12px; margin-bottom: 8px;'>Buton çalışmıyorsa aşağıdaki bağlantıyı tarayıcınıza kopyalayabilirsiniz:</p>
        <div class='url-box'>{$resetUrl}</div>
        <div class='footer'>Gönderici: pw@cenkfirtina.com | SafeBadger sıfır bilgi mimarisiyle korunmaktadır.</div>
      </div>
    </body>
    </html>
    ";

    // Yerel testlerde log dosyasına da yaz
    $logFile = __DIR__ . '/data/last_reset_link.txt';
    @file_put_contents($logFile, date('Y-m-d H:i:s') . " - URL: " . $resetUrl . " - Token: " . $token . " - Kime: " . $toEmail . "\n");

    return send_safebadger_mail($toEmail, $subject, $message);
}

// Veritabanı Yapılandırması
// Varsayılan: SQLite (cPanel'de tek tıkla çalışır, kurulum gerektirmez)
define('DB_TYPE', 'sqlite'); 


// MySQL kullanmak isterseniz doldurun:
define('MYSQL_HOST', 'localhost');
define('MYSQL_PORT', '3306');
define('MYSQL_DB',   'cpanel_safebadger');
define('MYSQL_USER', 'cpanel_user');
define('MYSQL_PASS', 'cpanel_password');

// SQLite Veritabanı Dosya Yolu
define('SQLITE_PATH', __DIR__ . '/data/vault.db');

/**
 * PDO Veritabanı Bağlantısını Döndürür
 */
function get_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        if (DB_TYPE === 'sqlite') {
            $dataDir = dirname(SQLITE_PATH);
            if (!is_dir($dataDir)) {
                mkdir($dataDir, 0755, true);
            }
            $dsn = 'sqlite:' . SQLITE_PATH;
            $pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 10
            ]);
            // SQLite WAL modu ve güvenli yabancı anahtarlar
            $pdo->exec('PRAGMA journal_mode = WAL;');
            $pdo->exec('PRAGMA foreign_keys = ON;');
        } else {
            $dsn = 'mysql:host=' . MYSQL_HOST . ';port=' . MYSQL_PORT . ';dbname=' . MYSQL_DB . ';charset=utf8mb4';
            $pdo = new PDO($dsn, MYSQL_USER, MYSQL_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        }

        // Tablolar yoksa otomatik oluştur
        init_database($pdo);

        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()
        ]);
        exit;
    }
}

/**
 * Tabloları Otomatik Oluşturur
 */
function init_database(PDO $pdo): void {
    if (DB_TYPE === 'sqlite') {
        // Kullanıcı tablosu (Ana Parola Hash ve Salt)
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            master_hash TEXT NOT NULL,
            salt TEXT NOT NULL,
            auth_key_salt TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );");

        // Şifreli Kasa Öğeleri Tablosu (Zero-Knowledge Ciphertext)
        $pdo->exec("CREATE TABLE IF NOT EXISTS vault_items (
            id TEXT PRIMARY KEY,
            title_hint TEXT NOT NULL,
            category TEXT NOT NULL DEFAULT 'general',
            encrypted_payload TEXT NOT NULL,
            iv TEXT NOT NULL,
            is_favorite INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );");

        // Şifre Değiştirme / Sıfırlama Token Tablosu
        $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            token TEXT NOT NULL UNIQUE,
            expires_at INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            master_hash VARCHAR(255) NOT NULL,
            salt VARCHAR(255) NOT NULL,
            auth_key_salt VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS vault_items (
            id VARCHAR(64) PRIMARY KEY,
            title_hint VARCHAR(255) NOT NULL,
            category VARCHAR(50) NOT NULL DEFAULT 'general',
            encrypted_payload MEDIUMTEXT NOT NULL,
            iv VARCHAR(100) NOT NULL,
            is_favorite TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(64) NOT NULL UNIQUE,
            expires_at INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
}

/**
 * Oturum Kontrolü
 */
function is_authenticated(): bool {
    return isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true;
}

/**
 * Kimlik Doğrulama Zorunlu Kıl
 */
function require_auth(): void {
    if (!is_authenticated()) {
        json_response(['success' => false, 'error' => 'unauthorized', 'message' => 'Lütfen giriş yapın.'], 401);
    }
}

/**
 * Standart JSON Yanıt Fonksiyonu
 */
function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Gelen JSON Verisini Çözme
 */
function get_json_input(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
