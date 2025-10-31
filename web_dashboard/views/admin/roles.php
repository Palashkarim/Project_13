
<?php
// Admin · Roles — RBAC matrix (read-only template + editable permissions)
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin · Roles & Permissions</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/base.css">
  <link rel="stylesheet" href="/assets/css/dashboard.css">
  <link rel="stylesheet" href="/assets/css/themes/dark.css" id="theme-dark">
</head>
<body>
<div class="app-shell">
  <div class="topbar"><div class="brand"><a href="/views/dashboard.php">IoT Platform</a> · Admin / Roles</div></div>
  <div class="content">
    <div class="toolbar"><h2>Roles</h2></div>
    <div class="card">
      <div id="matrix" class="boards-grid"></div>
      <div class="meta">Changes affect new sessions. Use ACL sync to update MQTT topic permissions.</div>
      <button class="btn" id="save">Save Changes</button>
    </div>
  </div>
</div>
<script src="/assets/js/auth.js"></script>
<script>
let roles=[], perms=[], allPerms=[];
function render(){
  const box = document.getElementById('matrix'); box.innerHTML='';
  roles.forEach(role=>{
    const card = document.createElement('div'); card.className='card';
    card.innerHTML = `<h3>${role.key}</h3>`;
    const grid = document.createElement('div'); grid.style.display='grid'; grid.style.gridTemplateColumns='repeat(2,1fr)'; grid.style.gap='6px';
    const allowed = new Set((perms.find(p=>p.role===role.key)?.permissions) || []);
    allPerms.forEach(p=>{
      const id = `${role.key}_${p}`;
      const row = document.createElement('label'); row.style.display='flex'; row.style.alignItems='center'; row.style.gap='6px';
      row.innerHTML = `<input type="checkbox" id="${id}" ${allowed.has(p)?'checked':''}/> <span>${p}</span>`;
      grid.appendChild(row);
    });
    card.appendChild(grid); box.appendChild(card);
  });
}
async function load(){
  roles = await Auth.api('/api/admin/roles') || [];
  perms = await Auth.api('/api/admin/roles/permissions') || [];
  allPerms = await Auth.api('/api/admin/permissions') || ['users.read','users.write','devices.read','devices.write','mqtt.manage','billing.manage','boards.manage','widgets.manage','exports.manage'];
  render();
}
document.getElementById('save').onclick = async ()=>{
  const body = [];
  roles.forEach(r=>{
    const selected = [];
    allPerms.forEach(p=>{
      const id = `${r.key}_${p}`;
      const el = document.getElementById(id);
      if (el && el.checked) selected.push(p);
    });
    body.push({role:r.key, permissions:selected});
  });
  await Auth.api('/api/admin/roles/permissions', {method:'POST', body:JSON.stringify(body)});
  alert('Saved. Consider running ACL sync.');
};
load();
</script>
</body>
</html>
