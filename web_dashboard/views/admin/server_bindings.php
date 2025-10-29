<?php
// Admin · Server Bindings — choose which MQTT broker & DB shard a user uses (for load-balance)
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin · Server Bindings</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/base.css">
  <link rel="stylesheet" href="/assets/css/dashboard.css">
  <link rel="stylesheet" href="/assets/css/themes/dark.css" id="theme-dark">
</head>
<body>
<div class="app-shell">
  <div class="topbar"><div class="brand"><a href="/views/dashboard.php">IoT Platform</a> · Admin / Server Bindings</div></div>
  <div class="content">
    <div class="toolbar">
      <h2>Bind User to Servers</h2>
      <div class="row" style="gap:8px;flex-wrap:wrap">
        <input class="input" id="user" placeholder="User id/email">
        <select class="input" id="mqtt"></select>
        <select class="input" id="dbs"></select>
        <button class="btn" id="bind">Bind</button>
      </div>
    </div>

    <div class="card">
      <h3>Bindings</h3>
      <div id="list"></div>
    </div>
  </div>
</div>
<script src="/assets/js/auth.js"></script>
<script>
let brokers=[], nodes=[];
async function loadOptions(){
  brokers = await Auth.api('/api/mqtt/servers') || [];
  nodes   = await Auth.api('/api/db/servers') || [];
  const selM = document.getElementById('mqtt'); selM.innerHTML = brokers.map(b=>`<option value="${b.id}">${b.host}:${b.ws_port}</option>`).join('');
  const selD = document.getElementById('dbs');  selD.innerHTML = nodes.map(n=>`<option value="${n.id}">${n.host}:${n.port}/${n.database} (${n.db_role})</option>`).join('');
}
async function loadBindings(){
  const arr = await Auth.api('/api/admin/bindings') || [];
  const box = document.getElementById('list'); box.innerHTML='';
  arr.forEach(b=>{
    const row=document.createElement('div'); row.className='row'; row.style.gap='8px';
    row.innerHTML = `
      <span class="badge">user:${b.user_id}</span>
      <span class="badge">mqtt:${b.mqtt_server?.host||'-'}</span>
      <span class="badge">db:${b.db_server?.host||'-'}</span>
      <button class="btn" data-id="${b.user_id}">Unbind</button>
    `;
    row.querySelector('button').onclick = async ()=>{
      await Auth.api('/api/admin/bindings/'+b.user_id, {method:'DELETE'});
      loadBindings();
    };
    box.appendChild(row);
  });
}
document.getElementById('bind').onclick = async ()=>{
  const user = document.getElementById('user').value.trim();
  const mqtt_id = Number(document.getElementById('mqtt').value);
  const db_id   = Number(document.getElementById('dbs').value);
  if(!user) return alert('User id/email required');
  await Auth.api('/api/admin/bindings', {method:'POST', body: JSON.stringify({user, mqtt_server_id:mqtt_id, db_server_id:db_id})});
  loadBindings();
};
loadOptions(); loadBindings();
</script>
</body>
</html>

