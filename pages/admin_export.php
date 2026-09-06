<?php
require_once '../includes/config.php';
requireLogin();

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $query = "SELECT p.nik, p.nama_lengkap, p.tanggal_lahir, p.no_telp, p.alamat, b.nama_bidang, p.status, p.created_at 
              FROM pendaftaran p JOIN bidang b ON p.bidang_id = b.id ORDER BY p.created_at DESC";
    $data = $pdo->query($query)->fetchAll();
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=pendaftaran_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['NIK', 'Nama', 'Tgl Lahir', 'Telp', 'Alamat', 'Bidang', 'Status', 'Tgl Daftar']);
    
    foreach ($data as $row) {
        fputcsv($output, [
            $row['nik'],
            $row['nama_lengkap'],
            $row['tanggal_lahir'] ? date('d/m/Y', strtotime($row['tanggal_lahir'])) : '-',
            $row['no_telp'],
            $row['alamat'],
            $row['nama_bidang'],
            $row['status'],
            date('d/m/Y', strtotime($row['created_at']))
        ]);
    }
    fclose($output);
    exit;
}

$total = $pdo->query("SELECT COUNT(*) FROM pendaftaran")->fetchColumn();
$per_bidang = $pdo->query("SELECT b.nama_bidang, COUNT(p.id) as jumlah FROM pendaftaran p JOIN bidang b ON p.bidang_id = b.id GROUP BY b.nama_bidang ORDER BY jumlah DESC")->fetchAll();
$per_status = $pdo->query("SELECT status, COUNT(*) as jumlah FROM pendaftaran GROUP BY status")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Data</title>
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
                <a href="admin_bidang.php" class="nav-link"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>Kelola Bidang</a>
                <a href="admin_export.php" class="nav-link active"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Export Data</a>
            </nav>
            <div class="logout"><a href="logout.php" class="nav-link" style="color:var(--danger);"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Logout</a></div>
        </aside>
        
        <main class="main-content">
            <div class="page-header fade-in"><h1>Export Data</h1><p>Download data pendaftaran</p></div>
            
            <div class="stats-grid fade-in">
                <div class="stat-card"><div class="stat-icon purple">📊</div><div class="stat-info"><h3><?= $total ?></h3><p>Total Data</p></div></div>
            </div>
            
            <div class="card fade-in">
                <div class="card-header"><h3>Download CSV</h3></div>
                <div style="text-align:center;padding:32px 20px;">
                    <div style="font-size:48px;margin-bottom:16px;">📥</div>
                    <h3 style="margin-bottom:6px;">Export ke CSV</h3>
                    <p style="color:var(--text-muted);margin-bottom:20px;font-size:13px;">Download semua data pendaftar</p>
                    <a href="?export=csv" class="btn btn-primary" style="display:inline-flex;width:auto;">Download CSV</a>
                </div>
            </div>
            
            <?php if ($per_status): ?>
            <div class="card fade-in">
                <div class="card-header"><h3>Status</h3></div>
                <div style="display:flex;gap:12px;flex-wrap:wrap;padding:16px 0;">
                    <?php $st=['pending'=>'Menunggu','diterima'=>'Diterima','ditolak'=>'Ditolak']; $sc=['pending'=>'badge-pending','diterima'=>'badge-accepted','ditolak'=>'badge-rejected']; foreach($per_status as $ps): ?>
                        <div style="flex:1;min-width:100px;text-align:center;padding:16px;background:var(--darker);border-radius:12px;">
                            <span class="badge <?= $sc[$ps['status']] ?>"><?= $st[$ps['status']] ?></span>
                            <h2 style="font-size:28px;margin-top:8px;"><?= $ps['jumlah'] ?></h2>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <script>function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('active');document.querySelector('.sidebar-overlay').classList.toggle('active');}</script>
</body>
</html>
