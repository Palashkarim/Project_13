<?php
// Admin · Widgets — global widget catalog + per-user allow-list matrix
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin · Widgets</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/base.css">
  <link rel="stylesheet" href="/assets/css/dashboard.css">
  <link rel="stylesheet" href="/assets/css/themes/dark.css" id="theme-dark">
</head>
<body>
<div class="app-shell">
  <div class="topbar"><div class="brand"><a href="/views/dashboard.php">IoT Platform</a> · Admin / Widgets</div></div>
  <div class="content">
    <div class="toolbar"><h2>Widget Library</h2></div>
    <div class="card">
      <div class="row" style="gap:8px;flex-wrap:wrap">
        <input class="input" id="key" placeholder="type key (e.g., switch)">
        <input class="input" id="title" placeholder="Default title">
        <button class="btn" id="add">Register</button>
      </div>
      <div id="lib" style="margin-top:8px"></div>
    </div>

    <div class="card" style="margin-top:12px">
      <h3>Allow Matrix (per user)</h3>
      <div class="row" style="gap:8px">
        <input class="input" id="user" placeholder="User id/email">
        <button class="btn" id="load">Load</button>
      </div>
      <div id="matrix" style="margin-top:8px"></div>
      <button class="btn" id="save">Save Allowed Widgets</button>
    </div>
  </div>
</div>
<script src="/assets/js/auth.js"></script>
<script>
let allTypes=[], allowedSet=new Set(), currentUser=null;
async function loadLib(){
  const lib = await Auth.api('/api/admin/widgets') || [];
  allTypes = lib.map(x=>x.key);
  const box = document.getElementById('lib'); box.innerHTML='';
  lib.forEach(w=>{
    const row=document.createElement('div'); row.className='row'; row.style.gap='8px';
    row.innerHTML = `<span class="badge">${w.key}</span><span class="badge">${w.default_title||''}</span>`;
    box.appendChild(row);
  });
}
document.getElementById('add').onclick = async ()=>{
  const key=document.getElementById('key').value.trim();
  const title=document.getElementById('title').value.trim();
  if(!key) return alert('Key required');
  await Auth.api('/api/admin/widgets', {method:'POST', body: JSON.stringify({key, default_title:title})});
  loadLib();
};
document.getElementById('load').onclick = async ()=>{
  const user = document.getElementById('user').value.trim(); if(!user) return;
  currentUser = user;
  allowedSet = new Set((await Auth.api('/api/admin/widgets/allow?user='+encodeURIComponent(user))) || []);
  const box = document.getElementById('matrix'); box.innerHTML='';
  allTypes.forEach(k=>{
    const id='w_'+k;
    const row=document.createElement('label'); row.style.display='flex'; row.style.gap='8px'; row.style.alignItems='center';
    row.innerHTML = `<input type="checkbox" id="${id}" ${allowedSet.has(k)?'checked':''}/> <span>${k}</span>`;
    box.appendChild(row);
  });
};
document.getElementById('save').onclick = async ()=>{
  if(!currentUser) return alert('Load a user first');
  const sel = Array.from(document.querySelectorAll('#matrix input[type=checkbox]')).filter(i=>i.checked).map(i=>i.id.slice(2));
  await Auth.api('/api/admin/widgets/allow', {method:'POST', body: JSON.stringify({user: currentUser, allowed: sel})});
  alert('Saved');
};
loadLib();
</script>
</body>
</html>
