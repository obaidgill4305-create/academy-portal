<?php
// Absence fines: Rs 30 per absent day, counted from 1 November 2026 onwards.
// Fine amounts are NOT stored — they are always calculated live from the
// attendance table, so correcting an attendance entry automatically fixes the fine.
// Only the "paid / unpaid" status of each student's monthly fine is stored (table `fines`).
require __DIR__ . '/db.php';
require_login();
date_default_timezone_set('Asia/Karachi');

const FINE_PER_DAY   = 30;            // Rs per absent day
const FINE_START_DATE = '2026-11-01'; // absences before this date are never fined

$method = $_SERVER['REQUEST_METHOD'];

// Make sure the payment-status table exists (so no manual migration is needed).
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS fines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(50) NOT NULL,
        month CHAR(7) NOT NULL,
        paid TINYINT(1) NOT NULL DEFAULT 0,
        paid_date DATE DEFAULT NULL,
        UNIQUE KEY unique_student_month (student_id, month),
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fines table nahi ban saka: ' . $e->getMessage()]);
    exit;
}

function valid_month($m) {
    return is_string($m) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $m);
}

if ($method === 'GET' && isset($_GET['all'])) {
    // /api/fines.php?all=1 -> every (student, month) that has absences since FINE_START_DATE
    // (used by the Defaulters page to add up unpaid fines)
    $stmt = $pdo->prepare("
        SELECT a.student_id AS studentId, DATE_FORMAT(a.att_date, '%Y-%m') AS ym,
               COUNT(a.id) AS absentDays, COALESCE(f.paid, 0) AS paid
        FROM attendance a
        LEFT JOIN fines f ON f.student_id = a.student_id AND f.month = DATE_FORMAT(a.att_date, '%Y-%m')
        WHERE a.status = 'absent' AND a.att_date >= :start
        GROUP BY a.student_id, DATE_FORMAT(a.att_date, '%Y-%m'), f.paid");
    $stmt->execute([':start' => FINE_START_DATE]);
    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $out[] = ['studentId' => $r['studentId'], 'month' => $r['ym'], 'absentDays' => (int)$r['absentDays'],
                  'fine' => (int)$r['absentDays'] * FINE_PER_DAY, 'paid' => (bool)$r['paid']];
    }
    echo json_encode($out);
    exit;
}

if ($method === 'GET') {
    // /api/fines.php?month=YYYY-MM  ->  per-student fine summary for that month
    $month = $_GET['month'] ?? date('Y-m');
    if (!valid_month($month)) { http_response_code(400); echo json_encode(['error' => 'Invalid month']); exit; }

    $from = $month . '-01';
    $to   = date('Y-m-t', strtotime($from));
    if ($from < FINE_START_DATE) $from = FINE_START_DATE; // nothing is fined before the start date

    $stmt = $pdo->prepare("
        SELECT s.id AS studentId, s.roll, s.name, s.class,
               COUNT(a.id) AS absentDays,
               GROUP_CONCAT(DAY(a.att_date) ORDER BY a.att_date SEPARATOR ',') AS days,
               COALESCE(f.paid, 0) AS paid, f.paid_date AS paidDate
        FROM attendance a
        JOIN students s ON s.id = a.student_id
        LEFT JOIN fines f ON f.student_id = s.id AND f.month = :m
        WHERE a.status = 'absent' AND a.att_date BETWEEN :from AND :to
        GROUP BY s.id, s.roll, s.name, s.class, f.paid, f.paid_date
        ORDER BY s.roll ASC");
    $stmt->execute([':m' => $month, ':from' => $from, ':to' => $to]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($rows as $r) {
        $days = (int)$r['absentDays'];
        $out[] = [
            'studentId'  => $r['studentId'],
            'roll'       => (int)$r['roll'],
            'name'       => $r['name'],
            'class'      => $r['class'],
            'absentDays' => $days,
            'days'       => $r['days'],
            'fine'       => $days * FINE_PER_DAY,
            'paid'       => (bool)$r['paid'],
            'paidDate'   => $r['paidDate'],
        ];
    }
    echo json_encode(['month' => $month, 'perDay' => FINE_PER_DAY, 'startDate' => FINE_START_DATE, 'rows' => $out]);
    exit;
}

if ($method === 'POST') {
    // Body: { studentId, month: "YYYY-MM", paid: true/false }
    $data = read_json_body();
    $sid = $data['studentId'] ?? '';
    $month = $data['month'] ?? '';
    $paid = !empty($data['paid']) ? 1 : 0;
    if (!$sid || !valid_month($month)) { http_response_code(400); echo json_encode(['error' => 'studentId / month missing']); exit; }

    $stmt = $pdo->prepare("INSERT INTO fines (student_id, month, paid, paid_date)
                            VALUES (:sid, :m, :paid, :pd)
                            ON DUPLICATE KEY UPDATE paid = VALUES(paid), paid_date = VALUES(paid_date)");
    $stmt->execute([':sid' => $sid, ':m' => $month, ':paid' => $paid, ':pd' => $paid ? date('Y-m-d') : null]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
