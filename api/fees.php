<?php
require __DIR__ . '/db.php';
require_login();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = "SELECT id, student_id AS studentId, month, amount, status, fee_date AS date FROM fees WHERE 1=1";
    $params = [];
    if (!empty($_GET['studentId'])) { $sql .= " AND student_id = :sid"; $params[':sid'] = $_GET['studentId']; }
    if (!empty($_GET['status']))    { $sql .= " AND status = :status"; $params[':status'] = $_GET['status']; }
    $sql .= " ORDER BY fee_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) { $r['amount'] = (float)$r['amount']; }
    echo json_encode($rows);
    exit;
}

if ($method === 'POST') {
    $data = read_json_body();
    $id = 'fee_' . bin2hex(random_bytes(6));
    $stmt = $pdo->prepare("INSERT INTO fees (id, student_id, month, amount, status, fee_date)
                            VALUES (:id, :sid, :month, :amount, :status, :date)");
    $stmt->execute([
        ':id'     => $id,
        ':sid'    => $data['studentId'] ?? '',
        ':month'  => $data['month'] ?? '',
        ':amount' => (float)($data['amount'] ?? 0),
        ':status' => $data['status'] ?? 'due',
        ':date'   => date('Y-m-d'),
    ]);
    echo json_encode(['success' => true, 'id' => $id]);
    exit;
}

if ($method === 'PUT') {
    // Toggle paid/due: /api/fees.php?id=fee_xxx
    $id = $_GET['id'] ?? '';
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
    $stmt = $pdo->prepare("UPDATE fees SET status = IF(status='paid','due','paid') WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
    $stmt = $pdo->prepare("DELETE FROM fees WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
