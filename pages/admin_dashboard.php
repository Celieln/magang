<?php
require_once '../includes/config.php';
requireLogin();

// Handle mark notification as read
if (isset($_GET['mark_read'])) {
    $id = (int)$_GET['mark_read'];
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?")->execute([$id]);
    redirect('admin_dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        flash('danger', 'Token keamanan tidak valid!');
        redirect('admin_dashboard.php');
    }

    // Generate link
    if (isset($_POST['generate_link'])) {
        $judul = 'Form Pendaftaran Magang';
        $deskripsi = sanitize(trim($_POST['deskripsi'] ?? ''));
        $sekali_pakai = isset($_POST['sekali_pakai']) ? 1 : 0;
        $bidang_id = (int)($_POST['bidang_id'] ?? 0);
        $lpk_id = (int)($_POST['lpk_id'] ?? 0);
        $token = generateToken(32);

        $stmt = $pdo->prepare("INSERT INTO link_pendaftaran (token, bidang_id, judul, deskripsi, sekali_pakai, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$token, $bidang_id, $judul, $deskripsi, $sekali_pakai, $_SESSION['admin_id']]);

        flash('success', 'Link berhasil dibuat!');
        redirect('admin_dashboard.php');
    }

    // Hapus link
    if (isset($_POST['hapus_link'])) {
        $id = (int)$_POST['hapus_link'];
        $pdo->prepare("DELETE FROM pendaftaran WHERE link_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM link_pendaftaran WHERE id = ?")->execute([$id]);
        flash('success', 'Link berhasil dihapus!');
        redirect('admin_dashboard.php');
    }

    // Toggle aktif
    if (isset($_POST['toggle_link'])) {
        $id = (int)$_POST['toggle_link'];
        $pdo->prepare("UPDATE link_pendaftaran SET aktif = NOT aktif WHERE id = ?")->execute([$id]);
        redirect('admin_dashboard.php');
    }
}

$links = $pdo->query("SELECT l.*, (SELECT COUNT(*) FROM pendaftaran WHERE link_id = l.id) as jumlah_daftar FROM link_pendaftaran l ORDER BY l.created_at DESC")->fetchAll();
$total_link = count($links);
$total_daftar = $pdo->query("SELECT COUNT(*) FROM pendaftaran")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM pendaftaran WHERE status = 'pending'")->fetchColumn();
$diterima = $pdo->query("SELECT COUNT(*) FROM pendaftaran WHERE status = 'diterima'")->fetchColumn();
$ditolak = $pdo->query("SELECT COUNT(*) FROM pendaftaran WHERE status = 'ditolak'")->fetchColumn();
$total_lpk = $pdo->query("SELECT COUNT(*) FROM lpk WHERE aktif = 1")->fetchColumn();

// Notifications
$notif_unread = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn();
$notifications = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10")->fetchAll();

// Data chart per status
$per_status = [
    'pending' => $pending,
    'diterima' => $diterima,
    'ditolak' => $ditolak
];

// Data chart per bidang + LPK
$per_bidang = $pdo->query("SELECT b.nama_bidang, COUNT(p.id) as jumlah FROM bidang b LEFT JOIN pendaftaran p ON p.bidang_id = b.id GROUP BY b.id, b.nama_bidang ORDER BY b.nama_bidang")->fetchAll();

// 7 hari terakhir
$harian = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $harian[] = [
        'tanggal' => date('d M', strtotime($date)),
        'jumlah' => (int)$pdo->prepare("SELECT COUNT(*) FROM pendaftaran WHERE DATE(created_at) = ?")->execute([$date])
    ];
}
// Re-query harian dengan benar (execute lalu fetch, bukan inline)
$harian = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $st = $pdo->prepare("SELECT COUNT(*) FROM pendaftaran WHERE DATE(created_at) = ?");
    $st->execute([$date]);
    $harian[] = ['tanggal' => date('d M', strtotime($date)), 'jumlah' => (int)$st->fetchColumn()];
}

// Pendaftar terbaru
$recent = $pdo->query("SELECT p.*, b.nama_bidang FROM pendaftaran p JOIN bidang b ON p.bidang_id = b.id ORDER BY p.created_at DESC LIMIT 6")->fetchAll();

$bidang = $pdo->query("SELECT id, nama_bidang FROM bidang WHERE aktif = 1 ORDER BY nama_bidang")->fetchAll();
$lpk_opt = $pdo->query("SELECT id, nama_lpk FROM lpk WHERE aktif = 1 ORDER BY nama_lpk")->fetchAll();
$csrf = generateCSRF();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="../assets/style.css?v=1.4">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card { cursor: default; }
        .stat-card .stat-trend{font-size:11px;color:var(--success);font-weight:600;margin-top:4px;}
        .stat-card .stat-value{font-size:26px;font-weight:700;line-height:1;}
        .chart-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
        .chart-box{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;}
        .chart-box h3{font-size:15px;font-weight:600;margin-bottom:16px;}
        .chart-wrapper{position:relative;height:280px;}
        .recent-list{list-style:none;padding:0;}
        .recent-list li{display:flex;align-items:center;gap:14px;padding:12px 0;border-bottom:1px solid var(--border);}
        .recent-list li:last-child{border-bottom:none;}
        .recent-list .avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,rgba(99,102,241,.3),rgba(139,92,246,.2));display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
        .recent-list .recent-info{flex:1;min-width:0;}
        .recent-list .recent-info h5{font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .recent-list .recent-info p{font-size:11px;color:var(--text-muted);}
        .recent-list .recent-badge{align-self:center;}
        .form-inline{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;}
        .form-inline .form-group{flex:1;min-width:160px;margin-bottom:0;}
        @media(max-width:900px){.chart-grid{grid-template-columns:1fr;}}
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
                <a href="admin_dashboard.php" class="nav-link active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Dashboard
                    <?php if ($notif_unread > 0): ?><span style="background:var(--danger);color:#fff;font-size:10px;padding:2px 6px;border-radius:10px;margin-left:auto;font-weight:700;"><?= $notif_unread ?></span><?php endif; ?>
                </a>
                <a href="admin_data.php" class="nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Data Pendaftar
                </a>
                <a href="admin_bidang.php" class="nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    Kelola Bidang
                </a>
                <a href="admin_lpk.php" class="nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1m4 0h1M9 13h1m4 0h1M10 21v-4h4v4"/></svg>
                    Kelola LPK
                </a>
                <a href="admin_export.php" class="nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Export Data
                </a>
            </nav>
            <div class="logout"><a href="logout.php" class="nav-link" style="color:var(--danger);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a></div>
        </aside>

        <main class="main-content">
            <div class="page-header fade-in">
                <h1>Dashboard</h1>
                <p>Selamat datang, <?= sanitize($_SESSION['admin_username']) ?>! Berikut ringkasan sistem magang Anda.</p>
            </div>

            <?php $flash = getFlash(); if ($flash): ?>
                <div class="alert alert-<?= in_array($flash['type'], ['success','danger','warning']) ? $flash['type'] : 'danger' ?>"><?= sanitize($flash['message']) ?></div>
            <?php endif; ?>

            <div class="stats-grid fade-in">
                <div class="stat-card"><div class="stat-icon purple">🔗</div><div class="stat-info"><h3 class="stat-value"><?= $total_link ?></h3><p>Total Link</p></div></div>
                <div class="stat-card"><div class="stat-icon blue">📋</div><div class="stat-info"><h3 class="stat-value"><?= $total_daftar ?></h3><p>Total Pendaftar</p></div></div>
                <div class="stat-card"><div class="stat-icon yellow">⏳</div><div class="stat-info"><h3 class="stat-value"><?= $pending ?></h3><p>Menunggu</p></div></div>
                <div class="stat-card"><div class="stat-icon green">✅</div><div class="stat-info"><h3 class="stat-value"><?= $diterima ?></h3><p>Diterima</p></div></div>
                <div class="stat-card"><div class="stat-icon red">❌</div><div class="stat-info"><h3 class="stat-value"><?= $ditolak ?></h3><p>Ditolak</p></div></div>
                <div class="stat-card"><div class="stat-icon purple">🏢</div><div class="stat-info"><h3 class="stat-value"><?= $total_lpk ?></h3><p>LPK Aktif</p></div></div>
            </div>

            <?php if ($notif_unread > 0): ?>
            <div class="card fade-in" style="border-left:3px solid var(--warning);background:rgba(245,158,11,.05);">
                <div class="card-header"><h3>Notifikasi (<?= $notif_unread ?> belum dibaca)</h3></div>
                <?php foreach ($notifications as $n): ?>
                    <div style="display:flex;align-items:center;gap:12px;padding:10px 0;<?= !$n['is_read']?'border-bottom:1px solid var(--border);':'border-bottom:1px solid rgba(35,35,64,.3);' ?>">
                        <span style="font-size:18px;"><?= $n['type']==='duplicate_nik'?'⚠️':'🔔' ?></span>
                        <div style="flex:1;min-width:0;">
                            <p style="font-size:13px;<?= !$n['is_read']?'color:var(--text);font-weight:600;':'color:var(--text-muted);' ?>"><?= sanitize($n['message']) ?></p>
                            <span style="font-size:11px;color:var(--text-muted);"><?= date('d M Y H:i', strtotime($n['created_at'])) ?></span>
                        </div>
                        <?php if (!$n['is_read']): ?>
                            <a href="?mark_read=<?= $n['id'] ?>" style="font-size:11px;color:var(--primary);text-decoration:none;white-space:nowrap;">Tandai dibaca</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="chart-grid fade-in">
                <div class="chart-box">
                    <h3>📊 Distribusi Pendaftar per Bidang</h3>
                    <div class="chart-wrapper"><canvas id="chartBidang"></canvas></div>
                </div>
                <div class="chart-box">
                    <h3>📈 Pendaftar 7 Hari Terakhir</h3>
                    <div class="chart-wrapper"><canvas id="chartHarian"></canvas></div>
                </div>
            </div>

            <div class="chart-grid fade-in">
                <div class="chart-box">
                    <h3>🎯 Status Pendaftaran</h3>
                    <div class="chart-wrapper"><canvas id="chartStatus"></canvas></div>
                </div>
                <div class="chart-box">
                    <h3>🕒 Pendaftar Terbaru</h3>
                    <?php if (empty($recent)): ?>
                        <p style="text-align:center;padding:32px;color:var(--text-muted);">Belum ada pendaftar</p>
                    <?php else: ?>
                        <ul class="recent-list">
                            <?php foreach ($recent as $p): ?>
                                <?php $sc=['pending'=>'badge-pending','diterima'=>'badge-accepted','ditolak'=>'badge-rejected']; $st=['pending'=>'Menunggu','diterima'=>'Diterima','ditolak'=>'Ditolak']; ?>
                                <li>
                                    <div class="avatar">👤</div>
                                    <div class="recent-info">
                                        <h5><?= sanitize($p['nama_lengkap']) ?></h5>
                                        <p><?= sanitize($p['nama_bidang']) ?> • <?= date('d M H:i', strtotime($p['created_at'])) ?></p>
                                    </div>
                                    <span class="badge <?= $sc[$p['status']] ?> recent-badge"><?= $st[$p['status']] ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card fade-in">
                <div class="card-header"><h3>🔗 Buat Link Baru</h3></div>
                <form method="POST" class="form-inline">
                    <?= csrfField() ?>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <input type="text" name="deskripsi" class="form-control" placeholder="Deskripsi singkat..." maxlength="200">
                    </div>
                    <div class="form-group">
                        <label>Bidang</label>
                        <select name="bidang_id" class="form-control">
                            <option value="0">-- Umum / Semua --</option>
                            <?php foreach ($bidang as $b): ?>
                                <option value="<?= (int)$b['id'] ?>"><?= sanitize($b['nama_bidang']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>LPK</label>
                        <select name="lpk_id" class="form-control">
                            <option value="0">-- Pilih LPK --</option>
                            <?php foreach ($lpk_opt as $l): ?>
                                <option value="<?= (int)$l['id'] ?>"><?= sanitize($l['nama_lpk']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="display:flex;align-items:center;gap:8px;min-width:auto;">
                        <input type="checkbox" name="sekali_pakai" checked style="width:16px;height:16px;accent-color:var(--primary);">
                        <label style="margin:0;cursor:pointer;font-size:12px;white-space:nowrap;">1x pakai</label>
                    </div>
                    <input type="hidden" name="generate_link" value="1">
                    <button type="submit" class="btn btn-primary btn-sm" style="width:auto;white-space:nowrap;">Generate Link</button>
                </form>
            </div>

            <div class="card fade-in">
                <div class="card-header"><h3>Link Aktif (<?= $total_link ?>)</h3></div>
                <?php if (empty($links)): ?>
                    <p style="text-align:center;padding:32px;color:var(--text-muted);">Belum ada link</p>
                <?php else: ?>
                    <?php foreach ($links as $l): ?>
                        <div class="link-item">
                            <div class="link-item-header">
                                <div class="link-item-info">
                                    <h4><?= sanitize($l['judul']) ?></h4>
                                    <p><?= $l['jumlah_daftar'] ?> pendaftar • <?= date('d M Y', strtotime($l['created_at'])) ?></p>
                                    <div class="link-copy-box">
                                        <input type="text" value="<?= (isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST'].'/magang/pages/form_public.php?token='.$l['token'] ?>" readonly id="link_<?= $l['id'] ?>">
                                        <button onclick="copyLink(<?= $l['id'] ?>)" class="btn btn-outline btn-sm">Salin</button>
                                    </div>
                                </div>
                                <div class="link-item-actions">
                                    <span class="badge <?= $l['aktif']?'badge-accepted':'badge-rejected' ?>"><?= $l['aktif']?'Aktif':'Nonaktif' ?></span>
                                    <?php if ($l['sekali_pakai']): ?><span class="badge badge-pending">1x</span><?php endif; ?>
                                    <form method="POST" style="display:inline;"><?= csrfField() ?><input type="hidden" name="toggle_link" value="<?= $l['id'] ?>"><button type="submit" class="btn btn-warning btn-sm"><?= $l['aktif']?'🔒':'🔓' ?></button></form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus link ini?')"><?= csrfField() ?><input type="hidden" name="hapus_link" value="<?= $l['id'] ?>"><button type="submit" class="btn btn-danger btn-sm">🗑</button></form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script>
    function toggleSidebar(){document.querySelector('.sidebar').classList.toggle('active');document.querySelector('.sidebar-overlay').classList.toggle('active');}
    function copyLink(id){const i=document.getElementById('link_'+id);i.select();document.execCommand('copy');const b=event.target;b.textContent='Tersalin!';setTimeout(()=>{b.textContent='Salin';},1500);}

    // Charts
    Chart.defaults.color = '#8892b0';
    Chart.defaults.borderColor = 'rgba(35,35,64,0.8)';
    Chart.defaults.font.family = "'Inter', sans-serif";

    const chartBidang = document.getElementById('chartBidang');
    const chartHarian = document.getElementById('chartHarian');
    const chartStatus = document.getElementById('chartStatus');
    if (chartBidang) {
        new Chart(chartBidang, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map(fn($b)=>$b['nama_bidang'], $per_bidang)) ?>,
                datasets: [{
                    label: 'Pendaftar',
                    data: <?= json_encode(array_map(fn($b)=>(int)$b['jumlah'], $per_bidang)) ?>,
                    backgroundColor: 'rgba(99,102,241,0.7)',
                    borderColor: 'rgba(99,102,241,1)',
                    borderWidth: 1,
                    borderRadius: 8,
                    maxBarThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }
    if (chartHarian) {
        new Chart(chartHarian, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_map(fn($h)=>$h['tanggal'], $harian)) ?>,
                datasets: [{
                    label: 'Pendaftar',
                    data: <?= json_encode(array_map(fn($h)=>$h['jumlah'], $harian)) ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.12)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#10b981',
                    pointRadius: 4,
                    maxBarThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }
    if (chartStatus) {
        new Chart(chartStatus, {
            type: 'doughnut',
            data: {
                labels: ['Menunggu', 'Diterima', 'Ditolak'],
                datasets: [{
                    data: <?= json_encode([$pending, $diterima, $ditolak]) ?>,
                    backgroundColor: ['#f59e0b', '#10b981', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#141425'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%'
            }
        });
    }
    </script>
</body>
</html>