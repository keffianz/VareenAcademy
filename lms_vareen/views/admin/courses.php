<?php
/**
 * Admin — Manage Courses
 * Activate/deactivate courses, assign teachers, enroll students.
 */
requireRole('admin');
$active = 'courses';
$page_title = 'Manage Courses';
$additional_css = [appBasePath() . '/public/css/dashboard.css'];
?>
<div class="dash-shell">
<?php include __DIR__ . '/_sidebar.php'; ?>
<main class="dash-main">
  <div class="dash-topbar"><div><h1>Manage Courses</h1><p class="dash-sub">Course status, teacher assignment, and student enrollment.</p></div></div>
  <div class="dash-content">
    <div id="msg" class="admin-msg" hidden></div>
    <div class="card table-wrap"><table class="table"><thead><tr><th>ID</th><th>Title</th><th>Teacher</th><th>Category</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead><tbody id="rows"></tbody></table></div>
    <form id="frmEnroll" class="card admin-form">
      <h2>Enroll a student</h2>
      <div class="admin-grid">
        <label>Student<select name="student_id" id="selStudent" required><option value="">Loading students…</option></select></label>
        <label>Course<select name="course_id" id="selCourse" required><option value="">Loading courses…</option></select></label>
      </div>
      <button class="btn btn-primary" type="submit">Enroll</button>
    </form>
  </div>
</main>
</div>
<style>
.admin-msg{padding:12px 14px;border-radius:8px;margin-bottom:14px;font-size:14px}
.admin-msg.ok{background:#eefaf0;color:#1e7e34;border:1px solid #bfe6c8}
.admin-msg.err{background:#fdeeee;color:#b02a2a;border:1px solid #f3c3c3}
.admin-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin:12px 0}
.admin-grid label{display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:600;color:#333}
.admin-grid select{padding:9px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;font-weight:400}
.admin-form h2{margin:0 0 4px;font-size:18px}
.table-wrap{overflow-x:auto}
.badge-ok{color:#1e7e34;font-weight:600}
.badge-off{color:#b02a2a;font-weight:600}
.row-actions{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.row-actions select{padding:6px 8px;border:1px solid #ddd;border-radius:6px;font-size:13px;max-width:170px}
@media (max-width:640px){.admin-grid{grid-template-columns:1fr}}
</style>
<script>
window.CSRF_TOKEN='<?php echo csrfToken(); ?>';
const API='<?php echo appBasePath(); ?>/src/api/admin.php';
const $=s=>document.querySelector(s);
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const msg=(t,ok=true)=>{const m=$('#msg');m.textContent=t;m.hidden=false;m.className='admin-msg '+(ok?'ok':'err');};
async function jget(q){const r=await fetch(`${API}?${q}`,{headers:{'X-CSRF-Token':window.CSRF_TOKEN}});return r.json();}
async function jpost(action,fields){const fd=new FormData();Object.entries(fields).forEach(([k,v])=>fd.append(k,v));const r=await fetch(`${API}?action=${encodeURIComponent(action)}`,{method:'POST',headers:{'X-CSRF-Token':window.CSRF_TOKEN},body:fd});return r.json();}
let courses=[];
async function load(){
  const d=await jget(new URLSearchParams({action:'courses_list'}));
  const tb=$('#rows');tb.innerHTML='';
  if(!d.success){msg(d.message||'Failed to load courses',false);return;}
  courses=d.courses||[];
  courses.forEach(c=>{
    const tr=document.createElement('tr');
    tr.innerHTML=`<td>${esc(c.id)}</td>
      <td>${esc(c.title)}</td>
      <td>${esc(c.teacher_name??'—')}</td>
      <td>${esc(c.category??'—')}</td>
      <td>${(+c.price>0)?esc(c.price):'Free'}</td>
      <td>${+c.is_active?'<span class="badge-ok">Active</span>':'<span class="badge-off">Inactive</span>'}</td>
      <td><div class="row-actions">
        <button class="btn btn-sm ${+c.is_active?'':'btn-primary'}" type="button" data-active="${esc(c.id)}" data-next="${+c.is_active?'0':'1'}">${+c.is_active?'Deactivate':'Activate'}</button>
        <select data-assign="${esc(c.id)}"><option value="">Assign teacher…</option>${(window._teachers||[]).map(t=>`<option value="${esc(t.id)}"${+c.teacher_id===+t.id?' selected':''}>${esc(((t.first_name||'')+' '+(t.last_name||'')).trim())}</option>`).join('')}</select>
        <button class="btn btn-sm" type="button" data-assign-save="${esc(c.id)}">Save</button>
      </div></td>`;
    tb.appendChild(tr);
  });
  if(!courses.length)tb.innerHTML='<tr><td colspan="7">No courses yet.</td></tr>';
  const sel=$('#selCourse');
  sel.innerHTML='<option value="">Choose course…</option>'+courses.map(c=>`<option value="${esc(c.id)}">${esc(c.title)}</option>`).join('');
}
async function loadPeople(){
  const t=await jget(new URLSearchParams({action:'list_users',role:'teacher'}));
  window._teachers=t.users||[];
  const s=await jget(new URLSearchParams({action:'list_users',role:'student'}));
  $('#selStudent').innerHTML='<option value="">Choose student…</option>'+(s.users||[]).map(u=>`<option value="${esc(u.id)}">${esc(((u.first_name||'')+' '+(u.last_name||'')).trim())} (${esc(u.email)})</option>`).join('');
  await load();
}
$('#rows').addEventListener('click',async e=>{
  const act=e.target.closest('[data-active]'),asg=e.target.closest('[data-assign-save]');
  let d;
  if(act){
    d=await jpost('course_set_active',{course_id:act.dataset.active,is_active:act.dataset.next});
  }else if(asg){
    const sel=document.querySelector(`[data-assign="${asg.dataset.assignSave}"]`);
    if(!sel.value){msg('Choose a teacher first',false);return;}
    d=await jpost('course_assign_teacher',{course_id:asg.dataset.assignSave,teacher_id:sel.value});
  }else return;
  msg(d.message||'Saved',!!d.success);
  await load();
});
$('#frmEnroll').addEventListener('submit',async e=>{
  e.preventDefault();
  const f=e.target;
  if(!f.student_id.value||!f.course_id.value){msg('Choose both a student and a course',false);return;}
  const d=await jpost('enroll_student',{student_id:f.student_id.value,course_id:f.course_id.value});
  msg(d.message||'Enrolled',!!d.success);
});
loadPeople();
</script>
