<?php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

session_start();

function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRF() . '">';
}

// LOCAL Config
$host = '127.0.0.1';
$dbname = 'magang_db';
$dbuser = 'root';
$dbpass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    http_response_code(500);
    die('Terjadi kesalahan server. Coba lagi nanti.');
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

function isAdmin() {
    return isLoggedIn();
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function uploadFoto($file) {
    $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    $maxSize = 2 * 1024 * 1024;
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Gagal upload foto.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['error' => 'Ukuran foto maksimal 2MB.'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowedMime)) {
        return ['error' => 'Format foto tidak valid.'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) {
        return ['error' => 'Ekstensi foto tidak valid.'];
    }
    
    $imgInfo = @getimagesize($file['tmp_name']);
    if ($imgInfo === false) {
        return ['error' => 'File bukan gambar yang valid.'];
    }
    
    $foto_name = 'foto_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = __DIR__ . '/../uploads/' . $foto_name;
    
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['error' => 'Gagal menyimpan foto.'];
    }
    
    return ['success' => $foto_name];
}
