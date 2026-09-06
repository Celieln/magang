<?php
require_once '../includes/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        flash('danger', 'Token keamanan tidak valid!');
        redirect('admin_data.php');
    }
    
    if (isset($_POST['assign_lpk'])) {
        $pendaftar_id = (int)$_POST['pendaftar_id'];
        $lpk_id = (int)$_POST['lpk_id'];
        $pdo->prepare("UPDATE pendaftaran SET lpk_id = ? WHERE id = ?")->execute([$lpk_id > 0 ? $lpk_id : null, $pendaftar_id]);
        flash('success', 'Peserta berhasil di-assign ke LPK!');
        redirect('admin_data.php');
    }
    
    if (isset($_POST['update_status'])) {
        $pendaftar_id = (int)$_POST['pendaftar_id'];
        $status = $_POST['status'];
        if (in_array($status, ['pending','diterima','ditolak'])) {
            $pdo->prepare("UPDATE pendaftaran SET status = ? WHERE id = ?")->execute([$status, $pendaftar_id]);
            flash('success', 'Status berhasil diupdate!');
        }
        redirect('admin_data.php');
    }
    
    if (isset($_POST['hapus_id'])) {
        $id = (int)$_POST['hapus_id'];
        $ambil = $pdo->prepare("SELECT foto FROM pendaftaran WHERE id = ?");
        $ambil->execute([$id]);
        $data = $ambil->fetch();
        if ($data && $data['foto']) {
            $path = __DIR__ . '/../uploads/' . basename($data['foto']);
            if (file_exists($path)) unlink($path);
        }
        $pdo->prepare("DELETE FROM pendaftaran WHERE id = ?")->execute([$id]);
        flash('success', 'Data berhasil dihapus!');
        redirect('admin_data.php');
    }
}

$lpk_list = $pdo->query("SELECT id, nama_lpk FROM lpk WHERE aktif = 1 ORDER BY nama_lpk")->fetchAll();

$search = $_GET['search'] ?? '';
$query = "SELECT p.*, b.nama_bidang, l.nama_lpk as nama_lpk_assigned FROM pendaftaran p JOIN bidang b ON p.bidang_id = b.id LEFT JOIN lpk l ON p.lpk_id = l.id WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (p.nama_lengkap LIKE ? OR p.nik LIKE ? OR p.no_telp LIKE ?)";
    $params = array_merge($params, ["%$search", "%$search", "%$search"]);
}

$query .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pendaftar = $stmt->fetchAll();
$csrf = generateCSRF();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pendaftar</title>
    <link rel="stylesheet" href="../assets/style.css?v=1.3">
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
                <a href="admin_data.php" class="nav-link active"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Data Pendaftar</a>
                <a href="admin_bidang.php" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>Kelola Bidang</a>
                <a href="admin_export.php" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Export Data</a>
            </nav>
            <div class="logout"><a href="logout.php" class="nav-link" style="color:var(--danger);"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Logout</a></div>
        </aside>
        
        <main class="main-content">
            <div class="page-header fade-in"><h1>Data Pendaftar</h1><p><?= count($pendaftar) ?> data ditemukan</p></div>
            
            <?php $flash = getFlash(); if ($flash): ?>
                <div class="alert alert-<?= in_array($flash['type'], ['success','danger','warning']) ? $flash['type'] : 'danger' ?>"><?= sanitize($flash['message']) ?></div>
            <?php endif; ?>
            
            <div class="card fade-in">
                <div class="toolbar">
                    <form style="display:flex;gap:10px;width:100%;flex-wrap:wrap;" method="GET">
                        <div class="search-box">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" name="search" placeholder="Cari nama, NIK, telepon..." value="<?= sanitize($search) ?>">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Cari</button>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>No</th><th>Nama</th><th>Bidang</th><th>LPK</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php if (empty($pendaftar)): ?>
                                <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);">Tidak ada data</td></tr>
                            <?php else: ?>
                                <?php foreach ($pendaftar as $i => $p): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><strong><?= sanitize($p['nama_lengkap']) ?></strong><br><span style="font-size:11px;color:var(--text-muted);"><?= sanitize($p['nik']) ?></span></td>
                                        <td><span class="badge badge-accepted" style="font-size:10px;"><?= sanitize($p['nama_bidang']) ?></span></td>
                                        <td>
                                            <?php if ($p['nama_lpk_assigned']): ?>
                                                <span class="badge badge-pending" style="font-size:10px;"><?= sanitize($p['nama_lpk_assigned']) ?></span>
                                            <?php else: ?>
                                                <span style="font-size:11px;color:var(--text-muted);">Belum di-assign</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php $sc=['pending'=>'badge-pending','diterima'=>'badge-accepted','ditolak'=>'badge-rejected']; $st=['pending'=>'Menunggu','diterima'=>'Diterima','ditolak'=>'Ditolak']; ?>
                                            <span class="badge <?= $sc[$p['status']] ?>"><?= $st[$p['status']] ?></span>
                                        </td>
                                        <td>
                                            <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                                <form method="POST" style="display:inline-flex;gap:4px;align-items:center;"><?= csrfField() ?>
                                                    <input type="hidden" name="assign_lpk" value="1">
                                                    <input type="hidden" name="pendaftar_id" value="<?= (int)$p['id'] ?>">
                                                    <select name="lpk_id" class="form-control" style="width:auto;padding:4px 8px;font-size:11px;background:var(--darker);color:var(--text);border:1px solid var(--border);border-radius:6px;">
                                                        <option value="0">-- Pilih LPK --</option>
                                                        <?php foreach ($lpk_list as $l): ?>
                                                            <option value="<?= (int)$l['id'] ?>" <?= $p['lpk_id']==$l['id']?'selected':'' ?>><?= sanitize($l['nama_lpk']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="btn btn-success btn-sm" style="padding:4px 8px;font-size:11px;">Assign</button>
                                                </form>
                                                <form method="POST" style="display:inline;"><?= csrfField() ?><input type="hidden" name="hapus_id" value="<?= (int)$p['id'] ?>"><button type="submit" class="btn btn-danger btn-sm" style="padding:4px 8px;font-size:11px;" onclick="return confirm('Hapus?')">🗑</button></form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <div class="modal-overlay" id="modalDetail">
        <div class="modal">
            <div class="modal-header"><h3>Detail Pendaftar</h3><button class="modal-close" onclick="closeModal()">&times;</button></div>
            <div id="modalContent"></div>
        </div>
    </div>
    
    <script>
    function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('active');document.querySelector('.sidebar-overlay').classList.toggle('active');}
    function showDetail(d){
        const foto=d.foto?'<div style="text-align:center;margin-bottom:14px;"><img src="../uploads/'+d.foto.replace(/[^a-z0-9._-]/gi,'')+'" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid var(--primary);"></div>':'<div style="text-align:center;margin-bottom:14px;"><div style="width:72px;height:72px;border-radius:50%;background:var(--border);display:flex;align-items:center;justify-content:center;margin:0 auto;font-size:28px;">👤</div></div>';
        const t=d.tanggal_lahir?new Date(d.tanggal_lahir).toLocaleDateString('id-ID',{day:'numeric',month:'long',year:'numeric'}):'-';
        document.getElementById('modalContent').innerHTML=foto+'<table style="width:100%;font-size:13px;"><tr><td style="padding:6px 0;color:var(--text-muted);width:100px;">NIK</td><td>'+d.nik+'</td></tr><tr><td style="padding:6px 0;color:var(--text-muted);">Nama</td><td>'+d.nama_lengkap+'</td></tr><tr><td style="padding:6px 0;color:var(--text-muted);">Tgl Lahir</td><td>'+t+'</td></tr><tr><td style="padding:6px 0;color:var(--text-muted);">Telepon</td><td>'+d.no_telp+'</td></tr><tr><td style="padding:6px 0;color:var(--text-muted);">Bidang</td><td>'+d.nama_bidang+'</td></tr><tr><td style="padding:6px 0;color:var(--text-muted);">Alamat</td><td>'+d.alamat+'</td></tr></table>';
        document.getElementById('modalDetail').classList.add('active');
    }
    function closeModal(){document.getElementById('modalDetail').classList.remove('active');}
    document.getElementById('modalDetail').addEventListener('click',function(e){if(e.target===this)closeModal();});
    </script>
</body>
</html>
