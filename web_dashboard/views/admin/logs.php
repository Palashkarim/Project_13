<?php
// Admin · Logs — audit trail and security events with filters/pagination
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin · Logs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/base.css">
  <link rel="stylesheet" href="/assets/css/dashboard.css">
  <link rel="stylesheet" href="/assets/css/themes/dark.css" id="theme-dark">
</head>
<body>
<div class="app-shell">
  <div class="topbar"><div class="brand"><a href="/views/dashboard.php">IoT Platform</a> · Admin / Logs</div></div>
  <div class="content">
    <div class="toolbar">
      <h2>Audit & Security Logs</h2>
      <div class="row" style="gap:8px;flex-wrap:wrap">
        <input class="input" id="q" placeholder="Contains text">
        <select class="input" id="type"><option value="">Any</option><option>login</option><option>device_cmd</option><option>acl</option><option>export</option></select>
        <button class="btn" id="load">Load</button>
      </div>
    </div>
    <div class="card"><div id="list"></div></div>
  </div>
</div>
<script src="/assets/js/auth.js"></script>
<script>
async function load(){
  const q = document.getElementById('q').value.trim();
  const t = document.getElementById('type').value;
  const items = await Auth.api('/api/admin/logs?q='+encodeURIComponent(q)+'&type='+encodeURIComponent(t)) || [];
  const box = document.getElementById('list'); box.innerHTML='';
  items.forEach(l=>{
    const row=document.createElement('div'); row.className='row'; row.style.gap='8px';
    row.innerHTML = `
      <span class="badge">${l.ts}</span>
      <span class="badge">${l.user_id || '-'}</span>
      <span class="badge">${l.type}</span>
      <span class="badge">${l.message}</span>
    `;
    box.appendChild(row);
  });
}
document.getElementById('load').onclick = load;
load();
</script>
</body>
</html>
