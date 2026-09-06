<?php
require_once 'includes/config.php';
$total_daftar = $pdo->query("SELECT COUNT(*) FROM pendaftaran")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM pendaftaran WHERE status='pending'")->fetchColumn();
$diterima = $pdo->query("SELECT COUNT(*) FROM pendaftaran WHERE status='diterima'")->fetchColumn();
$ditolak = $pdo->query("SELECT COUNT(*) FROM pendaftaran WHERE status='ditolak'")->fetchColumn();
$per_bidang = $pdo->query("SELECT b.nama_bidang, COUNT(p.id) as jumlah FROM bidang b LEFT JOIN pendaftaran p ON p.bidang_id=b.id WHERE b.aktif=1 GROUP BY b.id,b.nama_bidang ORDER BY jumlah DESC")->fetchAll();
$terassign = $pdo->query("SELECT COUNT(*) FROM pendaftaran WHERE lpk_id IS NOT NULL")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Sistem Pendaftaran Magang</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',sans-serif;background:#0a0a14;color:#fff;overflow-x:hidden;}
#bg3d{position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;}
.content{position:relative;z-index:1;}

.hero{min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:40px 24px;}
.hero-inner{max-width:700px;}
.hero-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 16px;background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.3);border-radius:50px;font-size:11px;color:#a78bfa;margin-bottom:24px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;}
.hero-badge .dot{width:6px;height:6px;background:#10b981;border-radius:50%;animation:pulse 2s infinite;}
.hero h1{font-size:clamp(36px,7vw,64px);font-weight:800;line-height:1.05;margin-bottom:20px;letter-spacing:-1.5px;}
.hero h1 .g{background:linear-gradient(135deg,#c4b5fd,#818cf8,#6366f1);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.hero p{color:#c0c8e0;font-size:clamp(14px,2vw,17px);max-width:520px;margin:0 auto 36px;line-height:1.7;}
.hero-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;}
.stats-row{display:flex;gap:40px;justify-content:center;margin-top:56px;flex-wrap:wrap;}
.stat{text-align:center;}
.stat h3{font-size:32px;font-weight:700;background:linear-gradient(135deg,#a78bfa,#6366f1);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.stat p{font-size:11px;color:#9098b0;margin-top:4px;text-transform:uppercase;letter-spacing:1px;}

.section{padding:80px 24px;max-width:960px;margin:0 auto;}
.sec-label{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:600;color:#818cf8;text-transform:uppercase;letter-spacing:2px;margin-bottom:12px;}
.sec-title{font-size:clamp(26px,5vw,40px);font-weight:700;margin-bottom:8px;letter-spacing:-.5px;}
.sec-desc{color:#b0b8d0;font-size:14px;margin-bottom:40px;line-height:1.7;}
.info-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:48px;}
.info-card{background:rgba(15,15,30,.45);border:1px solid rgba(50,50,80,.5);border-radius:20px;padding:28px 24px;transition:all .4s ease;}
.info-card:hover{transform:translateY(-6px);border-color:rgba(99,102,241,.4);box-shadow:0 20px 60px -20px rgba(99,102,241,.25);}
.info-ic{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,rgba(99,102,241,.2),rgba(139,92,246,.08));display:flex;align-items:center;justify-content:center;margin-bottom:14px;font-size:20px;border:1px solid rgba(99,102,241,.25);}
.info-card h3{font-size:16px;font-weight:600;margin-bottom:8px;}
.info-card p{font-size:13px;color:#c0c8e0;line-height:1.6;}

.bidang-wrap{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-bottom:48px;}
.chip{padding:10px 20px;background:rgba(15,15,30,.4);border:1px solid rgba(50,50,80,.5);border-radius:50px;font-size:13px;color:#e0e4f0;transition:all .3s ease;}
.chip:hover{border-color:#6366f1;color:#c4b5fd;transform:translateY(-3px);box-shadow:0 8px 24px rgba(99,102,241,.15);}






.lpk-loc{font-size:12px;color:#5a6080;}

.contact-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:32px;}
.contact-card{background:rgba(15,15,30,.45);border:1px solid rgba(50,50,80,.5);border-radius:16px;padding:24px;text-align:center;transition:all .3s ease;}
.contact-card:hover{transform:translateY(-4px);border-color:rgba(99,102,241,.4);}
.contact-ic{width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,rgba(99,102,241,.2),rgba(139,92,246,.08));display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:22px;border:1px solid rgba(99,102,241,.3);}
.contact-card h3{font-size:15px;font-weight:600;margin-bottom:6px;}
.contact-card p{font-size:13px;color:#c0c8e0;line-height:1.6;}
.contact-card a{color:#a78bfa;text-decoration:none;font-weight:600;}
.contact-card a:hover{color:#818cf8;}

.contact-note{max-width:560px;margin:0 auto;background:rgba(15,15,30,.5);border:1px solid rgba(50,50,80,.5);border-radius:24px;padding:36px 28px;text-align:center;}
.contact-note h3{font-size:22px;font-weight:700;margin-bottom:8px;}
.contact-note p{color:#c0c8e0;font-size:14px;margin-bottom:20px;line-height:1.7;}

.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px 28px;border-radius:12px;font-size:14px;font-weight:700;text-decoration:none;cursor:pointer;border:1px solid transparent;transition:all .3s ease;font-family:inherit;}
.btn-primary{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;box-shadow:0 8px 30px -8px rgba(99,102,241,.5);}
.btn-primary:hover{transform:translateY(-3px);box-shadow:0 16px 40px -8px rgba(99,102,241,.6);}
.btn-ghost{background:rgba(15,15,30,.5);border-color:rgba(50,50,80,.6);color:#c0c8e0;}
.btn-ghost:hover{border-color:#6366f1;color:#c4b5fd;transform:translateY(-3px);}
.btn-sm{padding:12px 22px;font-size:13px;}
.ft{text-align:center;padding:24px;border-top:1px solid rgba(50,50,80,.4);color:#8090b0;font-size:11px;}
#langBtn{position:fixed;top:20px;right:20px;z-index:100;padding:10px 18px;background:rgba(15,15,30,.7);border:1px solid rgba(99,102,241,.4);border-radius:12px;color:#a78bfa;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .3s ease;backdrop-filter:blur(8px);}
#langBtn:hover{background:rgba(99,102,241,.2);border-color:#818cf8;transform:scale(1.05);}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(1.8)}}
@media(max-width:768px){.info-grid{grid-template-columns:1fr;}.stats-row{gap:20px;}.section{padding:50px 16px;}.hero h1{font-size:28px;}.hero p{font-size:14px;}.sec-title{font-size:24px;}.stat h3{font-size:24px;}#langBtn{top:12px;right:12px;padding:8px 14px;font-size:12px;}}
@media(max-width:480px){.info-grid{grid-template-columns:1fr;gap:12px;}.stats-row{flex-direction:column;gap:16px;}.hero{padding:30px 16px;}.hero-btns{flex-direction:column;align-items:center;}.btn{width:100%;justify-content:center;}}
</style>
</head>
<body>
<button id="langBtn">JP</button>
<canvas id="bg3d"></canvas>
<div class="content">

<section class="hero">
<div class="hero-inner">
<div class="hero-badge"><span class="dot"></span> <span id="t-badge">Sistem Online</span></div>
<h1 id="t-h1"><span class="g">Pendaftaran Magang</span><br>Indonesia &mdash; Jepang</h1>
<p id="t-desc">Program magang terpadu dengan LPK mitra untuk mengembangkan skill dan pengalaman kerja Anda. Daftar online dan tunggu verifikasi.</p>
<div class="hero-btns">
<a href="#info" class="btn btn-primary" style="width:auto;" id="t-btn1">Lihat Informasi &#8594;</a>
<a href="#kontak" class="btn btn-ghost btn-sm" style="width:auto;" id="t-btn2">Hubungi Admin</a>
</div>
<div class="stats-row">
<div class="stat"><h3><?= $total_daftar ?></h3><p id="t-s1">Peserta Terdaftar</p></div>
<div class="stat"><h3><?= $diterima ?></h3><p id="t-s2">Diterima</p></div>
<div class="stat"><h3><?= $pending ?></h3><p id="t-s3">Menunggu</p></div>
</div>
</div>
</section>

<section class="section" id="info">
<div style="text-align:center;margin-bottom:36px;">
<div class="sec-label" id="t-l2label">Dashboard</div>
<h2 class="sec-title" id="t-l2title">Statistik Peserta Magang</h2>
<p class="sec-desc" id="t-l2desc">Ringkasan jumlah pendaftar berdasarkan bidang magang yang dipilih</p>
</div>
<div class="info-grid">
<div class="info-card"><div class="info-ic">&#128101;</div><h3>Total Peserta</h3><p><?= $total_daftar ?> orang terdaftar</p></div>
<div class="info-card"><div class="info-ic">&#9989;</div><h3>Diterima</h3><p><?= $diterima ?> orang diterima</p></div>
<div class="info-card"><div class="info-ic">&#127970;</div><h3>Ter-assign LPK</h3><p><?= $terassign ?> orang sudah ditentukan</p></div>
</div>
<div class="info-grid" style="margin-top:20px;">
<?php foreach ($per_bidang as $pb): ?>
<div class="info-card"><div class="info-ic">&#128200;</div><h3><?= sanitize($pb['nama_bidang']) ?></h3><p><?= (int)$pb['jumlah'] ?> peserta</p></div>
<?php endforeach; ?>
</div>
</section>

<footer class="ft" id="t-footer">&copy; <?= date('Y') ?> Sistem Pendaftaran Magang</footer>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script>
(function(){
var W=window,c=document.getElementById('bg3d');
if(!c)return;
var r=new THREE.WebGLRenderer({canvas:c,antialias:true,alpha:true});
r.setSize(W.innerWidth,W.innerHeight);
r.setPixelRatio(Math.min(W.devicePixelRatio,2));
var sc=new THREE.Scene();
var cam=new THREE.PerspectiveCamera(55,W.innerWidth/W.innerHeight,.1,1000);
cam.position.z=30;
var wd=new THREE.Group();
sc.add(wd);

function mkP(samples,sz,op){
    var n=samples.length,pos=new Float32Array(n*3),col=new Float32Array(n*3);
    for(var i=0;i<n;i++){var s=samples[i];pos[i*3]=s[0];pos[i*3+1]=s[1];pos[i*3+2]=s[2];col[i*3]=s[3];col[i*3+1]=s[4];col[i*3+2]=s[5];}
    var g=new THREE.BufferGeometry();
    g.setAttribute('position',new THREE.BufferAttribute(pos,3));
    g.setAttribute('color',new THREE.BufferAttribute(col,3));
    return new THREE.Points(g,new THREE.PointsMaterial({size:sz,vertexColors:true,transparent:true,opacity:op,blending:THREE.AdditiveBlending,depthWrite:false,sizeAttenuation:true}));
}
function wp(o){var v=new THREE.Vector3();o.getWorldPosition(v);return v;}

// Indonesia flag
(function(){
    var s=[],R=[.95,.16,.18],Wh=[.86,.9,1];
    for(var i=0;i<105;i++)for(var j=0;j<68;j++){
        var x=(i/104-.5)*13.5,y=(.5-j/67)*9;
        var c=y>=0?R:Wh;s.push([x,y,0,c[0],c[1],c[2]]);
    }
    var P=mkP(s,.16,.85);P.position.set(-11,4.5,-2);
    P.userData.base=new Float32Array(P.geometry.attributes.position.array);
    P.userData.fl=true;P.userData.sd=[];P.userData.sa=new Float32Array(P.geometry.attributes.position.count);
    var n=P.geometry.attributes.position.count;var sd=new Float32Array(n*3);
    for(var i=0;i<n;i++){var rx=(Math.random()-.5)*2,ry=(Math.random()-.5)*2,rz=(Math.random()-.5)*2;var l=Math.sqrt(rx*rx+ry*ry+rz*rz)||1;sd[i*3]=rx/l;sd[i*3+1]=ry/l;sd[i*3+2]=rz/l;}
    P.userData.sd=sd;
    wd.add(P);wd.userData.fI=P;
})();

// Japan flag
(function(){
    var s=[],Wh=[.80,.84,.97],R=[.88,.05,.21],rad=9*.30;
    for(var i=0;i<105;i++)for(var j=0;j<68;j++){
        var x=(i/104-.5)*13.5,y=(.5-j/67)*9;
        var c=Math.hypot(x,y)<=rad?R:Wh;s.push([x,y,0,c[0],c[1],c[2]]);
    }
    var P=mkP(s,.16,.85);P.position.set(11,1.5,-2);
    P.userData.base=new Float32Array(P.geometry.attributes.position.array);
    P.userData.fl=true;
    var n=P.geometry.attributes.position.count;var sd=new Float32Array(n*3);
    for(var i=0;i<n;i++){var rx=(Math.random()-.5)*2,ry=(Math.random()-.5)*2,rz=(Math.random()-.5)*2;var l=Math.sqrt(rx*rx+ry*ry+rz*rz)||1;sd[i*3]=rx/l;sd[i*3+1]=ry/l;sd[i*3+2]=rz/l;}
    P.userData.sd=sd;P.userData.sa=new Float32Array(n);
    wd.add(P);wd.userData.fJ=P;
})();

// Students (white shirt, black pants, red accent)
var SK=[.96,.78,.60],HR=[.09,.07,.10],SH=[.95,.96,1],PN=[.11,.12,.16],SC=[.06,.06,.08],RD=[.92,.11,.18];
function ptC(x,y){
    if(Math.hypot(x+.28,y+.50)<=.15)return SC;if(Math.hypot(x-.28,y+.50)<=.15)return SC;
    if(Math.hypot(x+.22,y+.10)<=.20)return PN;if(Math.hypot(x-.22,y+.10)<=.20)return PN;
    if(Math.abs(x)<=.34&&y>=.20&&y<=.95)return PN;
    var sw=.30+.22*((y-.60)/.90);
    if(y>=.60&&y<=1.55&&Math.abs(x)<=Math.min(sw,.52)){if(y>=1.10&&y<=1.22&&Math.abs(x)<=.30)return RD;return SH;}
    if(Math.abs(x)<=.14&&y>=1.55&&y<=1.78)return SK;
    if(Math.hypot(x,y-2.05)<=.42){if(y>2.22)return HR;if(Math.abs(x)<.36&&y>2.08)return HR;return SK;}
    return null;
}
function genS(){var s=[];for(var y=-.75;y<=2.70;y+=.032)for(var x=-1.25;x<=1.25;x+=.032){var c=ptC(x,y);if(c)s.push([x+(Math.random()-.5)*.04,y+(Math.random()-.5)*.04,(Math.random()-.5)*.16,c[0],c[1],c[2]]);}return s;}

function mkObj(samples,px,py,pz,sc,fl){
    var local=samples.map(function(s){return[s[0]*sc,s[1]*sc,s[2]*sc,s[3],s[4],s[5]];});
    var P=mkP(local,.18,.90);P.position.set(px,py,pz);
    P.userData.base=new Float32Array(P.geometry.attributes.position.array);
    P.userData.fl=fl;P.userData.scale=sc;
    var n=P.geometry.attributes.position.count;var sd=new Float32Array(n*3);
    for(var i=0;i<n;i++){var rx=(Math.random()-.5)*2,ry=(Math.random()-.5)*2,rz=(Math.random()-.5)*2;var l=Math.sqrt(rx*rx+ry*ry+rz*rz)||1;sd[i*3]=rx/l;sd[i*3+1]=ry/l;sd[i*3+2]=rz/l;}
    P.userData.sd=sd;P.userData.sa=new Float32Array(n);
    wd.add(P);return P;
}
var sA=mkObj(genS(),-7,-7.2,2,1.25,false);
var sB=mkObj(genS(),0,-7.8,3.5,1.35,false);
var sC=mkObj(genS(),7,-7.2,2,1.25,false);
var IA=[wd.userData.fI,wd.userData.fJ,sA,sB,sC];

// Sakura petals
(function(){
    var n=350,pos=new Float32Array(n*3),col=new Float32Array(n*3),md=[];
    for(var i=0;i<n;i++){
        var x=(Math.random()-.5)*55,y=Math.random()*40-20,z=-2-Math.random()*8;
        pos[i*3]=x;pos[i*3+1]=y;pos[i*3+2]=z;
        var v=.7+Math.random()*.3;col[i*3]=.98;col[i*3+1]=v*.72;col[i*3+2]=v*.82;
        md.push({x:x,y:y,z:z,sp:.2+Math.random()*.4,ph:Math.random()*6.28,dx:(Math.random()-.5)*.3});
    }
    var g=new THREE.BufferGeometry();g.setAttribute('position',new THREE.BufferAttribute(pos,3));g.setAttribute('color',new THREE.BufferAttribute(col,3));
    var P=new THREE.Points(g,new THREE.PointsMaterial({size:.12,vertexColors:true,transparent:true,opacity:.5,blending:THREE.AdditiveBlending,depthWrite:false,sizeAttenuation:true}));
    P._md=md;wd.add(P);window._sakura=P;
})();

// Stars
(function(){
    var n=300,pos=new Float32Array(n*3),col=new Float32Array(n*3);
    for(var i=0;i<n;i++){pos[i*3]=(Math.random()-.5)*80;pos[i*3+1]=(Math.random()-.5)*55;pos[i*3+2]=-6-Math.random()*12;var v=.2+Math.random()*.5;col[i*3]=v;col[i*3+1]=v;col[i*3+2]=v+.06;}
    var g=new THREE.BufferGeometry();g.setAttribute('position',new THREE.BufferAttribute(pos,3));g.setAttribute('color',new THREE.BufferAttribute(col,3));
    wd.add(new THREE.Points(g,new THREE.PointsMaterial({size:.09,vertexColors:true,transparent:true,opacity:.4,blending:THREE.AdditiveBlending,depthWrite:false,sizeAttenuation:true})));
})();

// Route arc
(function(){
    var s=[],ax=-11,ay=4.5,bx=11,by=1.5,cx=0,cy=12;
    for(var i=0;i<=180;i++){var t=i/180,u=1-t;var px=u*u*ax+2*u*t*cx+t*t*bx;var py=u*u*ay+2*u*t*cy+t*t*by;var c=i%5<2?RD:i%5<4?SH:[1,.8,.9];s.push([px+(Math.random()-.5)*.3,py+(Math.random()-.5)*.3,(Math.random()-.5)*.4,c[0],c[1],c[2]]);}
    wd.add(mkP(s,.08,.55));
})();

// Glow texture untuk partikel
var _gcv=document.createElement('canvas');_gcv.width=64;_gcv.height=64;
var _gcx=_gcv.getContext('2d');
var _ggr=_gcx.createRadialGradient(32,32,0,32,32,32);
_ggr.addColorStop(0,'rgba(255,255,255,1)');
_ggr.addColorStop(.15,'rgba(255,255,255,.9)');
_ggr.addColorStop(.4,'rgba(255,255,255,.4)');
_ggr.addColorStop(.7,'rgba(255,255,255,.1)');
_ggr.addColorStop(1,'rgba(255,255,255,0)');
_gcx.fillStyle=_ggr;_gcx.fillRect(0,0,64,64);
var _glowTex=new THREE.CanvasTexture(_gcv);
// Hanabi System - Roket terbang lalu meledak (hanya saat interaksi)
var hanabi=(function(){
    var EMAX=1200,TMAX=400;
    var ep=new Float32Array(EMAX*3),ec=new Float32Array(EMAX*3),ev=new Float32Array(EMAX*3);
    var el=new Float32Array(EMAX),em=new Float32Array(EMAX),ea=new Uint8Array(EMAX);
    var eg=new THREE.BufferGeometry();
    eg.setAttribute('position',new THREE.BufferAttribute(ep,3));
    eg.setAttribute('color',new THREE.BufferAttribute(ec,3));
    var eP=new THREE.Points(eg,new THREE.PointsMaterial({map:_glowTex,size:.5,vertexColors:true,transparent:true,opacity:1,blending:THREE.AdditiveBlending,depthWrite:false,sizeAttenuation:true}));

    var tp=new Float32Array(TMAX*3),tc=new Float32Array(TMAX*3);
    var tl=new Float32Array(TMAX),tm=new Float32Array(TMAX),ta=new Uint8Array(TMAX);
    var tg=new THREE.BufferGeometry();
    tg.setAttribute('position',new THREE.BufferAttribute(tp,3));
    tg.setAttribute('color',new THREE.BufferAttribute(tc,3));
    var tP=new THREE.Points(tg,new THREE.PointsMaterial({map:_glowTex,size:.3,vertexColors:true,transparent:true,opacity:.9,blending:THREE.AdditiveBlending,depthWrite:false,sizeAttenuation:true}));

    var cols=[[1,.08,.15],[1,.72,.02],[1,.92,.88],[.1,.42,1],[1,.25,.55],[1,.48,0],[.92,.92,.12],[1,.18,.45],[.35,1,.45],[.8,.2,1]];
    var rockets=[];

    function launch(sx,sy,sz){
        var c=cols[Math.floor(Math.random()*cols.length)];
        rockets.push({x:sx,y:sy,z:sz,vy:14+Math.random()*5,vx:(Math.random()-.5)*1.2,vz:(Math.random()-.5)*.2,
            trail:0,color:c,targetY:7+Math.random()*6,active:true,phase:0});
    }
    function addTrail(x,y,z,c,sz){
        for(var i=0;i<TMAX;i++){if(!ta[i]){
            tp[i*3]=x+(Math.random()-.5)*.15;tp[i*3+1]=y;tp[i*3+2]=z+(Math.random()-.5)*.1;
            tc[i*3]=c[0]*(.6+Math.random()*.3);tc[i*3+1]=c[1]*(.6+Math.random()*.3);tc[i*3+2]=c[2]*(.6+Math.random()*.3);
            tl[i]=.15+Math.random()*.25;tm[i]=tl[i];ta[i]=1;break;
        }}
    }
    function explode(x,y,z,c){
        // Layer 1: Core burst (besar, terang)
        for(var i=0;i<120;i++){
            for(var j=0;j<EMAX;j++){if(!ea[j]){
                var th=Math.random()*Math.PI*2,ph=Math.acos(2*Math.random()-1);
                var sp=1.5+Math.random()*7;
                ev[j*3]=Math.sin(ph)*Math.cos(th)*sp;
                ev[j*3+1]=Math.sin(ph)*Math.sin(th)*sp*.8+1;
                ev[j*3+2]=Math.cos(ph)*sp*.2;
                ep[j*3]=x;ep[j*3+1]=y;ep[j*3+2]=z;
                ec[j*3]=Math.min(1,c[0]+(Math.random()-.5)*.1);
                ec[j*3+1]=Math.min(1,c[1]+(Math.random()-.5)*.1);
                ec[j*3+2]=Math.min(1,c[2]+(Math.random()-.5)*.1);
                el[j]=1.8+Math.random()*2;em[j]=el[j];ea[j]=1;break;
            }}
        }
        // Layer 2: Sparkle ring (kecil, cepat, efek kilau)
        for(var i=0;i<60;i++){
            for(var j=0;j<EMAX;j++){if(!ea[j]){
                var th=Math.random()*Math.PI*2,ph=Math.acos(2*Math.random()-1);
                var sp=4+Math.random()*10;
                ev[j*3]=Math.sin(ph)*Math.cos(th)*sp;
                ev[j*3+1]=Math.sin(ph)*Math.sin(th)*sp*.7+.5;
                ev[j*3+2]=Math.cos(ph)*sp*.15;
                ep[j*3]=x;ep[j*3+1]=y;ep[j*3+2]=z;
                ec[j*3]=1;ec[j*3+1]=.95;ec[j*3+2]=.9;
                el[j]=.6+Math.random()*1;em[j]=el[j];ea[j]=1;break;
            }}
        }
        // Layer 3: Lingkaran dalam (menyala terang di pusat)
        for(var i=0;i<30;i++){
            for(var j=0;j<EMAX;j++){if(!ea[j]){
                var a=Math.random()*Math.PI*2;
                var r=.3+Math.random()*1.2;
                ev[j*3]=Math.cos(a)*r*2;
                ev[j*3+1]=Math.sin(a)*r*2+.3;
                ev[j*3+2]=(Math.random()-.5)*.3;
                ep[j*3]=x;ep[j*3+1]=y;ep[j*3+2]=z;
                ec[j*3]=1;ec[j*3+1]=1;ec[j*3+2]=1;
                el[j]=.4+Math.random()*.6;em[j]=el[j];ea[j]=1;break;
            }}
        }
    }
    function update(dt){
        for(var r=rockets.length-1;r>=0;r--){
            var rk=rockets[r];if(!rk.active){rockets.splice(r,1);continue;}
            rk.y+=rk.vy*dt;rk.x+=rk.vx*dt;rk.vy-=1.8*dt;
            rk.trail-=dt;
            if(rk.trail<=0){
                addTrail(rk.x,rk.y,rk.z,rk.color);
                addTrail(rk.x+(Math.random()-.5)*.3,rk.y-.2,rk.z,rk.color);
                rk.trail=.012;
            }
            if(rk.y>=rk.targetY||rk.vy<=0){explode(rk.x,rk.y,rk.z,rk.color);rk.active=false;}
        }
        for(var i=0;i<TMAX;i++){if(!ta[i])continue;
            tl[i]-=dt;if(tl[i]<=0){ta[i]=0;tp[i*3+1]=-999;continue;}
            var f=tl[i]/tm[i];tc[i*3]*=(.93+f*.05);tc[i*3+1]*=(.93+f*.05);tc[i*3+2]*=(.93+f*.05);
        }
        for(var i=0;i<EMAX;i++){if(!ea[i])continue;
            el[i]-=dt;if(el[i]<=0){ea[i]=0;ep[i*3+1]=-999;continue;}
            ev[i*3+1]-=1.0*dt;
            ep[i*3]+=ev[i*3]*dt;ep[i*3+1]+=ev[i*3+1]*dt;ep[i*3+2]+=ev[i*3+2]*dt;
            ev[i*3]*=.98;ev[i*3+1]*=.98;ev[i*3+2]*=.98;
            var f=el[i]/em[i];
            ec[i*3]*=(.997-f*.003);ec[i*3+1]*=(.997-f*.003);ec[i*3+2]*=(.997-f*.003);
        }
        eg.attributes.position.needsUpdate=true;eg.attributes.color.needsUpdate=true;
        tg.attributes.position.needsUpdate=true;tg.attributes.color.needsUpdate=true;
    }
    return{eP:eP,tP:tP,launch:launch,update:update};
})();
wd.add(hanabi.eP);wd.add(hanabi.tP);

// Hanabi trigger - hanya saat cursor BARU masuk zone (bukan looping)
var mouseZone=0,hCooldown=0;

// Mouse
var mx=0,my=0,tx=0,ty=0,mwX=-999,mwY=-999,click=0;
document.addEventListener('pointermove',function(e){
    mx=(e.clientX/W.innerWidth-.5)*2;my=(e.clientY/W.innerHeight-.5)*2;
    // Project mouse to world z=0 plane accounting for camera
    var ndc=new THREE.Vector2(mx,-my);
    var raycaster=new THREE.Raycaster();raycaster.setFromCamera(ndc,cam);
    var plane=new THREE.Plane(new THREE.Vector3(0,0,1),0);
    var target=new THREE.Vector3();
    raycaster.ray.intersectPlane(plane,target);
    if(target){mwX=target.x;mwY=target.y;}
});
document.addEventListener('pointerdown',function(){click=1;});
document.addEventListener('pointerup',function(){click=0;});

function animate(){
    requestAnimationFrame(animate);
    var t=Date.now()*.001;
    tx+=(mx-tx)*.035;ty+=(my-ty)*.035;
    wd.rotation.y=tx*.10+Math.sin(t*.04)*.03;
    wd.rotation.x=ty*.06+Math.sin(t*.03)*.02+.03;

    // Sakura
    var sk=window._sakura;
    if(sk){var ma=sk.geometry.attributes.position.array;var md=sk._md;for(var i=0;i<md.length;i++){var d=md[i];ma[i*3]=d.x+Math.sin(t*.3+d.ph)*1+d.dx*t*.05;ma[i*3+1]=((d.y-((t*d.sp+d.ph*10)%40))+20);ma[i*3+2]=d.z+Math.sin(t*.4+d.ph)*.6;}sk.geometry.attributes.position.needsUpdate=true;}

    // Hanabi - HANYA saat cursor BARU masuk zone (bukan looping)
    var newZone=0;
    if(mwX>2)newZone=1;
    if(mwX<-2)newZone=2;
    if(newZone!==mouseZone&&newZone!==0&&hCooldown<=0){
        if(newZone===1){hanabi.launch(8+(Math.random()-.5)*5,-8,-1+(Math.random()-.5)*2);}
        else{hanabi.launch(-8+(Math.random()-.5)*5,-8,-1+(Math.random()-.5)*2);}
        hCooldown=2.5;
    }
    mouseZone=newZone;
    hCooldown-=0.016;
    hanabi.update(0.016);

    // Interactive scatter - use worldToLocal for accuracy
    var _ml=new THREE.Vector3();
    for(var oi=0;oi<IA.length;oi++){
        var obj=IA[oi];if(!obj)continue;
        var arr=obj.geometry.attributes.position.array;var base=obj.userData.base;var sd=obj.userData.sd;var sa=obj.userData.sa;var isF=obj.userData.fl;
        var inf=isF?9:6;var str=isF?7:6;
        // Convert mouse world position to object local space
        _ml.set(mwX,mwY,0);obj.worldToLocal(_ml);
        for(var i=0;i<arr.length;i+=3){
            var idx=i/3;
            var bx=base[i],by=base[i+1],bz=base[i+2];
            var nx=bx,ny=by,nz=bz;
            if(isF){nz+=Math.sin(bx*.5+t*1.2)*.8+Math.sin(by*.4-t*1.3)*.3;ny+=Math.sin(bx*.4+t*.7)*.12;}
            else{nx+=Math.sin(t*1.05+obj.userData.scale*5+idx*.0001)*.04;ny+=Math.sin(t*1.2+idx*.0005)*.06;}
            var dx=bx-_ml.x,dy=by-_ml.y;var d=Math.sqrt(dx*dx+dy*dy);
            var target=0;if(d<inf){target=(1-d/inf)*str*(1+click*2.5);}
            sa[idx]+=(target-sa[idx])*.10;
            if(sa[idx]>.001){arr[i]=nx+sd[i]*sa[idx];arr[i+1]=ny+sd[i+1]*sa[idx];arr[i+2]=nz+sd[i+2]*sa[idx];}
            else{arr[i]=nx;arr[i+1]=ny;arr[i+2]=nz;}
        }
        obj.geometry.attributes.position.needsUpdate=true;
    }
    click=Math.max(0,click-.015);
    r.render(sc,cam);
}
animate();
W.addEventListener('resize',function(){cam.aspect=W.innerWidth/W.innerHeight;cam.updateProjectionMatrix();r.setSize(W.innerWidth,W.innerHeight);});
})();
</script>
<script>
(function(){
var lang='id';
var tr={
    id:{badge:'Sistem Online',h1:'Pendaftaran Magang',h1sub:'Indonesia — Jepang',desc:'Program magang terpadu dengan LPK mitra untuk mengembangkan skill dan pengalaman kerja Anda. Daftar online dan tunggu verifikasi.',btn1:'Lihat Informasi →',btn2:'Hubungi Admin',s1:'Mitra LPK',s2:'Bidang',s3:'2 Negara',l2l:'Info Program',l2t:'Tentang Program Magang',l2d:'Program magang terpadu bersama LPK dan perusahaan mitra untuk mengembangkan skill serta pengalaman kerja Anda.',c1h:'Sertifikat Resmi',c1p:'Dapatkan sertifikat magang resmi yang diakui sebagai pengalaman kerja profesional.',c2h:'Pendampingan Mentor',c2p:'Dibimbing langsung oleh mentor berpengalaman di bidangnya masing-masing.',c3h:'Pengembangan Skill',c3p:'Pelatihan dan praktik nyata untuk meningkatkan kompetensi dan kesiapan kerja.',l3l:'Hubungi Kami',l3t:'Minta Link Pendaftaran',l3d:'Hubungi admin melalui kontak di bawah ini',ctah:'Siap Magang?',ctap:'Hubungi admin via WhatsApp atau email, lalu sebutkan bidang magang yang diinginkan.',ctawa:'Chat WhatsApp',ctaemail:'Kirim Email',footer:'Sistem Pendaftaran Magang'},
    jp:{badge:'オンラインシステム',h1:'インターンシップ登録',h1sub:'インドネシア — 日本',desc:'LPKパートナーとの統合インターンシップ。スキルと職業経験を向上させましょう。オンラインで登録し、確認をお待ちください。',btn1:'詳細を見る →',btn2:'管理者に連絡',s1:'提携LPK',s2:'分野',s3:'2カ国',l2l:'プログラム情報',l2t:'インターンシップについて',l2d:'LPKや提携企業と一緒にスキルと職業経験を磨く統合プログラムです。',c1h:'公式証明書',c1p:'プロの職業経験として認められる公式インターンシップ証明書を取得できます。',c2h:'メンター指導',c2p:'各分野の経験豊富なメンターから直接指導を受けられます。',c3h:'スキル開発',c3p:'コンピテンシーと就職準備を高めるための実践的なトレーニングと実習。',l3l:'お問い合わせ',l3t:'登録リンクを依頼',l3d:'以下の連絡先まで管理者にお問い合わせください',ctah:'インターンシップ准备はいいですか？',ctap:'WhatsAppまたはメールで管理者に連絡し、希望する分野をお知らせください。',ctawa:'WhatsAppで連絡',ctaemail:'メールを送信',footer:'インターンシップ登録システム'}
};
function sw(){
    var d=tr[lang];
    var e=function(id){return document.getElementById(id);};
    if(e('t-badge'))e('t-badge').textContent=d.badge;
    if(e('t-h1'))e('t-h1').innerHTML='<span class="g">'+d.h1+'</span><br>'+d.h1sub;
    if(e('t-desc'))e('t-desc').textContent=d.desc;
    if(e('t-btn1'))e('t-btn1').innerHTML=d.btn1;
    if(e('t-btn2'))e('t-btn2').textContent=d.btn2;
    if(e('t-s1'))e('t-s1').textContent=d.s1;
    if(e('t-s2'))e('t-s2').textContent=d.s2;
    if(e('t-s3'))e('t-s3').textContent=d.s3;
    if(e('t-l2label'))e('t-l2label').textContent=d.l2l;
    if(e('t-l2title'))e('t-l2title').textContent=d.l2t;
    if(e('t-l2desc'))e('t-l2desc').textContent=d.l2d;
    if(e('t-c1h'))e('t-c1h').textContent=d.c1h;
    if(e('t-c1p'))e('t-c1p').textContent=d.c1p;
    if(e('t-c2h'))e('t-c2h').textContent=d.c2h;
    if(e('t-c2p'))e('t-c2p').textContent=d.c2p;
    if(e('t-c3h'))e('t-c3h').textContent=d.c3h;
    if(e('t-c3p'))e('t-c3p').textContent=d.c3p;
    
    
    
    if(e('t-l3label'))e('t-l3label').textContent=d.l3l;
    if(e('t-l3title'))e('t-l3title').textContent=d.l3t;
    if(e('t-l3desc'))e('t-l3desc').textContent=d.l3d;
    if(e('t-ctah'))e('t-ctah').textContent=d.ctah;
    if(e('t-ctap'))e('t-ctap').textContent=d.ctap;
    if(e('t-ctawa'))e('t-ctawa').textContent=d.ctawa;
    if(e('t-ctaemail'))e('t-ctaemail').textContent=d.ctaemail;
    if(e('t-footer'))e('t-footer').innerHTML='© '+new Date().getFullYear()+' '+d.footer;
    e('langBtn').textContent=lang==='id'?'JP':'ID';
}
document.getElementById('langBtn').addEventListener('click',function(){lang=lang==='id'?'jp':'id';sw();});
sw();
})();
</script>
</body>
</html>