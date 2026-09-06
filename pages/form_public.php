<?php
require_once '../includes/config.php';

$token = $_GET['token'] ?? '';

if (empty($token) || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
    die('<div style="text-align:center;padding:100px;font-family:sans-serif;"><h1>Link Tidak Valid</h1></div>');
}

$stmt = $pdo->prepare("SELECT * FROM link_pendaftaran WHERE token = ?");
$stmt->execute([$token]);
$link = $stmt->fetch();

if (!$link || !$link['aktif']) {
    die('<div style="text-align:center;padding:100px;font-family:sans-serif;"><h1>Link Tidak Ditemukan</h1></div>');
}

if ($link['sekali_pakai']) {
    $cek = $pdo->prepare("SELECT COUNT(*) FROM pendaftaran WHERE link_id = ?");
    $cek->execute([$link['id']]);
    if ($cek->fetchColumn() > 0) {
        die('<div style="text-align:center;padding:100px;font-family:Inter,sans-serif;background:#0a0a14;color:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center;">
            <div style="background:#141425;border:1px solid #232340;border-radius:24px;padding:48px;max-width:420px;text-align:center;">
                <div style="font-size:64px;margin-bottom:16px;">🔒</div>
                <h1 style="font-size:24px;margin-bottom:8px;">Link Sudah Digunakan</h1>
                <p style="color:#8892b0;">Hubungi admin untuk mendapatkan link baru.</p>
            </div>
        </div>');
    }
}

$bidang = $pdo->query("SELECT * FROM bidang WHERE aktif = 1 ORDER BY nama_bidang")->fetchAll();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        $nik = preg_replace('/[^0-9]/', '', $_POST['nik'] ?? '');
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $telp = preg_replace('/[^0-9]/', '', $_POST['no_telp'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $bidang_id = (int)($_POST['bidang_id'] ?? 0);
        $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
        
        // Validate tanggal_lahir
        if (!empty($tanggal_lahir)) {
            $dateParts = explode('-', $tanggal_lahir);
            if (count($dateParts) !== 3 || !checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0])) {
                $tanggal_lahir = null;
            }
        } else {
            $tanggal_lahir = null;
        }
        
        // Validate bidang exists
        $cek_bidang = $pdo->prepare("SELECT id FROM bidang WHERE id = ? AND aktif = 1");
        $cek_bidang->execute([$bidang_id]);
        if (!$cek_bidang->fetch()) {
            $bidang_id = 0;
        }
        
        // Handle upload foto
        $foto_name = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFoto($_FILES['foto']);
            if (isset($upload['error'])) {
                $error = $upload['error'];
            } else {
                $foto_name = $upload['success'];
            }
        }
        
        if (empty($error)) {
            if (empty($nik) || empty($nama) || empty($telp) || empty($alamat) || $bidang_id === 0) {
                $error = 'Semua field wajib diisi!';
            } elseif (!preg_match('/^[0-9]{16}$/', $nik)) {
                $error = 'NIK harus 16 digit angka!';
            } elseif (!preg_match('/^[0-9]{10,13}$/', $telp)) {
                $error = 'Nomor telepon harus 10-13 digit angka!';
            } elseif (mb_strlen($nama) < 3 || mb_strlen($nama) > 100) {
                $error = 'Nama harus 3-100 karakter!';
            } elseif (mb_strlen($alamat) < 5 || mb_strlen($alamat) > 500) {
                $error = 'Alamat harus 5-500 karakter!';
            } else {
                $cek_nik = $pdo->prepare("SELECT COUNT(*) FROM pendaftaran WHERE nik = ?");
                $cek_nik->execute([$nik]);
                $is_duplicate = $cek_nik->fetchColumn() > 0;
                
                $stmt = $pdo->prepare("INSERT INTO pendaftaran (link_id, token, nik, nama_lengkap, no_telp, alamat, foto, tanggal_lahir, bidang_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$link['id'], $token, $nik, $nama, $telp, $alamat, $foto_name, $tanggal_lahir, $bidang_id]);
                
                $new_id = $pdo->lastInsertId();
                
                if ($is_duplicate) {
                    $nama_bidang = $pdo->prepare("SELECT nama_bidang FROM bidang WHERE id = ?");
                    $nama_bidang->execute([$bidang_id]);
                    $nb = $nama_bidang->fetchColumn();
                    
                    $notif_msg = "NIK $nik ($nama) terdaftar lagi di bidang $nb. NIK ini sudah pernah dipakai sebelumnya!";
                    $pdo->prepare("INSERT INTO notifications (type, message, pendaftar_id) VALUES ('duplicate_nik', ?, ?)")->execute([$notif_msg, $new_id]);
                }
                
                $success = 'Pendaftaran berhasil!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pendaftaran Magang</title>
    <link rel="stylesheet" href="../assets/style.css?v=1.3">
    <style>
        .foto-upload{display:flex;flex-direction:column;align-items:center;gap:10px}
        .foto-preview{width:96px;height:96px;border-radius:50%;background:var(--darker);border:2px dashed var(--border);display:flex;align-items:center;justify-content:center;overflow:hidden;cursor:pointer;transition:all .3s}
        .foto-preview:hover{border-color:var(--primary)}
        .foto-preview img{width:100%;height:100%;object-fit:cover}
        .foto-preview svg{width:28px;height:28px;color:var(--text-muted)}
        .dob-badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;color:var(--success);margin-top:4px}
        .dob-badge.hidden{display:none}
    </style>
</head>
<body>
    <div class="login-container" style="flex-direction:column;gap:20px;">
        <div class="form-card fade-in" style="max-width:540px;width:100%;">
            <div class="form-header">
                <div style="width:60px;height:60px;background:linear-gradient(135deg,rgba(99,102,241,.2),rgba(139,92,246,.1));border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:26px;">📋</div>
                <h1 style="font-size:20px;margin-bottom:6px;background:linear-gradient(135deg,#a78bfa,#6366f1);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Form Pendaftaran Magang</h1>
                <?php if ($link['deskripsi']): ?>
                    <p style="font-size:12px;"><?= sanitize($link['deskripsi']) ?></p>
                <?php endif; ?>
                <?php if ($link['sekali_pakai']): ?>
                    <p style="margin-top:6px;font-size:11px;color:#f59e0b;">⚠ Form ini hanya bisa diisi 1 kali</p>
                <?php endif; ?>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= sanitize($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div style="text-align:center;padding:32px 20px;">
                    <div style="width:72px;height:72px;background:rgba(16,185,129,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:36px;">✅</div>
                    <h2 style="font-size:20px;margin-bottom:6px;color:#10b981;font-weight:700;">Berhasil!</h2>
                    <p style="color:#8892b0;font-size:13px;">Data Anda telah diterima.</p>
                </div>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    
                    <div class="form-group">
                        <label>Foto Profil</label>
                        <div class="foto-upload">
                            <label for="foto_input" class="foto-preview" id="foto_preview">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" id="foto_icon"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                <img id="foto_img" style="display:none;" alt="">
                            </label>
                            <input type="file" name="foto" id="foto_input" accept="image/jpeg,image/png,image/webp" style="display:none;">
                            <p style="font-size:11px;color:var(--text-muted);">Klik untuk upload (opsional, max 2MB)</p>
                        </div>
                    </div>
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div class="form-group">
                            <label>NIK (16 digit)</label>
                            <input type="text" name="nik" id="nik_input" class="form-control" placeholder="Masukkan NIK" required maxlength="16" pattern="[0-9]{16}" inputmode="numeric">
                        </div>
                        <div class="form-group">
                            <label>Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" id="tanggal_lahir" class="form-control" readonly style="cursor:not-allowed;opacity:.8;">
                            <div class="dob-badge hidden" id="dob_badge">✓ Auto dari NIK</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" placeholder="Masukkan nama lengkap" required maxlength="100">
                    </div>
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div class="form-group">
                            <label>No. Telepon</label>
                            <input type="tel" name="no_telp" class="form-control" placeholder="081234567890" required maxlength="13" pattern="[0-9]{10,13}" inputmode="numeric">
                        </div>
                        <div class="form-group">
                            <label>Bidang Magang</label>
                            <select name="bidang_id" class="form-control" required>
                                <option value="">-- Pilih --</option>
                                <?php foreach ($bidang as $b): ?>
                                    <option value="<?= (int)$b['id'] ?>"><?= sanitize($b['nama_bidang']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" placeholder="Masukkan alamat" required rows="3" maxlength="500"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="margin-top:4px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        Kirim Pendaftaran
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <p style="color:#4a4a6a;font-size:11px;">Sistem Pendaftaran Magang</p>
    </div>
    
    <script>
    document.getElementById('foto_input')?.addEventListener('change',function(e){
        const f=e.target.files[0];
        if(f){const r=new FileReader();r.onload=function(ev){
            document.getElementById('foto_img').src=ev.target.result;
            document.getElementById('foto_img').style.display='block';
            document.getElementById('foto_icon').style.display='none';
        };r.readAsDataURL(f);}
    });
    document.getElementById('nik_input')?.addEventListener('input',function(e){
        const n=e.target.value.replace(/[^0-9]/g,'');
        e.target.value=n;
        const d=document.getElementById('tanggal_lahir'),b=document.getElementById('dob_badge');
        if(n.length===16){
            const dd=n.substring(6,8),mm=n.substring(8,10),yy=n.substring(10,12);
            let yr=parseInt(yy),cur=new Date().getFullYear()%100;
            yr+=(yr>cur)?1900:2000;
            const dt=new Date(yr,parseInt(mm)-1,parseInt(dd));
            if(dt.getFullYear()===yr&&(dt.getMonth()+1)===parseInt(mm)&&dt.getDate()===parseInt(dd)){
                d.value=yr+'-'+mm.padStart(2,'0')+'-'+dd.padStart(2,'0');b.classList.remove('hidden');
            }else{d.value='';b.classList.add('hidden');}
        }else{d.value='';b.classList.add('hidden');}
    });
    </script>
</body>
</html>
