<?php
// Admin · Subscriptions — assign plans to users, set expiry, renew/cancel
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin · Subscriptions</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/base.css">
  <link rel="stylesheet" href="/assets/css/dashboard.css">
  <link rel="stylesheet" href="/assets/css/themes/dark.css" id="theme-dark">
</head>
<body>
<div class="app-shell">
  <div class="topbar"><div class="brand"><a href="/views/dashboard.php">IoT Platform</a> · Admin / Subscriptions</div></div>
  <div class="content">
    <div class="toolbar">
      <h2>Assign / Renew</h2>
      <div class="row" style="gap:8px;flex-wrap:wrap">
        <input class="input" id="user_id" placeholder="User ID/email">
        <input class="input" id="plan_key" placeholder="Plan key (e.g., PRO)">
        <input class="input" id="expires" type="date" placeholder="Expiry (YYYY-MM-DD)">
        <button class="btn" id="assign">Assign/Renew</button>
      </div>
    </div>

    <div class="card">
      <h3>Active Subscriptions</h3>
      <div id="list"></div>
    </div>
  </div>
</div>
<script src="/assets/js/auth.js"></script>
<script>
async function load(){
  const subs = await Auth.api('/api/admin/subscriptions') || [];
  const box = document.getElementById('list'); box.innerHTML='';
  subs.forEach(s=>{
    const row = document.createElement('div'); row.className='row'; row.style.gap='8px';
    row.innerHTML = `
      <span class="badge">#${s.id}</span>
      <span class="badge">user:${s.user_id}</span>
      <span class="badge">plan:${s.plan_key}</span>
      <span class="badge">expiry:${s.expires_at}</span>
      <button class="btn" data-id="${s.id}" data-act="cancel">Cancel</button>
    `;
    row.addEventListener('click', async (e)=>{
      const b=e.target.closest('button'); if(!b) return;
      const id=b.getAttribute('data-id'), act=b.getAttribute('data-act');
      if(act==='cancel'){ await Auth.api('/api/admin/subscriptions/'+id, {method:'DELETE'}); load(); }
    });
    box.appendChild(row);
  });
}
document.getElementById('assign').onclick = async ()=>{
  const user_id = document.getElementById('user_id').value.trim();
  const plan_key= document.getElementById('plan_key').value.trim();
  const expires = document.getElementById('expires').value;
  if(!user_id || !plan_key || !expires) return alert('All fields required');
  await Auth.api('/api/admin/subscriptions', {method:'POST', body: JSON.stringify({user_id, plan_key, expires_at: expires})});
  load();
};
load();
</script>
</body>
</html>
