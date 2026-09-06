<?php
require_once '../includes/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        flash('danger', 'Token keamanan tidak valid!');
        redirect('admin_lpk.php');
    }

    if (isset($_POST['tambah_lpk'])) {
        $nama = sanitize(trim($_POST['nama_lpk'] ?? ''));
        $lokasi = sanitize(trim($_POST['lokasi'] ?? ''));
        if (!empty($nama) && mb_strlen($nama) <= 150) {
            $stmt = $pdo->prepare("INSERT INTO lpk (nama_lpk, lokasi) VALUES (?, ?)");
            $stmt->execute([$nama, $lokasi]);
            flash('success', 'LPK berhasil ditambahkan!');
        } else {
            flash('danger', 'Nama LPK tidak valid!');
        }
        redirect('admin_lpk.php');
    }

    if (isset($_POST['edit_lpk'])) {
        $id = (int)$_POST['edit_lpk'];
        $nama = sanitize(trim($_POST['nama_lpk'] ?? ''));
        $lokasi = sanitize(trim($_POST['lokasi'] ?? ''));
        if (!empty($nama) && mb_strlen($nama) <= 150) {
            $stmt = $pdo->prepare("UPDATE lpk SET nama_lpk = ?, lokasi = ? WHERE id = ?");
            $stmt->execute([$nama, $lokasi, $id]);
            flash('success', 'LPK berhasil diperbarui!');
        } else {
            flash('danger', 'Nama LPK tidak valid!');
        }
        redirect('admin_lpk.php');
    }

    if (isset($_POST['hapus_lpk'])) {
        $id = (int)$_POST['hapus_lpk'];
        $pdo->prepare("DELETE FROM lpk WHERE id = ?")->execute([$id]);
        flash('success', 'LPK berhasil dihapus!');
        redirect('admin_lpk.php');
    }

    if (isset($_POST['toggle_lpk'])) {
        $id = (int)$_POST['toggle_lpk'];
        $pdo->prepare("UPDATE lpk SET aktif = NOT aktif WHERE id = ?")->execute([$id]);
        redirect('admin_lpk.php');
    }
}

$lpk = $pdo->query("SELECT * FROM lpk ORDER BY nama_lpk")->fetchAll();
$total_aktif = $pdo->query("SELECT COUNT(*) FROM lpk WHERE aktif = 1")->fetchColumn();
$csrf = generateCSRF();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola LPK</title>
    <link rel="stylesheet" href="../assets/style.css?v=1.4">
    <style>
        .lpk-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;}
        .lpk-admin-card{background:var(--darker);border:1px solid var(--border);border-radius:var(--radius);padding:20px;display:flex;flex-direction:column;gap:12px;transition:all .25s ease;}
        .lpk-admin-card:hover{border-color:var(--primary);box-shadow:0 8px 32px rgba(99,102,241,0.12);}
        .lpk-admin-head{display:flex;align-items:center;gap:12px;}
        .lpk-admin-logo{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,rgba(99,102,241,.2),rgba(139,92,246,.1));display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
        .lpk-admin-head h4{font-size:14px;font-weight:600;line-height:1.3;}
        .lpk-admin-head p{font-size:11px;color:var(--text-muted);display:flex;align-items:center;gap:4px;}
        .lpk-admin-actions{display:flex;gap:6px;flex-wrap:wrap;border-top:1px solid var(--border);padding-top:12px;}
        .form-inline{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;}
        .form-inline .form-group{flex:1;min-width:180px;margin-bottom:0;}
        .edit-row{display:none;margin-top:12px;}
        .edit-row.open{display:block;}
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="mobile-header">
            <span class="brand-mobile">Sistem Magang</span>
            <button class="mobile-toggle" onclick="toggleSidebar()"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
        </div>
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <aside class="sidebar">
            <div class="brand"><h2>Sistem Magang</h2><span>Panel Admin</span></div>
            <nav>
                <a href="admin_dashboard.php" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</a>
                <a href="admin_data.php" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Data Pendaftar</a>
                <a href="admin_bidang.php" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>Kelola Bidang</a>
                <a href="admin_lpk.php" class="nav-link active"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1m4 0h1M9 13h1m4 0h1M10 21v-4h4v4"/></svg>Kelola LPK</a>
                <a href="admin_export.php" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Export Data</a>
            </nav>
            <div class="logout"><a href="logout.php" class="nav-link" style="color:var(--danger);"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Logout</a></div>
        </aside>

        <main class="main-content">
            <div class="page-header fade-in">
                <h1>Kelola LPK</h1>
                <p>Kelola daftar Lembaga Pelatihan Kerja mitra</p>
            </div>

            <?php $flash = getFlash(); if ($flash): ?>
                <div class="alert alert-<?= in_array($flash['type'], ['success','danger','warning']) ? $flash['type'] : 'danger' ?>"><?= sanitize($flash['message']) ?></div>
            <?php endif; ?>

            <div class="stats-grid fade-in">
                <div class="stat-card"><div class="stat-icon purple">🏢</div><div class="stat-info"><h3><?= count($lpk) ?></h3><p>Total LPK</p></div></div>
                <div class="stat-card"><div class="stat-icon green">✅</div><div class="stat-info"><h3><?= $total_aktif ?></h3><p>LPK Aktif</p></div></div>
                <div class="stat-card"><div class="stat-icon yellow">⭕</div><div class="stat-info"><h3><?= count($lpk) - $total_aktif ?></h3><p>LPK Nonaktif</p></div></div>
            </div>

            <div class="card fade-in">
                <div class="card-header"><h3>+ Tambah LPK Baru</h3></div>
                <form method="POST" class="form-inline">
                    <?= csrfField() ?>
                    <div class="form-group">
                        <label>Nama LPK</label>
                        <input type="text" name="nama_lpk" class="form-control" placeholder="Nama LPK..." required maxlength="150">
                    </div>
                    <div class="form-group">
                        <label>Lokasi / Kota</label>
                        <input type="text" name="lokasi" class="form-control" placeholder="Mis. Manado" maxlength="150">
                    </div>
                    <input type="hidden" name="tambah_lpk" value="1">
                    <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap;width:auto;">+ Tambah</button>
                </form>
            </div>

            <div class="card fade-in">
                <div class="card-header"><h3>Daftar LPK (<?= count($lpk) ?>)</h3><span class="badge badge-accepted">Ditampilkan di halaman utama</span></div>
                <?php if (empty($lpk)): ?>
                    <p style="text-align:center;padding:32px;color:var(--text-muted);">Belum ada LPK</p>
                <?php else: ?>
                <div class="lpk-grid">
                    <?php foreach ($lpk as $l): ?>
                        <div class="lpk-admin-card">
                            <div class="lpk-admin-head">
                                <div class="lpk-admin-logo">🏢</div>
                                <div style="min-width:0;">
                                    <h4><?= sanitize($l['nama_lpk']) ?></h4>
                                    <p>📍 <?= sanitize($l['lokasi'] ?: '-') ?></p>
                                </div>
                            </div>
                            <div style="display:flex;gap:6px;align-items:center;">
                                <span class="badge <?= $l['aktif']?'badge-accepted':'badge-rejected' ?>"><?= $l['aktif']?'Aktif':'Nonaktif' ?></span>
                                <?php if (!$l['aktif']): ?><span style="font-size:11px;color:var(--text-muted);">tidak tampil di publik</span><?php endif; ?>
                            </div>
                            <div class="lpk-admin-actions">
                                <button onclick="toggleEdit(<?= $l['id'] ?>)" class="btn btn-outline btn-sm">✏️ Edit</button>
                                <form method="POST" style="display:inline;"><?= csrfField() ?><input type="hidden" name="toggle_lpk" value="<?= $l['id'] ?>"><button type="submit" class="btn btn-warning btn-sm"><?= $l['aktif']?'🔒 Nonaktifkan':'🔓 Aktifkan' ?></button></form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus LPK ini?')"><?= csrfField() ?><input type="hidden" name="hapus_lpk" value="<?= $l['id'] ?>"><button type="submit" class="btn btn-danger btn-sm">🗑</button></form>
                            </div>
                            <div class="edit-row" id="editrow_<?= $l['id'] ?>">
                                <form method="POST" class="form-inline">
                                    <?= csrfField() ?>
                                    <div class="form-group">
                                        <label style="font-size:10px;">Nama</label>
                                        <input type="text" name="nama_lpk" class="form-control" value="<?= sanitize($l['nama_lpk']) ?>" required maxlength="150">
                                    </div>
                                    <div class="form-group">
                                        <label style="font-size:10px;">Lokasi</label>
                                        <input type="text" name="lokasi" class="form-control" value="<?= sanitize($l['lokasi']) ?>" maxlength="150">
                                    </div>
                                    <input type="hidden" name="edit_lpk" value="<?= $l['id'] ?>">
                                    <button type="submit" class="btn btn-success btn-sm" style="white-space:nowrap;width:auto;">Simpan</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script>
    function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('active');document.querySelector('.sidebar-overlay').classList.toggle('active');}
    function toggleEdit(id){const el=document.getElementById('editrow_'+id);if(el)el.classList.toggle('open');}
    </script>
</body>
</html>
