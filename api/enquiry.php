<?php
require __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];

// Anyone visiting the public site can submit an enquiry — no login needed.
if ($method === 'POST') {
    $data = read_json_body();
    $name = trim($data['name'] ?? '');
    if ($name === '') { http_response_code(400); echo json_encode(['error' => 'Naam likhna zaroori hai']); exit; }

    $stmt = $pdo->prepare("INSERT INTO enquiries (name, phone, interest, message) VALUES (:name, :phone, :interest, :message)");
    $stmt->execute([
        ':name'     => $name,
        ':phone'    => trim($data['phone'] ?? ''),
        ':interest' => trim($data['interest'] ?? ''),
        ':message'  => trim($data['message'] ?? ''),
    ]);
    echo json_encode(['success' => true]);
    exit;
}

// Only logged-in admins can view submitted enquiries.
if ($method === 'GET') {
    require_login();
    $stmt = $pdo->query("SELECT id, name, phone, interest, message, created_at AS createdAt FROM enquiries ORDER BY created_at DESC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($method === 'DELETE') {
    require_login();
    $id = $_GET['id'] ?? '';
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
    $stmt = $pdo->prepare("DELETE FROM enquiries WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
