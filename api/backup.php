<?php
require_once __DIR__ . '/../config.php';

require_auth();

$action = $_GET['action'] ?? 'export';
$method = $_SERVER['REQUEST_METHOD'];
$db = get_db();

if ($action === 'export') {
    $stmt = $db->query("SELECT id, title_hint, category, encrypted_payload, iv, is_favorite, created_at, updated_at FROM vault_items ORDER BY created_at ASC");
    $items = $stmt->fetchAll();

    $backupData = [
        'app' => 'SafeBadger',
        'version' => '1.0.0',
        'export_date' => date('c'),
        'total_items' => count($items),
        'items' => $items
    ];

    $filename = 'safebadger_backup_' . date('Y-m-d_His') . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'import') {
    if ($method !== 'POST') {
        json_response(['success' => false, 'message' => 'Geçersiz metod.'], 405);
    }

    $input = get_json_input();
    $items = $input['items'] ?? [];

    if (!is_array($items) || empty($items)) {
        json_response(['success' => false, 'message' => 'İçe aktarılacak geçerli kayıt bulunamadı.'], 400);
    }

    $importedCount = 0;
    $stmt = $db->prepare("INSERT OR REPLACE INTO vault_items (id, title_hint, category, encrypted_payload, iv, is_favorite) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($items as $item) {
        $id = $item['id'] ?? '';
        $titleHint = $item['title_hint'] ?? 'İsimsiz';
        $category = $item['category'] ?? 'general';
        $encryptedPayload = $item['encrypted_payload'] ?? '';
        $iv = $item['iv'] ?? '';
        $isFavorite = !empty($item['is_favorite']) ? 1 : 0;

        if ($id && $encryptedPayload && $iv) {
            $stmt->execute([$id, $titleHint, $category, $encryptedPayload, $iv, $isFavorite]);
            $importedCount++;
        }
    }

    json_response([
        'success' => true,
        'message' => "Toplam {$importedCount} kayıt başarıyla içe aktarıldı."
    ]);
}

json_response(['success' => false, 'message' => 'Bilinmeyen işlem.'], 404);
