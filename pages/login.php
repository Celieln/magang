<?php
require_once '../includes/config.php';

if (isLoggedIn()) {
    redirect('admin_dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid!';
    } else {
        $username = sanitize(trim($_POST['username'] ?? ''));
        $password = $_POST['password'] ?? '';
        
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            header('Location: admin_dashboard.php');
            exit();
        } else {
            $error = 'Username atau password salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link rel="stylesheet" href="../assets/style.css?v=1.3">
</head>
<body>
    <div class="login-container">
        <div class="login-box fade-in">
            <div class="logo">
                <h1>Admin Panel</h1>
                <p>Sistem Magang</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= sanitize($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <?= csrfField() ?>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" required maxlength="50">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                </div>
                <button type="submit" class="btn btn-primary">Masuk</button>
            </form>
            <p style="text-align:center;margin-top:16px;"><a href="../index.php" style="color:var(--text-muted);text-decoration:none;font-size:12px;">← Kembali</a></p>
        </div>
    </div>
</body>
</html>
