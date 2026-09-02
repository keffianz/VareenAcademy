<?php
/**
 * Admin — Settings
 * Site name and support email (stored in the settings table).
 */
requireRole('admin');
$active = 'settings';
$page_title = 'Settings';
$additional_css = [appBasePath() . '/public/css/dashboard.css'];
?>
<div class="dash-shell">
<?php include __DIR__ . '/_sidebar.php'; ?>
<main class="dash-main">
  <div class="dash-topbar"><div><h1>Settings</h1><p class="dash-sub">Site-wide configuration.</p></div></div>
  <div class="dash-content">
    <div id="msg" class="admin-msg" hidden></div>
    <form id="frm" class="card admin-form">
      <h2>General</h2>
      <div class="admin-grid">
        <label>Site name<input name="site_name" id="siteName" required></label>
        <label>Support email<input name="support_email" id="supportEmail" type="email" required></label>
      </div>
      <button class="btn btn-primary" type="submit" id="btnSave">Save settings</button>
    </form>
  </div>
</main>
</div>
<style>
.admin-msg{padding:12px 14px;border-radius:8px;margin-bottom:14px;font-size:14px}
.admin-msg.ok{background:#eefaf0;color:#1e7e34;border:1px solid #bfe6c8}
.admin-msg.err{background:#fdeeee;color:#b02a2a;border:1px solid #f3c3c3}
.admin-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;margin:12px 0}
.admin-grid label{display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:600;color:#333}
.admin-grid input{padding:9px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;font-weight:400}
.admin-form h2{margin:0 0 4px;font-size:18px}
@media (max-width:640px){.admin-grid{grid-template-columns:1fr}}
</style>
<script>
window.CSRF_TOKEN='<?php echo csrfToken(); ?>';
const API='<?php echo appBasePath(); ?>/src/api/admin.php';
const $=s=>document.querySelector(s);
const msg=(t,ok=true)=>{const m=$('#msg');m.textContent=t;m.hidden=false;m.className='admin-msg '+(ok?'ok':'err');};
async function load(){
  const r=await fetch(`${API}?action=settings`,{headers:{'X-CSRF-Token':window.CSRF_TOKEN}});
  const d=await r.json();
  if(!d.success){msg(d.message||'Failed to load settings',false);return;}
  let s=d.settings||{};
  if(Array.isArray(s))s=Object.fromEntries(s.map(x=>[x.setting_key??x.key,x.setting_value??x.value]));
  $('#siteName').value=s.site_name??'';
  $('#supportEmail').value=s.support_email??'';
}
$('#frm').addEventListener('submit',async e=>{
  e.preventDefault();
  const btn=$('#btnSave');btn.disabled=true;
  try{
    const fd=new FormData();
    fd.append('site_name',$('#siteName').value.trim());
    fd.append('support_email',$('#supportEmail').value.trim());
    const r=await fetch(`${API}?action=settings_update`,{method:'POST',headers:{'X-CSRF-Token':window.CSRF_TOKEN},body:fd});
    const d=await r.json();
    msg(d.message||'Settings saved',!!d.success);
  }catch(err){msg('Network error — settings not saved',false);}
  finally{btn.disabled=false;}
});
load();
</script>
