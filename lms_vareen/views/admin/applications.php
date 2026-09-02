<?php
/**
 * Admin — Instructor Applications
 * Review queue for "Become an Instructor" submissions.
 */
requireRole('admin');
$active = 'applications';
$page_title = 'Instructor Applications';
$additional_css = [appBasePath() . '/public/css/dashboard.css'];
?>
<div class="dash-shell">
<?php include __DIR__ . '/_sidebar.php'; ?>
<main class="dash-main">
  <div class="dash-topbar"><div><h1>Instructor Applications</h1><p class="dash-sub">Review "Become an Instructor" submissions.</p></div></div>
  <div class="dash-content">
    <div class="admin-toolbar">
      <select id="fStatus"><option value="pending">Pending</option><option value="approved">Approved</option><option value="rejected">Rejected</option><option value="">All</option></select>
    </div>
    <div id="msg" class="admin-msg" hidden></div>
    <div id="list"></div>
  </div>
</main>
</div>
<style>
.admin-toolbar{display:flex;gap:10px;margin-bottom:16px}
.admin-toolbar select{padding:9px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px}
.admin-msg{padding:12px 14px;border-radius:8px;margin-bottom:14px;font-size:14px}
.admin-msg.ok{background:#eefaf0;color:#1e7e34;border:1px solid #bfe6c8}
.admin-msg.err{background:#fdeeee;color:#b02a2a;border:1px solid #f3c3c3}
.app-card{background:#fff;border:1px solid #e6e6ef;border-radius:12px;padding:18px;margin-bottom:14px}
.app-head{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:baseline}
.app-head h3{margin:0;font-size:16px}
.app-meta{color:#667;font-size:13px}
.app-spec{display:inline-block;background:#eef0ff;color:#4a54b0;border-radius:999px;padding:3px 10px;font-size:12px;font-weight:600;margin:6px 0}
.app-links{display:flex;gap:12px;flex-wrap:wrap;margin:8px 0}
.app-links a{color:#4a54b0;font-size:13px;word-break:break-all}
.app-info{white-space:pre-wrap;color:#444;font-size:14px;margin:8px 0}
.app-actions{display:flex;gap:8px;margin-top:10px}
.badge-pending{background:#fff3cd;color:#8a6d1a;border:1px solid #f0dc9e}
.badge-approved{background:#eefaf0;color:#1e7e34;border:1px solid #bfe6c8}
.badge-rejected{background:#fdeeee;color:#b02a2a;border:1px solid #f3c3c3}
.status-badge{font-size:12px;padding:3px 10px;border-radius:999px;font-weight:700}
.empty{color:#667;padding:24px;text-align:center}
</style>
<script>
window.CSRF_TOKEN='<?php echo csrfToken(); ?>';
const API='<?php echo appBasePath(); ?>/src/api/admin.php';
const $=s=>document.querySelector(s);
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const msg=(t,ok=true)=>{const m=$('#msg');m.textContent=t;m.hidden=false;m.className='admin-msg '+(ok?'ok':'err');};
async function jget(q){const r=await fetch(`${API}?${q}`,{headers:{'X-CSRF-Token':window.CSRF_TOKEN}});return r.json();}
async function jpost(action,fields){const fd=new FormData();Object.entries(fields).forEach(([k,v])=>fd.append(k,v));const r=await fetch(`${API}?action=${encodeURIComponent(action)}`,{method:'POST',headers:{'X-CSRF-Token':window.CSRF_TOKEN},body:fd});return r.json();}
async function load(){
  const q=new URLSearchParams({action:'applications_list'});
  const st=$('#fStatus').value;
  if(st)q.set('status',st);
  const d=await jget(q);
  const list=$('#list');list.innerHTML='';
  if(!d.success){msg(d.message||'Failed to load applications',false);return;}
  const apps=d.applications||[];
  if(!apps.length){list.innerHTML='<div class="card empty">No applications in this view.</div>';return;}
  apps.forEach(a=>{
    const name=esc(a.name??(((a.first_name||'')+' '+(a.last_name||'')).trim()||'—'));
    const status=String(a.status||'pending');
    const card=document.createElement('div');
    card.className='app-card';
    card.innerHTML=`<div class="app-head">
        <h3>${name} <span class="status-badge badge-${esc(status)}">${esc(status)}</span></h3>
        <span class="app-meta">${esc(a.email??'')} · applied ${esc(String(a.created_at||'').slice(0,10))}</span>
      </div>
      <span class="app-spec">${esc(a.specialization??'General')}</span>
      <p class="app-meta">Experience: ${esc(a.experience_years??'—')} year(s)</p>
      <div class="app-links">
        ${a.cv_url?`<a href="${esc(a.cv_url)}" target="_blank" rel="noopener">CV ↗</a>`:''}
        ${a.portfolio_url?`<a href="${esc(a.portfolio_url)}" target="_blank" rel="noopener">Portfolio ↗</a>`:''}
        ${a.sample_lesson_url?`<a href="${esc(a.sample_lesson_url)}" target="_blank" rel="noopener">Sample lesson ↗</a>`:''}
      </div>
      ${a.additional_info?`<p class="app-info">${esc(a.additional_info)}</p>`:''}
      ${status==='pending'?`<div class="app-actions">
        <button class="btn btn-primary btn-sm" type="button" data-review="${esc(a.id)}" data-status="approved">Approve</button>
        <button class="btn btn-sm" type="button" data-review="${esc(a.id)}" data-status="rejected">Reject</button>
      </div>`:''}`;
    list.appendChild(card);
  });
}
$('#list').addEventListener('click',async e=>{
  const b=e.target.closest('[data-review]');
  if(!b)return;
  b.disabled=true;
  const d=await jpost('application_review',{application_id:b.dataset.review,status:b.dataset.status});
  msg(d.message||('Application '+b.dataset.status),!!d.success);
  await load();
});
$('#fStatus').addEventListener('change',load);
load();
</script>
