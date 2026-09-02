<?php
/**
 * Admin — Certificate Management
 * List issued certificates, open public verification, revoke.
 */
requireRole('admin');
$active = 'certificates';
$page_title = 'Certificates';
$additional_css = [appBasePath() . '/public/css/dashboard.css'];
?>
<div class="dash-shell">
<?php include __DIR__ . '/_sidebar.php'; ?>
<main class="dash-main">
  <div class="dash-topbar"><div><h1>Certificates</h1><p class="dash-sub">Issued certificates and verification management.</p></div></div>
  <div class="dash-content">
    <div id="msg" class="admin-msg" hidden></div>
    <div class="card table-wrap"><table class="table"><thead><tr><th>Certificate ID</th><th>Student</th><th>Course</th><th>Issued</th><th>Status</th><th>Actions</th></tr></thead><tbody id="rows"></tbody></table></div>
  </div>
</main>
</div>
<style>
.admin-msg{padding:12px 14px;border-radius:8px;margin-bottom:14px;font-size:14px}
.admin-msg.ok{background:#eefaf0;color:#1e7e34;border:1px solid #bfe6c8}
.admin-msg.err{background:#fdeeee;color:#b02a2a;border:1px solid #f3c3c3}
.table-wrap{overflow-x:auto}
.badge-valid{color:#1e7e34;font-weight:600}
.badge-revoked{color:#b02a2a;font-weight:600}
.mono{font-family:Consolas,monospace;font-size:13px}
.code-link{color:#4a54b0;text-decoration:none}
.code-link:hover{text-decoration:underline}
</style>
<script>
window.CSRF_TOKEN='<?php echo csrfToken(); ?>';
const API='<?php echo appBasePath(); ?>/src/api/admin.php';
const VERIFY_BASE='<?php echo appBasePath(); ?>/index.php?page=verify&code=';
const $=s=>document.querySelector(s);
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const msg=(t,ok=true)=>{const m=$('#msg');m.textContent=t;m.hidden=false;m.className='admin-msg '+(ok?'ok':'err');};
async function jget(q){const r=await fetch(`${API}?${q}`,{headers:{'X-CSRF-Token':window.CSRF_TOKEN}});return r.json();}
async function jpost(action,fields){const fd=new FormData();Object.entries(fields).forEach(([k,v])=>fd.append(k,v));const r=await fetch(`${API}?action=${encodeURIComponent(action)}`,{method:'POST',headers:{'X-CSRF-Token':window.CSRF_TOKEN},body:fd});return r.json();}
async function load(){
  const d=await jget(new URLSearchParams({action:'certificates_list'}));
  const tb=$('#rows');tb.innerHTML='';
  if(!d.success){msg(d.message||'Failed to load certificates',false);return;}
  const certs=d.certificates||[];
  if(!certs.length){tb.innerHTML='<tr><td colspan="6">No certificates issued yet.</td></tr>';return;}
  certs.forEach(c=>{
    const code=c.certificate_code??c.code??'';
    const revoked=+(c.is_revoked??c.revoked??0)===1;
    const tr=document.createElement('tr');
    tr.innerHTML=`<td class="mono"><a class="code-link" href="${VERIFY_BASE}${encodeURIComponent(code)}" target="_blank" rel="noopener">${esc(code)}</a></td>
      <td>${esc(c.student_name??'—')}</td>
      <td>${esc(c.course_title??c.course??'—')}</td>
      <td>${esc(String(c.issued_at??'').slice(0,10))}</td>
      <td>${revoked?'<span class="badge-revoked">Revoked</span>':'<span class="badge-valid">Valid</span>'}</td>
      <td>${revoked?'':`<button class="btn btn-sm" type="button" data-revoke="${esc(c.id)}">Revoke</button>`}</td>`;
    tb.appendChild(tr);
  });
}
$('#rows').addEventListener('click',async e=>{
  const b=e.target.closest('[data-revoke]');
  if(!b)return;
  if(!confirm('Revoke this certificate? Public verification will report it as invalid.'))return;
  b.disabled=true;
  const d=await jpost('certificate_revoke',{certificate_id:b.dataset.revoke});
  msg(d.message||'Certificate revoked',!!d.success);
  await load();
});
load();
</script>
