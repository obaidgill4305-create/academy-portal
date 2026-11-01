<?php
// Shared database connection + auth helper for all API endpoints.
//
// Local (XAMPP) setup: the defaults below work out of the box.
// Live (Render / any host) setup: set these as Environment Variables in your
// hosting dashboard instead of editing this file — DB_HOST, DB_NAME, DB_USER,
// DB_PASS, DB_PORT. Env vars always win over the defaults below.
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'academy_portal';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: ''; // default XAMPP root password is empty
$DB_PORT = getenv('DB_PORT') ?: '3306';

session_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed. Did you run schema.sql? Details: ' . $e->getMessage()]);
    exit;
}

// Auto-create a default admin login the very first time the portal runs,
// so there is always at least one account to log in with.
// Username: admin   Password: admin123   -- CHANGE THIS after first login.
try {
    $count = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (:u, :p)");
        $stmt->execute([':u' => 'admin', ':p' => password_hash('admin123', PASSWORD_DEFAULT)]);
    }
} catch (PDOException $e) {
    // admins table may not exist yet if schema.sql/migrate_v2.sql hasn't been run — ignore here,
    // auth.php will report a clear error when login is attempted.
}

// Helper: read JSON body sent from fetch()
function read_json_body() {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?: [];
}

// Helper: block access unless logged in. Call this at the top of any
// endpoint that should only be reachable by a logged-in admin.
function require_login() {
    if (empty($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not logged in']);
        exit;
    }
}
