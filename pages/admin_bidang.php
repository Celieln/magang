<?php
require_once '../includes/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        flash('danger', 'Token keamanan tidak valid!');
        redirect('admin_bidang.php');
    }
    
    if (isset($_POST['tambah_bidang'])) {
        $nama = sanitize(trim($_POST['nama_bidang'] ?? ''));
        if (!empty($nama) && mb_strlen($nama) <= 100) {
            $stmt = $pdo->prepare("INSERT INTO bidang (nama_bidang) VALUES (?)");
            $stmt->execute([$nama]);
            flash('success', 'Bidang berhasil ditambahkan!');
        } else {
            flash('danger', 'Nama bidang tidak valid!');
        }
        redirect('admin_bidang.php');
    }
    
    if (isset($_POST['hapus_bidang'])) {
        $id = (int)$_POST['hapus_bidang'];
        $cek = $pdo->prepare("SELECT COUNT(*) FROM pendaftaran WHERE bidang_id = ?");
        $cek->execute([$id]);
        if ($cek->fetchColumn() > 0) {
            flash('danger', 'Bidang masih memiliki pendaftar!');
        } else {
            $pdo->prepare("DELETE FROM bidang WHERE id = ?")->execute([$id]);
            flash('success', 'Bidang berhasil dihapus!');
        }
        redirect('admin_bidang.php');
    }
    
    if (isset($_POST['toggle_bidang'])) {
        $id = (int)$_POST['toggle_bidang'];
        $pdo->prepare("UPDATE bidang SET aktif = NOT aktif WHERE id = ?")->execute([$id]);
        redirect('admin_bidang.php');
    }
}

$bidang = $pdo->query("SELECT b.*, (SELECT COUNT(*) FROM pendaftaran WHERE bidang_id = b.id) as jumlah FROM bidang b ORDER BY b.nama_bidang")->fetchAll();
$csrf = generateCSRF();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Bidang</title>
    <link rel="stylesheet" href="../assets/style.css?v=1.3">
</head>
<body>
    <div class="dashboard">
        <div class="mobile-header"><span class="brand-mobile">Sistem Magang</span><button class="mobile-toggle" onclick="toggleSidebar()"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button></div>
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
        
        <aside class="sidebar">
            <div class="brand"><h2>Sistem Magang</h2><span>Panel Admin</span></div>
            <nav>
                <a href="admin_dashboard.php" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</a>
                <a href="admin_data.php" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Data Pendaftar</a>
                <a href="admin_bidang.php" class="nav-link active"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>Kelola Bidang</a>
                <a href="admin_export.php" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Export Data</a>
            </nav>
            <div class="logout"><a href="logout.php" class="nav-link" style="color:var(--danger);"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Logout</a></div>
        </aside>
        
        <main class="main-content">
            <div class="page-header fade-in"><h1>Kelola Bidang</h1><p>Tambah, edit, atau hapus bidang magang</p></div>
            
            <?php $flash = getFlash(); if ($flash): ?>
                <div class="alert alert-<?= in_array($flash['type'], ['success','danger','warning']) ? $flash['type'] : 'danger' ?>"><?= sanitize($flash['message']) ?></div>
            <?php endif; ?>
            
            <div class="card fade-in">
                <div class="card-header"><h3>Tambah Bidang</h3></div>
                <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;">
                    <?= csrfField() ?>
                    <div class="form-group" style="flex:1;min-width:200px;margin-bottom:0;">
                        <input type="text" name="nama_bidang" class="form-control" placeholder="Nama bidang baru..." required maxlength="100">
                    </div>
                    <input type="hidden" name="tambah_bidang" value="1">
                    <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap;">+ Tambah</button>
                </form>
            </div>
            
            <div class="card fade-in">
                <div class="card-header"><h3>Daftar Bidang (<?= count($bidang) ?>)</h3></div>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>No</th><th>Nama Bidang</th><th>Pendaftar</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php foreach ($bidang as $i => $b): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><strong><?= sanitize($b['nama_bidang']) ?></strong></td>
                                    <td><?= $b['jumlah'] ?> orang</td>
                                    <td><span class="badge <?= $b['aktif']?'badge-accepted':'badge-rejected' ?>"><?= $b['aktif']?'Aktif':'Nonaktif' ?></span></td>
                                    <td style="display:flex;gap:4px;">
                                        <form method="POST" style="display:inline;"><?= csrfField() ?><input type="hidden" name="toggle_bidang" value="<?= $b['id'] ?>"><button type="submit" class="btn btn-warning btn-sm"><?= $b['aktif']?'🔒':'🔓' ?></button></form>
                                        <?php if ($b['jumlah'] === 0): ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus?')"><?= csrfField() ?><input type="hidden" name="hapus_bidang" value="<?= $b['id'] ?>"><button type="submit" class="btn btn-danger btn-sm">🗑</button></form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('active');document.querySelector('.sidebar-overlay').classList.toggle('active');}</script>
</body>
</html>
