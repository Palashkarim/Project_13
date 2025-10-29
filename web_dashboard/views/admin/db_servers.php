<?php
// Admin · DB Servers — register Postgres/Timescale shards and mark primary/replica
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin · DB Servers</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/base.css">
  <link rel="stylesheet" href="/assets/css/dashboard.css">
  <link rel="stylesheet" href="/assets/css/themes/dark.css" id="theme-dark">
</head>
<body>
<div class="app-shell">
  <div class="topbar"><div class="brand"><a href="/views/dashboard.php">IoT Platform</a> · Admin / DB Servers</div></div>
  <div class="content">
    <div class="toolbar">
      <h2>Database Nodes</h2>
      <div class="row">
        <input class="input" id="host" placeholder="Host/IP">
        <input class="input" id="port" placeholder="5432">
        <input class="input" id="db" placeholder="Database">
        <input class="input" id="user" placeholder="DB User">
        <input class="input" id="pwd" placeholder="DB Pass">
        <select class="input" id="role"><option value="primary">primary</option><option value="replica">replica</option></select>
        <button class="btn" id="add">Add</button>
      </div>
    </div>

    <div class="card">
      <h3>Registered Nodes</h3>
      <div id="list"></div>
    </div>
  </div>
</div>
<script src="/assets/js/auth.js"></script>
<script>
async function load(){
  const nodes = await Auth.api('/api/db/servers') || [];
  const box = document.getElementById('list'); box.innerHTML='';
  nodes.forEach(n=>{
    const row = document.createElement('div'); row.className='row'; row.style.gap='8px';
    row.innerHTML = `
      <span class="badge">#${n.id}</span>
      <span class="badge">${n.host}:${n.port}</span>
      <span class="badge">${n.database}</span>
      <span class="badge">${n.db_role}</span>
      <button class="btn" data-id="${n.id}" data-act="health">Health</button>
      <button class="btn" data-id="${n.id}" data-act="del">Delete</button>
    `;
    row.addEventListener('click', async (e)=>{
      const b = e.target.closest('button'); if(!b) return;
      const id = b.getAttribute('data-id'), act=b.getAttribute('data-act');
      if (act==='health'){
        const h = await Auth.api('/api/db/servers/'+id+'/health');
        alert('Health: ' + JSON.stringify(h));
      } else if (act==='del'){
        if (confirm('Delete DB node #' + id + '?')){ await Auth.api('/api/db/servers/'+id, {method:'DELETE'}); load(); }
      }
    });
    box.appendChild(row);
  });
}
document.getElementById('add').onclick = async ()=>{
  const body = {
    host: document.getElementById('host').value.trim(),
    port: Number(document.getElementById('port').value)||5432,
    database: document.getElementById('db').value.trim(),
    username: document.getElementById('user').value.trim(),
    password: document.getElementById('pwd').value,
    db_role: document.getElementById('role').value
  };
  if(!body.host || !body.database || !body.username) return alert('Missing fields');
  await Auth.api('/api/db/servers', {method:'POST', body: JSON.stringify(body)});
  load();
};
load();
</script>
</body>
</html>
