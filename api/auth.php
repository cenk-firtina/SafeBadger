<?php
require_once __DIR__ . '/../config.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$db = get_db();

if ($action === 'status') {
    // Kurulum ve oturum durumu
    $stmt = $db->query("SELECT id, username, salt, auth_key_salt FROM users LIMIT 1");
    $user = $stmt->fetch();

    $isSetup = ($user !== false);
    $isAuth = is_authenticated();

    json_response([
        'success' => true,
        'is_setup' => $isSetup,
        'is_authenticated' => $isAuth,
        'username' => $user ? $user['username'] : null,
        'salt' => $user ? $user['salt'] : null,
        'auth_key_salt' => $user ? $user['auth_key_salt'] : null
    ]);
}

if ($action === 'setup') {
    if ($method !== 'POST') {
        json_response(['success' => false, 'message' => 'Geçersiz metod.'], 405);
    }

    // Zaten kurulu mu?
    $check = $db->query("SELECT COUNT(*) as cnt FROM users")->fetch();
    if ($check && (int)$check['cnt'] > 0) {
        json_response(['success' => false, 'message' => 'Sistem zaten kurulmuş.'], 400);
    }

    $input = get_json_input();
    $username = trim($input['username'] ?? 'Cenk');
    $salt = trim($input['salt'] ?? '');
    $authKeySalt = trim($input['auth_key_salt'] ?? '');
    $authHash = trim($input['auth_hash'] ?? '');

    if (empty($salt) || empty($authKeySalt) || empty($authHash)) {
        json_response(['success' => false, 'message' => 'Gerekli güvenlik anahtarları eksik.'], 400);
    }

    // auth_hash'i bcrypt ile ek katman olarak güvenle hashle
    $masterHash = password_hash($authHash, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $db->prepare("INSERT INTO users (username, master_hash, salt, auth_key_salt) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $masterHash, $salt, $authKeySalt]);

    $_SESSION['user_authenticated'] = true;
    $_SESSION['username'] = $username;

    json_response([
        'success' => true,
        'message' => 'Kasa başarıyla oluşturuldu ve kilit açıldı.',
        'username' => $username,
        'salt' => $salt,
        'auth_key_salt' => $authKeySalt
    ]);
}

if ($action === 'login') {
    if ($method !== 'POST') {
        json_response(['success' => false, 'message' => 'Geçersiz metod.'], 405);
    }

    $stmt = $db->query("SELECT * FROM users LIMIT 1");
    $user = $stmt->fetch();

    if (!$user) {
        json_response(['success' => false, 'message' => 'Sistem henüz kurulmamış.'], 400);
    }

    $input = get_json_input();
    $authHash = trim($input['auth_hash'] ?? '');

    if (empty($authHash)) {
        json_response(['success' => false, 'message' => 'Parola bilgisi eksik.'], 400);
    }

    if (password_verify($authHash, $user['master_hash'])) {
        // Parola doğru! 2FA Kodu üret ve E-Posta Gönder
        $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        $_SESSION['pending_auth'] = true;
        $_SESSION['pending_user'] = $user;
        $_SESSION['otp_code'] = $otp;
        $_SESSION['otp_expires'] = time() + 600; // 10 dakika

        send_otp_email(AUTH_EMAIL, $otp);

        $response = [
            'success' => true,
            'requires_2fa' => true,
            'email' => AUTH_EMAIL,
            'message' => '6 haneli doğrulama kodu ' . AUTH_EMAIL . ' adresine gönderildi.'
        ];

        // Yerel testlerde (localhost) geliştirici kolaylığı
        if (in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])) {
            $response['local_code'] = $otp;
        }

        json_response($response);
    } else {
        // Hatalı şifre gecikmesi (brute-force koruması)
        usleep(300000);
        json_response(['success' => false, 'message' => 'Hatalı parola!'], 401);
    }
}

if ($action === 'verify_2fa') {
    if ($method !== 'POST') {
        json_response(['success' => false, 'message' => 'Geçersiz metod.'], 405);
    }

    if (empty($_SESSION['pending_auth']) || empty($_SESSION['pending_user'])) {
        json_response(['success' => false, 'message' => 'Oturum süresi dolmuş. Lütfen tekrar parola girin.'], 400);
    }

    $input = get_json_input();
    $code = trim($input['code'] ?? '');

    if (empty($code) || strlen($code) !== 6) {
        json_response(['success' => false, 'message' => 'Lütfen 6 haneli kodu girin.'], 400);
    }

    if (time() > ($_SESSION['otp_expires'] ?? 0)) {
        json_response(['success' => false, 'message' => 'Doğrulama kodunun süresi dolmuş. Lütfen yeni kod isteyin.'], 400);
    }

    if ($code === (string)$_SESSION['otp_code']) {
        $user = $_SESSION['pending_user'];
        $_SESSION['user_authenticated'] = true;
        $_SESSION['username'] = $user['username'];

        // OTP temizle
        unset($_SESSION['pending_auth'], $_SESSION['pending_user'], $_SESSION['otp_code'], $_SESSION['otp_expires']);

        json_response([
            'success' => true,
            'message' => 'Doğrulama başarılı. Giriş yapıldı.',
            'username' => $user['username'],
            'salt' => $user['salt'],
            'auth_key_salt' => $user['auth_key_salt']
        ]);
    } else {
        json_response(['success' => false, 'message' => 'Girdiğiniz doğrulama kodu hatalı!'], 400);
    }
}

if ($action === 'resend_2fa') {
    if ($method !== 'POST') {
        json_response(['success' => false, 'message' => 'Geçersiz metod.'], 405);
    }

    if (empty($_SESSION['pending_auth'])) {
        json_response(['success' => false, 'message' => 'Aktif bir giriş denemesi bulunamadı.'], 400);
    }

    $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $_SESSION['otp_code'] = $otp;
    $_SESSION['otp_expires'] = time() + 600;

    send_otp_email(AUTH_EMAIL, $otp);

    json_response([
        'success' => true,
        'message' => 'Yeni doğrulama kodu ' . AUTH_EMAIL . ' adresine gönderildi.'
    ]);
}

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    json_response(['success' => true, 'message' => 'Güvenli çıkış yapıldı.']);
}

if ($action === 'request_password_reset') {
    if ($method !== 'POST') {
        json_response(['success' => false, 'message' => 'Geçersiz metod.'], 405);
    }

    $stmt = $db->query("SELECT * FROM users LIMIT 1");
    $user = $stmt->fetch();
    if (!$user) {
        json_response(['success' => false, 'message' => 'Sistem henüz kurulmamış.'], 400);
    }

    // Eski tokenları temizle
    $db->exec("DELETE FROM password_resets WHERE expires_at < " . time());

    // 64 karakter güvenli token
    $token = bin2hex(random_bytes(32));
    $expiresAt = time() + 1800; // 30 dakika

    $stmt = $db->prepare("INSERT INTO password_resets (token, expires_at) VALUES (?, ?)");
    $stmt->execute([$token, $expiresAt]);

    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8080';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $baseDir = rtrim(str_replace('/api', '', $scriptDir), '/\\');
    $resetUrl = "{$scheme}://{$host}{$baseDir}/index.php?reset_token={$token}";

    send_reset_email(AUTH_EMAIL, $resetUrl, $token);

    $response = [
        'success' => true,
        'message' => 'Şifre değiştirme bağlantısı ' . AUTH_EMAIL . ' adresine gönderildi.',
        'email' => AUTH_EMAIL
    ];

    if (in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])) {
        $response['reset_url'] = $resetUrl;
        $response['token'] = $token;
    }

    json_response($response);
}

if ($action === 'verify_reset_token') {
    $token = trim($_GET['token'] ?? '');
    if (empty($token)) {
        $input = get_json_input();
        $token = trim($input['token'] ?? '');
    }

    if (empty($token)) {
        json_response(['success' => false, 'message' => 'Token eksik.'], 400);
    }

    $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > ? LIMIT 1");
    $stmt->execute([$token, time()]);
    $resetRow = $stmt->fetch();

    if (!$resetRow) {
        json_response(['success' => false, 'message' => 'Şifre sıfırlama bağlantısının süresi dolmuş veya geçersiz.'], 400);
    }

    // Ayrıca kayıtlı kullanıcı salt ve auth salt bilgisini de dönelim ki client hazırlık yapabilsin
    $user = $db->query("SELECT salt, auth_key_salt FROM users LIMIT 1")->fetch();

    json_response([
        'success' => true,
        'valid' => true,
        'salt' => $user['salt'] ?? null,
        'auth_key_salt' => $user['auth_key_salt'] ?? null,
        'message' => 'Bağlantı geçerli.'
    ]);
}

if ($action === 'get_reset_vault_items') {
    $token = trim($_GET['token'] ?? '');
    if (empty($token)) {
        json_response(['success' => false, 'message' => 'Token eksik.'], 400);
    }

    $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > ? LIMIT 1");
    $stmt->execute([$token, time()]);
    if (!$stmt->fetch()) {
        json_response(['success' => false, 'message' => 'Geçersiz veya süresi dolmuş token.'], 400);
    }

    $items = $db->query("SELECT id, title_hint, category, encrypted_payload, iv, is_favorite FROM vault_items")->fetchAll();
    json_response(['success' => true, 'items' => $items]);
}

if ($action === 'complete_password_change') {
    if ($method !== 'POST') {
        json_response(['success' => false, 'message' => 'Geçersiz metod.'], 405);
    }

    $input = get_json_input();
    $token = trim($input['token'] ?? '');
    $newAuthHash = trim($input['new_auth_hash'] ?? '');
    $newSalt = trim($input['new_salt'] ?? '');
    $newAuthKeySalt = trim($input['new_auth_key_salt'] ?? '');
    $resetVault = !empty($input['reset_vault']);
    $reencryptedItems = $input['reencrypted_items'] ?? null;

    if (empty($token) || empty($newAuthHash) || empty($newSalt) || empty($newAuthKeySalt)) {
        json_response(['success' => false, 'message' => 'Gerekli bilgiler eksik.'], 400);
    }

    $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > ? LIMIT 1");
    $stmt->execute([$token, time()]);
    $resetRow = $stmt->fetch();

    if (!$resetRow) {
        json_response(['success' => false, 'message' => 'Bağlantı süresi dolmuş veya geçersiz.'], 400);
    }

    $stmt = $db->query("SELECT * FROM users LIMIT 1");
    $user = $stmt->fetch();
    if (!$user) {
        json_response(['success' => false, 'message' => 'Kullanıcı bulunamadı.'], 400);
    }

    $newMasterHash = password_hash($newAuthHash, PASSWORD_BCRYPT, ['cost' => 12]);

    $db->beginTransaction();
    try {
        $updateUser = $db->prepare("UPDATE users SET master_hash = ?, salt = ?, auth_key_salt = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $updateUser->execute([$newMasterHash, $newSalt, $newAuthKeySalt, $user['id']]);

        if ($resetVault) {
            $db->exec("DELETE FROM vault_items");
        } else if (is_array($reencryptedItems)) {
            $updateItem = $db->prepare("UPDATE vault_items SET title_hint = ?, category = ?, encrypted_payload = ?, iv = ?, is_favorite = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            foreach ($reencryptedItems as $item) {
                if (!empty($item['id']) && !empty($item['encrypted_payload']) && !empty($item['iv'])) {
                    $updateItem->execute([
                        $item['title_hint'] ?? 'İsimsiz',
                        $item['category'] ?? 'general',
                        $item['encrypted_payload'],
                        $item['iv'],
                        !empty($item['is_favorite']) ? 1 : 0,
                        $item['id']
                    ]);
                }
            }
        }

        $delToken = $db->prepare("DELETE FROM password_resets WHERE token = ?");
        $delToken->execute([$token]);

        $db->commit();

        $_SESSION['user_authenticated'] = true;
        $_SESSION['username'] = $user['username'];

        json_response([
            'success' => true,
            'message' => 'Ana parolanız başarıyla güncellendi.',
            'username' => $user['username'],
            'salt' => $newSalt,
            'auth_key_salt' => $newAuthKeySalt
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        json_response(['success' => false, 'message' => 'Şifre güncellenirken hata oluştu: ' . $e->getMessage()], 500);
    }
}

// Test mail gönderimi (sorun tespiti için)
if ($action === 'test_mail') {
    $results = [];
    $serverIP = $_SERVER['SERVER_ADDR'] ?? '77.245.149.48';
    $toEmail = AUTH_EMAIL;
    $from = SMTP_FROM;
    $user = SMTP_USER;
    $pass = SMTP_PASS;
    $subject = "=?UTF-8?B?" . base64_encode("SafeBadger Test") . "?=";
    $body = "<h2>SafeBadger Test Basarili!</h2><p>Saat: " . date('H:i:s') . "</p>";
    $smtpTests = [
        ['label' => 'localhost:587', 'host' => 'localhost', 'port' => 587],
        ['label' => 'localhost:25', 'host' => 'localhost', 'port' => 25],
        ['label' => 'ssl://localhost:465', 'host' => 'ssl://localhost', 'port' => 465],
        ['label' => "IP:587($serverIP)", 'host' => $serverIP, 'port' => 587],
        ['label' => "IP:25($serverIP)", 'host' => $serverIP, 'port' => 25],
        ['label' => "ssl://IP:465", 'host' => "ssl://$serverIP", 'port' => 465],
        ['label' => 'ssl://domain:465', 'host' => 'ssl://example.com', 'port' => 465],
    ];
    foreach ($smtpTests as $t) {
        $lg = [];
        $s = @fsockopen($t['host'], $t['port'], $en, $es, 5);
        if (!$s) { $results[] = ['t' => $t['label'], 'ok' => false, 'l' => "FAIL:[$en]$es"]; continue; }
        stream_set_timeout($s, 8);
        $r = function() use ($s) { $o=''; $i=stream_get_meta_data($s); while(!$i['timed_out']&&($x=@fgets($s,515))){$o.=$x;if(isset($x[3])&&$x[3]===' ')break;$i=stream_get_meta_data($s);} return trim($o); };
        try {
            $b=$r(); $lg[]="B:$b";
            if(substr($b,0,3)!=='220'){fclose($s);$results[]=['t'=>$t['label'],'ok'=>false,'l'=>implode('|',$lg)];continue;}
            fputs($s,"EHLO cp.local\r\n"); $eh=$r(); $lg[]="E:".explode("\n",$eh)[0];
            if(strpos($eh,'STARTTLS')!==false&&$t['port']!==465){
                fputs($s,"STARTTLS\r\n");$tr=$r();$lg[]="TLS:$tr";
                if(substr($tr,0,3)==='220'){$cr=@stream_socket_enable_crypto($s,true,STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT|STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT|STREAM_CRYPTO_METHOD_TLS_CLIENT);$lg[]=$cr?"TLS-OK":"TLS-FAIL";if($cr){fputs($s,"EHLO cp.local\r\n");$r();}}
            }
            fputs($s,"AUTH LOGIN\r\n");$ap=$r();$lg[]="A:$ap";$ao=false;
            if(substr($ap,0,3)==='334'){fputs($s,base64_encode($user)."\r\n");$r();fputs($s,base64_encode($pass)."\r\n");$ar=$r();$lg[]="AR:$ar";$ao=(substr($ar,0,3)==='235');}else{$ao=true;}
            if(!$ao){fputs($s,"QUIT\r\n");fclose($s);$results[]=['t'=>$t['label'],'ok'=>false,'l'=>implode('|',$lg)];continue;}
            fputs($s,"MAIL FROM:<$from>\r\n");$mf=$r();$lg[]="MF:$mf";if(substr($mf,0,3)!=='250'){fputs($s,"QUIT\r\n");fclose($s);$results[]=['t'=>$t['label'],'ok'=>false,'l'=>implode('|',$lg)];continue;}
            fputs($s,"RCPT TO:<$toEmail>\r\n");$rt=$r();$lg[]="RT:$rt";if(substr($rt,0,3)!=='250'){fputs($s,"QUIT\r\n");fclose($s);$results[]=['t'=>$t['label'],'ok'=>false,'l'=>implode('|',$lg)];continue;}
            fputs($s,"DATA\r\n");$dr=$r();$lg[]="D:$dr";if(substr($dr,0,3)!=='354'){fputs($s,"QUIT\r\n");fclose($s);$results[]=['t'=>$t['label'],'ok'=>false,'l'=>implode('|',$lg)];continue;}
            $en2="=?UTF-8?B?".base64_encode('SafeBadger')."?=";
            $hd="From:$en2 <$from>\r\nTo:$toEmail\r\nSubject:$subject\r\nMIME-Version:1.0\r\nContent-Type:text/html;charset=UTF-8\r\nDate:".date('r')."\r\nMessage-ID:<".uniqid('cp',true)."@example.com>\r\n";
            fputs($s,$hd."\r\n".$body."\r\n.\r\n");$sr=$r();$lg[]="S:$sr";
            fputs($s,"QUIT\r\n");@fclose($s);
            $ok=(substr($sr,0,3)==='250');$results[]=['t'=>$t['label'],'ok'=>$ok,'l'=>implode('|',$lg)];
            if($ok)break;
        } catch(\Throwable $e){@fclose($s);$lg[]="EX:".$e->getMessage();$results[]=['t'=>$t['label'],'ok'=>false,'l'=>implode('|',$lg)];}
    }
    json_response(['success'=>true,'smtp_results'=>$results,'mail_fn'=>function_exists('mail'),'os'=>PHP_OS_FAMILY,'ip'=>$serverIP,'data_w'=>is_writable(__DIR__.'/../data/')]);
}

json_response(['success' => false, 'message' => 'Bilinmeyen işlem.'], 404);

