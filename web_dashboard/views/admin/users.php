<?php
// Admin · Users — CRUD + role assignment + quick actions
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin · Users</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/base.css">
  <link rel="stylesheet" href="/assets/css/dashboard.css">
  <link rel="stylesheet" href="/assets/css/themes/dark.css" id="theme-dark">
</head>
<body>
<div class="app-shell">
  <div class="topbar">
    <div class="brand"><a href="/views/dashboard.php">IoT Platform</a> · Admin / Users</div>
    <div class="right">
      <a class="btn" href="/views/admin/settings.php">Settings</a>
      <a class="btn" href="/views/admin/logs.php">Logs</a>
    </div>
  </div>
  <div class="content">
    <div class="toolbar">
      <h2>Users</h2>
      <div class="row">
        <input class="input" id="q" placeholder="Search email/id">
        <button class="btn" id="search">Search</button>
        <button class="btn primary" id="create">Create</button>
      </div>
    </div>

    <div class="card">
      <div class="row" style="gap:8px;flex-wrap:wrap">
        <input class="input" id="email" placeholder="Email">
        <input class="input" id="name" placeholder="Display name">
        <select class="input" id="role">
          <option value="super_admin">Super Admin</option>
          <option value="admin">Admin</option>
          <option value="technician">Technician</option>
          <option value="sales">Sales</option>
          <option value="super_user">Super User</option>
          <option value="sub_user">Sub User</option>
        </select>
        <button class="btn" id="invite">Invite</button>
        <div class="meta">Users cannot self-register. Admin/Technician creates and sends credentials.</div>
      </div>
    </div>

    <div class="card" style="margin-top:12px">
      <h3>All Users</h3>
      <div id="table"></div>
    </div>
  </div>
</div>

<script src="/assets/js/auth.js"></script>
<script>
let users=[];
function row(u){
  const r = document.createElement('div'); r.className='row'; r.style.gap='8px'; r.style.flexWrap='wrap';
  r.innerHTML = `
    <span class="badge">#${u.id}</span>
    <span class="badge">${u.email}</span>
    <span class="badge">${u.display_name||''}</span>
    <span class="badge">${u.role||''}</span>
    <button class="btn" data-act="role"  data-id="${u.id}">Change Role</button>
    <button class="btn" data-act="impersonate" data-id="${u.id}">Impersonate</button>
    <button class="btn" data-act="reset" data-id="${u.id}">Reset Password</button>
    <button class="btn" data-act="delete" data-id="${u.id}">Delete</button>
  `;
  r.addEventListener('click', async (e)=>{
    const b = e.target.closest('button'); if(!b) return;
    const id = b.getAttribute('data-id');
    const act= b.getAttribute('data-act');
    if (act==='role'){
      const role = prompt('Enter role (super_admin, admin, technician, sales, super_user, sub_user):', u.role||'');
      if (!role) return;
      await Auth.api('/api/admin/users/'+id+'/role', {method:'POST', body: JSON.stringify({role})});
      load();
    }
    if (act==='impersonate'){
      const j = await Auth.api('/api/admin/users/'+id+'/impersonate', {method:'POST'});
      alert('Use generated token in dev tools or dedicated admin feature. (Server-side)');
    }
    if (act==='reset'){
      await Auth.api('/api/admin/users/'+id+'/reset_password', {method:'POST'});
      alert('Reset email/SMS queued.');
    }
    if (act==='delete'){
      if (confirm('Delete user #' + id + '?')){
        await Auth.api('/api/admin/users/'+id, {method:'DELETE'});
        load();
      }
    }
  });
  return r;
}
async function load(){
  let list = await Auth.api('/api/admin/users'); users = list||[];
  const box = document.getElementById('table'); box.innerHTML='';
  users.forEach(u=> box.appendChild(row(u)));
}
document.getElementById('search').onclick = ()=>{
  const q = document.getElementById('q').value.trim().toLowerCase();
  const box = document.getElementById('table'); box.innerHTML='';
  users.filter(u=> (u.email||'').toLowerCase().includes(q) || String(u.id).includes(q)).forEach(u=> box.appendChild(row(u)));
};
document.getElementById('invite').onclick = async ()=>{
  const email = document.getElementById('email').value.trim();
  const name  = document.getElementById('name').value.trim();
  const role  = document.getElementById('role').value;
  if(!email || !role){ alert('Email & role required'); return; }
  const r = await Auth.api('/api/admin/users', {method:'POST', body: JSON.stringify({email, display_name:name, role})});
  alert('User created. Credentials sent.'); load();
};
document.getElementById('create').onclick = ()=> document.getElementById('email').focus();
load();
</script>
</body>
</html>
