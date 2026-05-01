<?php
// --- Session hardening -------------------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('verora_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/admin/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

function requireLogin(): void
{
    if (empty($_SESSION['admin'])) {
        header('Location: /admin/');
        exit;
    }
}

// --- CSRF --------------------------------------------------------------------
function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function requireCsrf(): void
{
    $submitted = $_POST['_csrf'] ?? $_GET['_csrf'] ?? '';
    $expected  = $_SESSION['csrf'] ?? '';
    if (!$expected || !is_string($submitted) || !hash_equals($expected, $submitted)) {
        http_response_code(403);
        exit('Forbidden: invalid CSRF token');
    }
}

// --- Safe post-file validation (defense in depth) ----------------------------
function validPostFilename(string $file): bool
{
    if ($file === '') return false;
    if ($file !== basename($file)) return false;
    return (bool)preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.md$/', $file);
}

function requireValidPostFilename(string $file): string
{
    $file = basename($file);
    if (!validPostFilename($file)) {
        http_response_code(400);
        exit('Bad Request: invalid filename');
    }
    return $file;
}

// --- Security headers for admin pages ---------------------------------------
function adminSecurityHeaders(): void
{
    if (headers_sent()) return;
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; font-src 'self' https://fonts.gstatic.com data:; script-src 'self' 'unsafe-inline' https://unpkg.com; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Frame-Options: DENY');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}
adminSecurityHeaders();

function adminHead(string $title = 'Admin', string $extra = ''): void { ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title><?= htmlspecialchars($title) ?> · Verora Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<?= $extra ?>
<style>
:root{--bg:#fffaf8;--surface:rgba(255,252,250,.88);--surface-strong:rgba(255,255,255,.94);--text:#37272d;--muted:#725861;--gold:#b8883e;--pink:#f7d7de;--pink-strong:#d9849d;--line:rgba(185,137,103,.18);--line-strong:rgba(185,137,103,.3);--shadow:0 20px 60px rgba(120,77,86,.11);--r:16px;--sidebar:280px}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Kanit',sans-serif;background:radial-gradient(ellipse 65% 45% at 0% 0%,rgba(247,215,222,.6),transparent 60%),radial-gradient(ellipse 55% 40% at 100% 6%,rgba(237,218,188,.45),transparent 60%),#fffdfb;color:var(--text);min-height:100vh;-webkit-font-smoothing:antialiased}
a{color:inherit;text-decoration:none}
body.admin-body{overflow-x:hidden}
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.65rem 1.25rem;border-radius:999px;border:1px solid transparent;font-family:inherit;font-size:.88rem;font-weight:600;cursor:pointer;transition:transform 180ms,box-shadow 180ms;white-space:nowrap}
.btn:hover{transform:translateY(-1px)}
.btn-primary{background:linear-gradient(135deg,#d9849d,#c79c63);color:#fff;box-shadow:0 10px 28px rgba(180,100,120,.24)}
.btn-ghost{background:rgba(255,255,255,.62);border-color:var(--line);color:var(--text)}
.btn-danger{background:rgba(200,50,50,.08);border-color:rgba(200,50,50,.2);color:#b02020}
.btn-sm{padding:.45rem .9rem;font-size:.82rem}
.btn-block{width:100%;justify-content:center}
label{display:block;margin-bottom:1rem}
label>span{display:block;font-size:.78rem;font-weight:600;color:var(--muted);margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.1em}
input[type=text],input[type=password],input[type=email],input[type=date],textarea,select{width:100%;padding:.75rem 1rem;border-radius:var(--r);border:1px solid var(--line);background:rgba(255,255,255,.72);font-family:inherit;font-size:.95rem;color:var(--text);outline:none;transition:border-color 180ms,box-shadow 180ms}
input:focus,textarea:focus,select:focus{border-color:rgba(217,132,157,.7);box-shadow:0 0 0 3px rgba(247,215,222,.25)}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.flash-ok{padding:.75rem 1rem;border-radius:var(--r);background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#0a6640;margin-bottom:1rem;font-size:.9rem}
.flash-err{padding:.75rem 1rem;border-radius:var(--r);background:rgba(200,50,50,.1);border:1px solid rgba(200,50,50,.2);color:#b02020;margin-bottom:1rem;font-size:.9rem}
.flash-note{padding:.75rem 1rem;border-radius:var(--r);background:rgba(184,136,62,.1);border:1px solid rgba(184,136,62,.2);color:#7b5d20;margin-bottom:1rem;font-size:.9rem}
.article-list{display:grid;gap:.65rem}
.article-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.9rem 1.15rem;border-radius:var(--r);background:rgba(255,255,255,.52);border:1px solid rgba(255,255,255,.88);transition:box-shadow 180ms}
.article-row:hover{box-shadow:0 6px 20px rgba(120,77,86,.09)}
.article-meta{flex:1;min-width:0}
.article-meta strong{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:.97rem}
.article-meta span{font-size:.78rem;color:var(--muted)}
.article-actions{display:flex;gap:.45rem;flex-shrink:0}
.toggle-row{display:flex;align-items:center;gap:.6rem;font-size:.9rem;cursor:pointer}
.toggle-row input[type=checkbox]{width:1rem;height:1rem;accent-color:#d9849d;cursor:pointer}
.inline-form{display:inline;margin:0}
.muted{color:var(--muted)}
.stack-lg > * + *{margin-top:1.5rem}
.stack-md > * + *{margin-top:1rem}
.stack-sm > * + *{margin-top:.75rem}
.admin-shell{display:grid;grid-template-columns:var(--sidebar) minmax(0,1fr);min-height:100vh;position:relative}
.admin-sidebar{padding:1.25rem;position:sticky;top:0;height:100vh}
.admin-sidebar-panel{height:100%;display:flex;flex-direction:column;gap:1.25rem;background:rgba(255,252,250,.78);border:1px solid rgba(255,255,255,.86);border-top-color:rgba(255,255,255,.96);box-shadow:var(--shadow);backdrop-filter:blur(20px);border-radius:30px;padding:1.35rem}
.admin-brand{display:flex;align-items:flex-start;gap:.85rem}
.admin-brand img{width:2.35rem;height:2.35rem;flex-shrink:0}
.admin-brand-copy strong{display:block;font-size:1rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase}
.admin-brand-copy span{display:block;font-size:.78rem;color:var(--muted);line-height:1.5}
.admin-sidebar-action{padding:1rem;border-radius:22px;background:linear-gradient(180deg,rgba(247,215,222,.55),rgba(255,255,255,.5));border:1px solid rgba(255,255,255,.85)}
.admin-nav{display:grid;gap:1rem;flex:1}
.admin-nav-group{display:grid;gap:.45rem}
.admin-nav-group-label{font-size:.72rem;font-weight:700;letter-spacing:.16em;color:var(--muted);text-transform:uppercase;padding:0 .8rem}
.admin-nav-link{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.85rem .95rem;border-radius:18px;color:var(--text);border:1px solid transparent;transition:background 180ms,border-color 180ms,transform 180ms}
.admin-nav-link:hover{background:rgba(255,255,255,.68);border-color:rgba(255,255,255,.86)}
.admin-nav-link[aria-current="page"]{background:linear-gradient(135deg,rgba(217,132,157,.18),rgba(199,156,99,.12));border-color:rgba(185,137,103,.18);box-shadow:inset 0 1px 0 rgba(255,255,255,.65)}
.admin-nav-link small{font-size:.72rem;color:var(--muted);font-weight:500}
.admin-sidebar-footer{display:grid;gap:.6rem;padding-top:.25rem;border-top:1px solid var(--line)}
.admin-main{min-width:0;padding:1.5rem 1.5rem 2rem}
.admin-main-inner{max-width:1200px;margin:0 auto}
.admin-mobilebar{display:none;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.1rem;margin-bottom:1rem;border-radius:22px;background:var(--surface-strong);border:1px solid rgba(255,255,255,.86);box-shadow:0 10px 25px rgba(120,77,86,.08)}
.admin-mobilebar-title{font-size:.95rem;font-weight:600}
.sidebar-toggle{display:inline-flex;align-items:center;justify-content:center;width:2.65rem;height:2.65rem;border-radius:999px;border:1px solid var(--line);background:rgba(255,255,255,.8);cursor:pointer;font:inherit;color:var(--text)}
.admin-sidebar-backdrop{display:none}
.admin-page{background:var(--surface);border:1px solid rgba(255,255,255,.82);border-top-color:rgba(255,255,255,.96);box-shadow:var(--shadow);backdrop-filter:blur(20px);border-radius:30px;padding:2rem}
.admin-page-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap}
.admin-page-eyebrow{display:inline-flex;align-items:center;gap:.45rem;font-size:.76rem;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:var(--gold);margin-bottom:.55rem}
.admin-page-header h1{font-size:1.7rem;line-height:1.1;font-weight:700}
.admin-page-header p{margin-top:.45rem;max-width:48rem;color:var(--muted);line-height:1.7}
.admin-page-actions{display:flex;gap:.75rem;flex-wrap:wrap;align-items:center}
.overview-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;margin-bottom:1.5rem}
.overview-card,.section-card,.note-card{background:rgba(255,255,255,.55);border:1px solid rgba(255,255,255,.86);border-radius:22px;padding:1.15rem 1.2rem}
.overview-card strong{display:block;font-size:1.9rem;line-height:1;font-weight:700;margin-bottom:.35rem}
.overview-card span{display:block;font-size:.82rem;color:var(--muted);text-transform:uppercase;letter-spacing:.12em}
.section-grid{display:grid;grid-template-columns:2fr 1fr;gap:1rem}
.section-heading{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem}
.section-heading h2{font-size:1.05rem;font-weight:700}
.section-heading p{font-size:.85rem;color:var(--muted)}
.form-section{padding:1.1rem 1.2rem;border:1px solid var(--line);border-radius:22px;background:rgba(255,255,255,.45)}
.form-section summary{cursor:pointer;font-weight:700;font-size:1rem;list-style:none}
.form-section summary::-webkit-details-marker{display:none}
.form-section-body{margin-top:1rem}
.sticky-actions{display:flex;gap:.75rem;position:sticky;bottom:1rem;padding:1rem;background:var(--surface-strong);border-radius:22px;box-shadow:var(--shadow);border:1px solid var(--line);flex-wrap:wrap;z-index:2}
.empty-state{padding:3rem 1.5rem;text-align:center;color:var(--muted);border:1px dashed var(--line-strong);border-radius:22px;background:rgba(255,255,255,.35)}
.meta-list{display:grid;gap:.4rem;font-size:.84rem;color:var(--muted)}
.pill{display:inline-flex;align-items:center;gap:.35rem;padding:.35rem .65rem;border-radius:999px;background:rgba(255,255,255,.6);border:1px solid var(--line);font-size:.76rem;color:var(--muted)}
.password-panel{max-width:520px}
@media(max-width:1080px){.overview-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.section-grid{grid-template-columns:1fr}.admin-shell{grid-template-columns:1fr}.admin-sidebar{position:fixed;left:0;top:0;bottom:0;height:100vh;width:min(92vw,var(--sidebar));z-index:30;transform:translateX(calc(-100% - 1rem));transition:transform 220ms ease}.admin-sidebar-backdrop{position:fixed;inset:0;background:rgba(55,39,45,.25);backdrop-filter:blur(2px);z-index:20}.admin-mobilebar{display:flex}body.admin-nav-open .admin-sidebar{transform:translateX(0)}body.admin-nav-open .admin-sidebar-backdrop{display:block}}
@media(max-width:720px){.field-row{grid-template-columns:1fr}.admin-main{padding:1rem}.admin-page{padding:1.25rem}.overview-grid{grid-template-columns:1fr}.admin-page-header h1{font-size:1.45rem}.article-row{align-items:flex-start;flex-direction:column}.article-actions{width:100%;flex-wrap:wrap}}
.img-tabs{display:flex;gap:2px;background:rgba(0,0,0,.06);border-radius:12px;padding:3px;margin-bottom:.75rem}
.img-tab{flex:1;text-align:center;padding:.45rem .5rem;border-radius:9px;font-size:.8rem;font-weight:600;cursor:pointer;border:none;background:none;font-family:inherit;color:var(--muted);transition:background 150ms,color 150ms;line-height:1.3}
.img-tab.active{background:#fff;color:var(--text);box-shadow:0 1px 4px rgba(0,0,0,.1)}
.img-upload-label{cursor:pointer;position:relative;overflow:hidden;display:inline-flex;align-items:center;gap:.4rem}
.img-upload-label input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.img-upload-status{font-size:.8rem;margin-top:.4rem;min-height:1.1em}
.img-preview-wrap img{display:block;max-width:100%;max-height:180px;border-radius:10px;margin-top:.5rem;border:1px solid var(--line);object-fit:cover}
.img-hint{font-size:.82rem;color:var(--muted);margin-top:.45rem}
.batch-url-fields{display:grid;gap:.75rem}
.batch-previews{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-top:.85rem}
.batch-preview-slot .slot-label{font-size:.73rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.1em;margin-bottom:.35rem}
.batch-previews .img-preview-wrap img{max-height:120px}
.crop-modal{position:fixed;inset:0;z-index:999;background:rgba(0,0,0,.72);display:flex;align-items:center;justify-content:center;padding:1rem}
.crop-modal-inner{background:#fff;border-radius:20px;padding:1.25rem;width:min(820px,100%);max-height:92vh;display:flex;flex-direction:column;gap:1rem;overflow:hidden}
.crop-modal-title{font-weight:700;font-size:1.05rem}
.crop-img-wrap{flex:1;overflow:hidden;min-height:280px;max-height:calc(92vh - 140px);background:#f0f0f0;border-radius:12px}
.crop-img-wrap img{max-width:100%;display:block}
.crop-modal-actions{display:flex;gap:.75rem;justify-content:flex-end;flex-shrink:0}
</style>
<script>
(function(){
  // ── helpers ──────────────────────────────────────────────────────────
  function getCsrf(){return(document.querySelector('input[name=_csrf]')||{}).value||'';}
  function setPreview(el,url){if(!el)return;el.innerHTML=url?'<img src="'+url+'" onerror="this.parentNode.innerHTML=\'\'">':'';}
  function setStatus(el,msg,ok){if(!el)return;el.textContent=msg;el.style.color=ok===true?'#0a6640':ok===false?'#b02020':'var(--muted)';}

  function uploadBlob(blob,statusEl,cb){
    setStatus(statusEl,'กำลังอัปโหลด...',null);
    var fd=new FormData();fd.append('_csrf',getCsrf());fd.append('file',blob,'image.jpg');
    fetch('/admin/upload.php',{method:'POST',body:fd})
      .then(function(r){return r.json();})
      .then(function(d){if(d.url){setStatus(statusEl,'✓ สำเร็จ',true);cb(d.url);}else{setStatus(statusEl,d.error||'อัปโหลดล้มเหลว',false);}})
      .catch(function(){setStatus(statusEl,'อัปโหลดล้มเหลว — ตรวจสอบการเชื่อมต่อ',false);});
  }

  function centerCrop(file,ratio,cb){
    var img=new Image(),url=URL.createObjectURL(file);
    img.onload=function(){
      var sw,sh,sx,sy,sR=img.width/img.height,tR=ratio||sR;
      if(sR>tR){sh=img.height;sw=sh*tR;sx=(img.width-sw)/2;sy=0;}
      else{sw=img.width;sh=sw/tR;sx=0;sy=(img.height-sh)/2;}
      var canvas=document.createElement('canvas');
      canvas.width=sw;canvas.height=sh;
      canvas.getContext('2d').drawImage(img,sx,sy,sw,sh,0,0,sw,sh);
      URL.revokeObjectURL(url);
      canvas.toBlob(cb,'image/jpeg',0.92);
    };
    img.src=url;
  }

  function loadCropper(cb){
    if(window.Cropper){cb();return;}
    var l=document.createElement('link');l.rel='stylesheet';l.href='https://unpkg.com/cropperjs/dist/cropper.min.css';document.head.appendChild(l);
    var s=document.createElement('script');s.src='https://unpkg.com/cropperjs/dist/cropper.min.js';s.onload=cb;document.head.appendChild(s);
  }

  function openCropper(file,ratio,title,cb){
    loadCropper(function(){
      var modal=document.createElement('div');modal.className='crop-modal';
      modal.innerHTML='<div class="crop-modal-inner"><div class="crop-modal-title">'+title+'</div>'
        +'<div class="crop-img-wrap"><img class="crop-src"></div>'
        +'<div class="crop-modal-actions">'
        +'<button type="button" class="btn btn-primary crop-ok">ยืนยัน Crop</button>'
        +'<button type="button" class="btn btn-ghost crop-skip">ข้าม</button>'
        +'</div></div>';
      document.body.appendChild(modal);
      var img=modal.querySelector('.crop-src'),objUrl=URL.createObjectURL(file),cropper;
      img.src=objUrl;
      img.onload=function(){cropper=new Cropper(img,{aspectRatio:ratio||NaN,viewMode:1,autoCropArea:0.9});};
      function cleanup(){if(cropper)cropper.destroy();URL.revokeObjectURL(objUrl);document.body.removeChild(modal);}
      modal.querySelector('.crop-ok').addEventListener('click',function(){
        cropper.getCroppedCanvas({maxWidth:2400,maxHeight:2400}).toBlob(function(blob){cleanup();cb(blob);},'image/jpeg',0.92);
      });
      modal.querySelector('.crop-skip').addEventListener('click',function(){cleanup();cb(null);});
    });
  }

  // ── tab switching ─────────────────────────────────────────────────────
  function initTabs(wrap){
    wrap.querySelectorAll('.img-tab').forEach(function(tab){
      tab.addEventListener('click',function(){
        wrap.querySelectorAll('.img-tab').forEach(function(t){t.classList.remove('active');});
        tab.classList.add('active');
        var mode=tab.dataset.tab;
        wrap.querySelectorAll('[data-panel]').forEach(function(p){p.hidden=true;});
        var panel=wrap.querySelector('[data-panel="'+mode+'"]');if(panel)panel.hidden=false;
      });
    });
  }

  // ── single-field picker ───────────────────────────────────────────────
  function initSingleField(wrap){
    initTabs(wrap);
    var ratio=parseFloat(wrap.dataset.ratio)||0;
    var urlInput=wrap.querySelector('.img-url-input');
    var status=wrap.querySelector('.img-upload-status');
    var preview=wrap.querySelector('.img-preview-wrap');
    if(urlInput.value)setPreview(preview,urlInput.value);
    urlInput.addEventListener('input',function(){setPreview(preview,urlInput.value);});

    var autoFile=wrap.querySelector('.img-auto-file');
    if(autoFile){autoFile.addEventListener('change',function(){
      if(!autoFile.files.length)return;
      setStatus(status,'กำลังประมวลผล...',null);
      centerCrop(autoFile.files[0],ratio,function(blob){
        uploadBlob(blob,status,function(url){urlInput.value=url;setPreview(preview,url);});
      });
    });}

    var manualFile=wrap.querySelector('.img-manual-file');
    if(manualFile){manualFile.addEventListener('change',function(){
      if(!manualFile.files.length)return;
      openCropper(manualFile.files[0],ratio,'ปรับ Crop',function(blob){
        if(!blob)return;
        uploadBlob(blob,status,function(url){urlInput.value=url;setPreview(preview,url);});
      });
    });}
  }

  // ── batch-field picker (Web / Facebook / Instagram) ───────────────────
  var FORMATS=[
    {key:'image',ratio:1.5,label:'Web (3:2)',hint:'1200×800'},
    {key:'image_facebook',ratio:1.905,label:'Facebook (1.91:1)',hint:'1200×630'},
    {key:'image_instagram',ratio:1,label:'Instagram (1:1)',hint:'1080×1080'},
  ];

  function initBatchField(wrap){
    initTabs(wrap);
    var status=wrap.querySelector('.batch-status');

    FORMATS.forEach(function(f){
      var inp=wrap.querySelector('.url-input-'+f.key);
      var prev=wrap.querySelector('.preview-'+f.key);
      if(inp&&inp.value)setPreview(prev,inp.value);
      if(inp)inp.addEventListener('input',function(){setPreview(prev,inp.value);});
    });

    var autoFile=wrap.querySelector('.batch-auto-file');
    if(autoFile){autoFile.addEventListener('change',function(){
      if(!autoFile.files.length)return;
      var file=autoFile.files[0],done=0;
      setStatus(status,'กำลังประมวลผล...',null);
      FORMATS.forEach(function(f){
        centerCrop(file,f.ratio,function(blob){
          var fd=new FormData();fd.append('_csrf',getCsrf());fd.append('file',blob,'image.jpg');
          fetch('/admin/upload.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
            if(d.url){
              var inp=wrap.querySelector('.url-input-'+f.key);if(inp)inp.value=d.url;
              setPreview(wrap.querySelector('.preview-'+f.key),d.url);
              done++;setStatus(status,done<3?'อัปโหลด '+done+'/3...':'✓ สำเร็จทั้ง 3 รูปแบบ',done===3?true:null);
            }
          });
        });
      });
    });}

    var manualFile=wrap.querySelector('.batch-manual-file');
    if(manualFile){manualFile.addEventListener('change',function(){
      if(!manualFile.files.length)return;
      var file=manualFile.files[0],done=0;
      setStatus(status,'',null);
      function next(i){
        if(i>=FORMATS.length){setStatus(status,'✓ บันทึกสำเร็จทั้ง 3 รูปแบบ',true);return;}
        var f=FORMATS[i];
        openCropper(file,f.ratio,f.label+' — '+f.hint,function(blob){
          if(!blob){next(i+1);return;}
          setStatus(status,'กำลังอัปโหลด '+f.label+'...',null);
          var fd=new FormData();fd.append('_csrf',getCsrf());fd.append('file',blob,'image.jpg');
          fetch('/admin/upload.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
            if(d.url){
              var inp=wrap.querySelector('.url-input-'+f.key);if(inp)inp.value=d.url;
              setPreview(wrap.querySelector('.preview-'+f.key),d.url);done++;
            }
            next(i+1);
          });
        });
      }
      next(0);
    });}
  }

  function init(){
    document.querySelectorAll('[data-img-field]').forEach(initSingleField);
    document.querySelectorAll('[data-img-batch]').forEach(initBatchField);
  }
  if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',init);}else{init();}
})();
</script>
</head>
<body class="admin-body">
<?php }

function adminShellStart(string $page = '', string $mobileTitle = 'Verora CMS'): void { ?>
<div class="admin-shell">
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-sidebar-panel">
            <a href="/admin/dashboard.php" class="admin-brand">
                <img src="/assets/verora-balloon-mark.svg" alt="Verora">
                <div class="admin-brand-copy">
                    <strong>Verora CMS</strong>
                    <span>จัดการคอนเทนต์, หน้าเว็บ, และงานเผยแพร่ในที่เดียว</span>
                </div>
            </a>

            <div class="admin-sidebar-action">
                <a href="/admin/edit.php" class="btn btn-primary btn-block">+ สร้างบทความใหม่</a>
            </div>

            <nav class="admin-nav" aria-label="Admin navigation">
                <div class="admin-nav-group">
                    <div class="admin-nav-group-label">Overview</div>
                    <a href="/admin/dashboard.php" class="admin-nav-link" <?= $page === 'dashboard' ? 'aria-current="page"' : '' ?>>
                        <span>Dashboard</span>
                        <small>ภาพรวมระบบ</small>
                    </a>
                </div>

                <div class="admin-nav-group">
                    <div class="admin-nav-group-label">Content</div>
                    <a href="/admin/content.php" class="admin-nav-link" <?= $page === 'content' ? 'aria-current="page"' : '' ?>>
                        <span>Website Content</span>
                        <small>ข้อความบนเว็บ</small>
                    </a>
                    <a href="/admin/dashboard.php" class="admin-nav-link" <?= $page === 'posts' ? 'aria-current="page"' : '' ?>>
                        <span>Blog Articles</span>
                        <small>บทความและรีวิว</small>
                    </a>
                    <a href="/admin/gallery.php" class="admin-nav-link" <?= $page === 'gallery' ? 'aria-current="page"' : '' ?>>
                        <span>Gallery</span>
                        <small>ผลงานและภาพโชว์เคส</small>
                    </a>
                </div>

                <div class="admin-nav-group">
                    <div class="admin-nav-group-label">System</div>
                    <a href="/admin/settings.php" class="admin-nav-link" <?= $page === 'settings' ? 'aria-current="page"' : '' ?>>
                        <span>Social Settings</span>
                        <small>Facebook และ Instagram</small>
                    </a>
                    <a href="/admin/change-password.php" class="admin-nav-link" <?= $page === 'password' ? 'aria-current="page"' : '' ?>>
                        <span>Security</span>
                        <small>รหัสผ่านผู้ดูแล</small>
                    </a>
                </div>
            </nav>

            <div class="admin-sidebar-footer">
                <a href="/" target="_blank" class="btn btn-ghost btn-sm">ดูหน้าเว็บจริง</a>
                <a href="/admin/logout.php" class="btn btn-ghost btn-sm">ออกจากระบบ</a>
            </div>
        </div>
    </aside>

    <div class="admin-sidebar-backdrop" data-sidebar-close></div>

    <main class="admin-main">
        <div class="admin-main-inner">
            <div class="admin-mobilebar">
                <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-controls="admin-sidebar" aria-expanded="false">☰</button>
                <div class="admin-mobilebar-title"><?= htmlspecialchars($mobileTitle) ?></div>
                <a href="/admin/edit.php" class="btn btn-primary btn-sm">+ บทความ</a>
            </div>
<?php }

function adminPageHeader(string $eyebrow, string $title, string $description = '', string $actions = ''): void { ?>
<header class="admin-page-header">
    <div>
        <div class="admin-page-eyebrow"><?= htmlspecialchars($eyebrow) ?></div>
        <h1><?= htmlspecialchars($title) ?></h1>
        <?php if ($description !== ''): ?>
            <p><?= htmlspecialchars($description) ?></p>
        <?php endif ?>
    </div>
    <?php if ($actions !== ''): ?>
        <div class="admin-page-actions"><?= $actions ?></div>
    <?php endif ?>
</header>
<?php }

function imagePickerField(string $name, string $value, bool $required = false, float $ratio = 0): void
{
    $n   = htmlspecialchars($name);
    $v   = htmlspecialchars($value);
    $req = $required ? ' required' : '';
    $r   = $ratio ?: '0';
    $acc = 'accept="image/jpeg,image/png,image/webp,image/gif"';
    echo '<div class="img-field-wrap" data-img-field data-ratio="' . $r . '">'
       // tabs
       . '<div class="img-tabs">'
       . '<button type="button" class="img-tab active" data-tab="url">🔗 URL</button>'
       . '<button type="button" class="img-tab" data-tab="auto">⚡ Auto Crop</button>'
       . '<button type="button" class="img-tab" data-tab="manual">✂️ Manual Crop</button>'
       . '</div>'
       // URL panel
       . '<div data-panel="url">'
       . '<input type="text" name="' . $n . '" value="' . $v . '" placeholder="https://..."' . $req . ' class="img-url-input">'
       . '</div>'
       // Auto panel
       . '<div data-panel="auto" hidden>'
       . '<label class="btn btn-ghost btn-sm img-upload-label">เลือกไฟล์'
       . '<input type="file" ' . $acc . ' class="img-auto-file"></label>'
       . '<div class="img-hint">ตัดรูปจากกึ่งกลางอัตโนมัติ แล้วอัปโหลด</div>'
       . '</div>'
       // Manual panel
       . '<div data-panel="manual" hidden>'
       . '<label class="btn btn-ghost btn-sm img-upload-label">เลือกไฟล์'
       . '<input type="file" ' . $acc . ' class="img-manual-file"></label>'
       . '<div class="img-hint">เปิดหน้าต่าง Crop เพื่อปรับเองก่อนอัปโหลด</div>'
       . '</div>'
       // shared
       . '<div class="img-upload-status"></div>'
       . '<div class="img-preview-wrap"></div>'
       . '</div>';
}

function imageBatchField(array $data): void
{
    $img = htmlspecialchars($data['image'] ?? '');
    $fb  = htmlspecialchars($data['image_facebook'] ?? '');
    $ig  = htmlspecialchars($data['image_instagram'] ?? '');
    $acc = 'accept="image/jpeg,image/png,image/webp,image/gif"';
    echo '<div class="form-section" data-img-batch>'
       . '<div style="font-size:.85rem;font-weight:600;color:var(--text);margin-bottom:.85rem">รูปภาพประกอบ — ไม่บังคับ ใส่เฉพาะที่ต้องการ</div>'
       // tabs
       . '<div class="img-tabs">'
       . '<button type="button" class="img-tab active" data-tab="url">🔗 URL</button>'
       . '<button type="button" class="img-tab" data-tab="auto">⚡ Auto Crop</button>'
       . '<button type="button" class="img-tab" data-tab="manual">✂️ Manual Crop</button>'
       . '</div>'
       // URL panel — inputs with name always exist here (submitted even when hidden)
       . '<div data-panel="url" class="batch-url-fields">'
       . '<label style="margin-bottom:0"><span>Web <small style="font-weight:400;text-transform:none;color:var(--muted)">— 1200×800 (3:2)</small></span>'
       . '<input type="text" name="image" value="' . $img . '" placeholder="https://..." class="url-input-image"></label>'
       . '<label style="margin-bottom:0"><span>Facebook <small style="font-weight:400;text-transform:none;color:var(--muted)">— 1200×630 (1.91:1)</small></span>'
       . '<input type="text" name="image_facebook" value="' . $fb . '" placeholder="https://..." class="url-input-image_facebook"></label>'
       . '<label style="margin-bottom:0"><span>Instagram <small style="font-weight:400;text-transform:none;color:var(--muted)">— 1080×1080 (1:1)</small></span>'
       . '<input type="text" name="image_instagram" value="' . $ig . '" placeholder="https://..." class="url-input-image_instagram"></label>'
       . '</div>'
       // Auto panel
       . '<div data-panel="auto" hidden>'
       . '<label class="btn btn-ghost btn-sm img-upload-label">เลือกไฟล์รูปต้นฉบับ'
       . '<input type="file" ' . $acc . ' class="batch-auto-file"></label>'
       . '<div class="img-hint">ระบบ crop กึ่งกลางอัตโนมัติสำหรับทั้ง 3 รูปแบบ แล้วอัปโหลดพร้อมกัน</div>'
       . '</div>'
       // Manual panel
       . '<div data-panel="manual" hidden>'
       . '<label class="btn btn-ghost btn-sm img-upload-label">เลือกไฟล์รูปต้นฉบับ'
       . '<input type="file" ' . $acc . ' class="batch-manual-file"></label>'
       . '<div class="img-hint">จะเปิดหน้าต่าง Crop ให้ปรับเองตามลำดับ: Web → Facebook → Instagram</div>'
       . '</div>'
       // status
       . '<div class="img-upload-status batch-status" style="margin-top:.6rem"></div>'
       // preview slots (always visible, JS fills them)
       . '<div class="batch-previews">'
       . '<div class="batch-preview-slot"><div class="slot-label">Web</div><div class="img-preview-wrap preview-image"></div></div>'
       . '<div class="batch-preview-slot"><div class="slot-label">Facebook</div><div class="img-preview-wrap preview-image_facebook"></div></div>'
       . '<div class="batch-preview-slot"><div class="slot-label">Instagram</div><div class="img-preview-wrap preview-image_instagram"></div></div>'
       . '</div>'
       . '</div>';
}

function adminShellEnd(): void { ?>
        </div>
    </main>
</div>
<script>
(function () {
    const body = document.body;
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const closers = document.querySelectorAll('[data-sidebar-close]');

    if (!toggle) return;

    function setOpen(isOpen) {
        body.classList.toggle('admin-nav-open', isOpen);
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    toggle.addEventListener('click', function () {
        setOpen(!body.classList.contains('admin-nav-open'));
    });

    closers.forEach(function (el) {
        el.addEventListener('click', function () {
            setOpen(false);
        });
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 1080) {
            setOpen(false);
        }
    });
})();
</script>
</body>
</html>
<?php }
