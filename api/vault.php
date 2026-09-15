<?php
require_once __DIR__ . '/../config.php';

require_auth();

$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];
$db = get_db();

if ($action === 'list') {
    $stmt = $db->query("SELECT id, title_hint, category, encrypted_payload, iv, is_favorite, created_at, updated_at FROM vault_items ORDER BY is_favorite DESC, updated_at DESC");
    $items = $stmt->fetchAll();

    json_response([
        'success' => true,
        'items' => $items
    ]);
}

if ($action === 'save') {
    if ($method !== 'POST') {
        json_response(['success' => false, 'message' => 'Geçersiz metod.'], 405);
    }

    $input = get_json_input();
    $id = trim($input['id'] ?? '');
    $titleHint = trim($input['title_hint'] ?? 'İsimsiz');
    $category = trim($input['category'] ?? 'general');
    $encryptedPayload = trim($input['encrypted_payload'] ?? '');
    $iv = trim($input['iv'] ?? '');
    $isFavorite = !empty($input['is_favorite']) ? 1 : 0;

    if (empty($id) || empty($encryptedPayload) || empty($iv)) {
        json_response(['success' => false, 'message' => 'Eksik veya hatalı şifrelenmiş veri.'], 400);
    }

    // Var olan kayıt mı?
    $check = $db->prepare("SELECT id FROM vault_items WHERE id = ?");
    $check->execute([$id]);
    $exists = $check->fetch();

    if ($exists) {
        $stmt = $db->prepare("UPDATE vault_items SET title_hint = ?, category = ?, encrypted_payload = ?, iv = ?, is_favorite = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$titleHint, $category, $encryptedPayload, $iv, $isFavorite, $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO vault_items (id, title_hint, category, encrypted_payload, iv, is_favorite) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $titleHint, $category, $encryptedPayload, $iv, $isFavorite]);
    }

    json_response([
        'success' => true,
        'message' => 'Kayıt başarıyla saklandı.',
        'id' => $id
    ]);
}

if ($action === 'delete') {
    if ($method !== 'POST') {
        json_response(['success' => false, 'message' => 'Geçersiz metod.'], 405);
    }

    $input = get_json_input();
    $id = trim($input['id'] ?? '');

    if (empty($id)) {
        json_response(['success' => false, 'message' => 'Geçersiz kayıt kimliği.'], 400);
    }

    $stmt = $db->prepare("DELETE FROM vault_items WHERE id = ?");
    $stmt->execute([$id]);

    json_response([
        'success' => true,
        'message' => 'Kayıt kasadan silindi.'
    ]);
}

if ($action === 'toggle_favorite') {
    if ($method !== 'POST') {
        json_response(['success' => false, 'message' => 'Geçersiz metod.'], 405);
    }

    $input = get_json_input();
    $id = trim($input['id'] ?? '');
    $isFavorite = !empty($input['is_favorite']) ? 1 : 0;

    if (empty($id)) {
        json_response(['success' => false, 'message' => 'Geçersiz kayıt kimliği.'], 400);
    }

    $stmt = $db->prepare("UPDATE vault_items SET is_favorite = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$isFavorite, $id]);

    json_response([
        'success' => true,
        'message' => 'Favori durumu güncellendi.'
    ]);
}

json_response(['success' => false, 'message' => 'Bilinmeyen işlem.'], 404);
