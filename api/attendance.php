<?php
require __DIR__ . '/db.php';
require_login();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['month'])) {
        // All records for one month -> [ { studentId, date, status }, ... ]
        $month = $_GET['month']; // expected format: YYYY-MM
        $stmt = $pdo->prepare("SELECT student_id, att_date, status FROM attendance WHERE att_date LIKE :m ORDER BY att_date ASC");
        $stmt->execute([':m' => $month . '%']);
        $out = array_map(function($row){
            return ['studentId' => $row['student_id'], 'date' => $row['att_date'], 'status' => $row['status']];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
        echo json_encode($out);
        exit;
    }
    if (isset($_GET['date'])) {
        // Records for one specific day -> { studentId: "present"/"absent", ... }
        $stmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE att_date = ?");
        $stmt->execute([$_GET['date']]);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[$row['student_id']] = $row['status'];
        }
        // Cast to object so an empty result encodes as {} not [] --
        // otherwise the frontend misreads it as an array and silently drops data.
        echo json_encode((object)$out);
        exit;
    }
    // No date given -> return everything grouped by date (used for dashboard/history if needed)
    $stmt = $pdo->query("SELECT student_id, att_date, status FROM attendance");
    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $out[$row['att_date']][$row['student_id']] = $row['status'];
    }
    echo json_encode($out);
    exit;
}

if ($method === 'POST') {
    // Body: { date: "YYYY-MM-DD", records: { studentId: "present"/"absent", ... } }
    $data = read_json_body();
    $date = $data['date'] ?? date('Y-m-d');
    $records = $data['records'] ?? [];

    $stmt = $pdo->prepare("INSERT INTO attendance (student_id, att_date, status)
                            VALUES (:sid, :date, :status)
                            ON DUPLICATE KEY UPDATE status = VALUES(status)");
    foreach ($records as $studentId => $status) {
        if ($status !== 'present' && $status !== 'absent') continue;
        $stmt->execute([':sid' => $studentId, ':date' => $date, ':status' => $status]);
    }
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
