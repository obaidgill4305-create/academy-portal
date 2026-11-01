<?php
require __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// GET ?action=check -> is someone currently logged in?
if ($method === 'GET' && $action === 'check') {
    echo json_encode(['loggedIn' => !empty($_SESSION['admin_id']), 'username' => $_SESSION['admin_username'] ?? null]);
    exit;
}

// POST -> log in with { username, password }
if ($method === 'POST' && $action === 'login') {
    $data = read_json_body();
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Ghalat username ya password.']);
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    echo json_encode(['success' => true, 'username' => $admin['username']]);
    exit;
}

// POST -> log out
if ($method === 'POST' && $action === 'logout') {
    $_SESSION = [];
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

// POST -> change password (must already be logged in) { currentPassword, newPassword }
if ($method === 'POST' && $action === 'change-password') {
    require_login();
    $data = read_json_body();
    $current = $data['currentPassword'] ?? '';
    $new = $data['newPassword'] ?? '';

    if (strlen($new) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Naya password kam az kam 6 characters ka hona chahiye.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($current, $row['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Current password ghalat hai.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
    $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
