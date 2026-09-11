<?php
/**
 * Admin — Contact & Training Inbox
 * Submissions from the marketing site: contact form (contact_messages)
 * and the Training Application form (applications).
 */
requireRole('admin');
$admin_active = 'inbox';
$page_title = 'Contact & Training Inbox';
$additional_css = [appBasePath() . '/public/css/dashboard.css'];
?>
<div class="dashboard-wrapper">
<?php include __DIR__ . '/_sidebar.php'; ?>
<main class="dashboard-content">
  <div class="dashboard-topbar"><div><h1>Contact &amp; Training Inbox</h1><p class="dash-sub">Submissions from the website contact form and Training Application form.</p></div></div>
  <div class="dashboard-section">
    <div class="admin-toolbar">
      <div class="tabs">
        <button class="tab active" type="button" data-tab="messages">Contact Messages</button>
        <button class="tab" type="button" data-tab="training">Training Applications</button>
      </div>
      <select id="fStatus"></select>
    </div>
    <div id="msg" class="admin-msg" hidden></div>
    <div id="list"></div>
  </div>
</main>
</div>
<style>
.admin-toolbar{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center}
.tabs{display:flex;gap:6px}
.tab{padding:9px 16px;border:1px solid #ddd;background:#fff;border-radius:8px;font-size:14px;cursor:pointer}
.tab.active{background:#667eea;color:#fff;border-color:#667eea}
.admin-toolbar select{padding:9px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px}
.admin-msg{padding:12px 14px;border-radius:8px;margin-bottom:14px;font-size:14px}
.admin-msg.ok{background:#eefaf0;color:#1e7e34;border:1px solid #bfe6c8}
.admin-msg.err{background:#fdeeee;color:#b02a2a;border:1px solid #f3c3c3}
.inbox-card{background:#fff;border:1px solid #e6e6ef;border-radius:12px;padding:18px;margin-bottom:14px}
.inbox-head{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:baseline}
.inbox-head h3{margin:0;font-size:16px}
.inbox-meta{color:#667;font-size:13px}
.inbox-body{white-space:pre-wrap;color:#444;font-size:14px;margin:10px 0}
.inbox-actions{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}
.inbox-actions a,.inbox-actions button{font-size:13px}
.badge-unread{background:#fff3cd;color:#8a6d1a;border:1px solid #f0dc9e}
.badge-read{background:#eef2f7;color:#445;border:1px solid #d5dbe5}
.badge-replied{background:#eefaf0;color:#1e7e34;border:1px solid #bfe6c8}
.badge-pending{background:#fff3cd;color:#8a6d1a;border:1px solid #f0dc9e}
.badge-approved{background:#eefaf0;color:#1e7e34;border:1px solid #bfe6c8}
.badge-rejected{background:#fdeeee;color:#b02a2a;border:1px solid #f3c3c3}
.badge-completed{background:#eef2f7;color:#445;border:1px solid #d5dbe5}
.status-badge{font-size:12px;padding:3px 10px;border-radius:999px;font-weight:700}
.empty{color:#667;padding:24px;text-align:center}
</style>
<script>
window.CSRF_TOKEN='<?php echo csrfToken(); ?>';
const API='<?php echo appBasePath(); ?>/src/api/admin.php';
const $=s=>document.querySelector(s);
const esc=s=>String(s??'').replace(/[&<>\"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#39;'}[c]));
const msg=(t,ok=true)=>{const m=$('#msg');m.textContent=t;m.hidden=false;m.className='admin-msg '+(ok?'ok':'err');};

let currentTab='messages';
const STATUS_OPTS={
  messages:[['','All statuses'],['unread','Unread'],['read','Read'],['replied','Replied']],
  training:[['','All statuses'],['pending','Pending'],['approved','Approved'],['rejected','Rejected'],['completed','Completed']]
};
const ACTIONS={
  messages:[['read','Mark read'],['replied','Mark replied'],['unread','Mark unread']],
  training:[['approved','Approve'],['rejected','Reject'],['completed','Mark completed'],['pending','Back to pending']]
};

async function jget(q){const r=await fetch(`${API}?${q}`,{headers:{'X-CSRF-Token':window.CSRF_TOKEN}});return r.json();}
async function jpost(action,fields){const fd=new FormData();Object.entries(fields).forEach(([k,v])=>fd.append(k,v));const r=await fetch(`${API}?action=${encodeURIComponent(action)}`,{method:'POST',headers:{'X-CSRF-Token':window.CSRF_TOKEN},body:fd});return r.json();}

function renderStatusOptions(){
  const sel=$('#fStatus');
  sel.innerHTML=STATUS_OPTS[currentTab].map(([v,l])=>`<option value="${esc(v)}">${esc(l)}</option>`).join('');
}

async function load(){
  const action=currentTab==='messages'?'messages_list':'training_apps_list';
  const q=new URLSearchParams({action});
  const st=$('#fStatus').value;
  if(st)q.set('status',st);
  const d=await jget(q);
  const list=$('#list');list.innerHTML='';
  if(!d.success){msg(d.message||'Failed to load',false);return;}
  const rows=currentTab==='messages'?(d.messages||[]):(d.applications||[]);
  if(!rows.length){list.innerHTML='<div class="empty">Nothing here yet.</div>';return;}
  rows.forEach(r=>{
    const card=document.createElement('div');
    card.className='inbox-card';
    if(currentTab==='messages'){
      const status=String(r.status||'unread');
      card.innerHTML=`<div class="inbox-head">
          <h3>${esc(r.subject||'(no subject)')} <span class="status-badge badge-${esc(status)}">${esc(status)}</span></h3>
          <span class="inbox-meta">${esc(r.name??'')} &lt;${esc(r.email??'')}&gt; · ${esc(String(r.created_at||'').slice(0,16))}</span>
        </div>
        ${r.phone?`<p class="inbox-meta">Phone: ${esc(r.phone)}</p>`:''}
        <p class="inbox-body">${esc(r.message??'')}</p>
        <div class="inbox-actions">
          <a class="btn btn-primary btn-sm" href="mailto:${esc(r.email??'')}?subject=Re: ${encodeURIComponent(r.subject||'Your message')}">Reply by email</a>
          ${ACTIONS.messages.filter(([v])=>v!==status).map(([v,l])=>`<button class="btn btn-sm" type="button" data-id="${esc(r.id)}" data-status="${esc(v)}">${esc(l)}</button>`).join('')}
        </div>`;
    }else{
      const status=String(r.status||'pending');
      const name=esc((((r.first_name||'')+' '+(r.last_name||'')).trim())||'—');
      card.innerHTML=`<div class="inbox-head">
          <h3>${name} — ${esc(r.program??'Training')} <span class="status-badge badge-${esc(status)}">${esc(status)}</span></h3>
          <span class="inbox-meta">${esc(r.email??'')} · applied ${esc(String(r.created_at||'').slice(0,10))}</span>
        </div>
        <p class="inbox-meta">Phone: ${esc(r.phone??'—')} · Preferred start: ${esc(r.start_date??'—')}</p>
        <div class="inbox-actions">
          <a class="btn btn-primary btn-sm" href="mailto:${esc(r.email??'')}?subject=${encodeURIComponent('Your Vareen Academy training application')}">Reply by email</a>
          ${ACTIONS.training.filter(([v])=>v!==status).map(([v,l])=>`<button class="btn btn-sm" type="button" data-id="${esc(r.id)}" data-status="${esc(v)}">${esc(l)}</button>`).join('')}
        </div>`;
    }
    list.appendChild(card);
  });
}

document.querySelectorAll('.tab').forEach(btn=>{
  btn.addEventListener('click',()=>{
    document.querySelectorAll('.tab').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    currentTab=btn.dataset.tab;
    renderStatusOptions();
    load();
  });
});
$('#fStatus').addEventListener('change',load);
$('#list').addEventListener('click',async e=>{
  const b=e.target.closest('button[data-id]');
  if(!b)return;
  b.disabled=true;
  const action=currentTab==='messages'?'message_set_status':'training_app_set_status';
  const fields=currentTab==='messages'
    ?{message_id:b.dataset.id,status:b.dataset.status}
    :{application_id:b.dataset.id,status:b.dataset.status};
  const d=await jpost(action,fields);
  msg(d.message||(d.success?'Updated':'Failed'),!!d.success);
  await load();
});
renderStatusOptions();
load();
</script>