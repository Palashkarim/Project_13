<?php
// Admin · MQTT Servers — register Mosquitto instances & health check & ACL sync
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin · MQTT Servers</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/base.css">
  <link rel="stylesheet" href="/assets/css/dashboard.css">
  <link rel="stylesheet" href="/assets/css/themes/dark.css" id="theme-dark">
</head>
<body>
<div class="app-shell">
  <div class="topbar"><div class="brand"><a href="/views/dashboard.php">IoT Platform</a> · Admin / MQTT Servers</div></div>
  <div class="content">
    <div class="toolbar">
      <h2>MQTT Brokers</h2>
      <div class="row">
        <input class="input" id="host" placeholder="Host/IP">
        <input class="input" id="ws_port" placeholder="WS Port (9001)">
        <input class="input" id="tcp_port" placeholder="TCP Port (1883)">
        <button class="btn" id="add">Add</button>
      </div>
    </div>

    <div class="card">
      <h3>Registered Brokers</h3>
      <div id="list"></div>
    </div>
  </div>
</div>
<script src="/assets/js/auth.js"></script>
<script>
async function load(){
  const brokers = await Auth.api('/api/mqtt/servers') || [];
  const box = document.getElementById('list'); box.innerHTML='';
  brokers.forEach(b=>{
    const row = document.createElement('div'); row.className='row'; row.style.gap='8px';
    row.innerHTML = `
      <span class="badge">#${b.id}</span>
      <span class="badge">${b.host}</span>
      <span class="badge">ws:${b.ws_port} tcp:${b.tcp_port}</span>
      <button class="btn" data-id="${b.id}" data-act="health">Health</button>
      <button class="btn" data-id="${b.id}" data-act="acls">Sync ACL</button>
      <button class="btn" data-id="${b.id}" data-act="del">Delete</button>
    `;
    row.addEventListener('click', async (e)=>{
      const bttn = e.target.closest('button'); if(!bttn) return;
      const id = bttn.getAttribute('data-id'), act=bttn.getAttribute('data-act');
      if (act==='health'){
        const h = await Auth.api('/api/mqtt/servers/'+id+'/health');
        alert('Health: ' + JSON.stringify(h));
      } else if (act==='acls'){
        await Auth.api('/api/mqtt/servers/'+id+'/sync_acl', {method:'POST'});
        alert('ACL sync queued');
      } else if (act==='del'){
        if (confirm('Delete broker #' + id + '?')){
          await Auth.api('/api/mqtt/servers/'+id, {method:'DELETE'});
          load();
        }
      }
    });
    box.appendChild(row);
  });
}
document.getElementById('add').onclick = async ()=>{
  const host = document.getElementById('host').value.trim();
  const ws_port = Number(document.getElementById('ws_port').value)||9001;
  const tcp_port= Number(document.getElementById('tcp_port').value)||1883;
  if(!host) return alert('Host required');
  await Auth.api('/api/mqtt/servers', {method:'POST', body: JSON.stringify({host, ws_port, tcp_port})});
  load();
};
load();
</script>
</body>
</html>
