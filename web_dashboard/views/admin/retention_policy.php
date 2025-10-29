<?php
// Admin · Retention Policy — set global defaults + overrides by plan/tenant
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin · Retention Policy</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/base.css">
  <link rel="stylesheet" href="/assets/css/dashboard.css">
  <link rel="stylesheet" href="/assets/css/themes/dark.css" id="theme-dark">
</head>
<body>
<div class="app-shell">
  <div class="topbar"><div class="brand"><a href="/views/dashboard.php">IoT Platform</a> · Admin / Retention</div></div>
  <div class="content">
    <div class="toolbar"><h2>Data Retention</h2></div>
    <div class="card">
      <h3>Defaults</h3>
      <div class="row" style="gap:8px;flex-wrap:wrap">
        <input class="input" id="days" placeholder="Retention days (default)">
        <input class="input" id="export" placeholder="Export window days">
        <button class="btn" id="save">Save</button>
      </div>
    </div>
    <div class="card" style="margin-top:12px">
      <h3>Per-Plan Overrides</h3>
      <div id="plans"></div>
    </div>
  </div>
</div>
<script src="/assets/js/auth.js"></script>
<script>
async function load(){
  const d = await Auth.api('/api/admin/retention/defaults') || {};
  document.getElementById('days').value = d.retention_days || 30;
  document.getElementById('export').value= d.export_window_days || 7;

  const list = await Auth.api('/api/admin/retention/plans') || [];
  const box  = document.getElementById('plans'); box.innerHTML='';
  list.forEach(p=>{
    const row = document.createElement('div'); row.className='row'; row.style.gap='8px';
    row.innerHTML = `
      <span class="badge">${p.plan_key}</span>
      <input class="input" id="ret_${p.plan_key}" placeholder="days" value="${p.retention_days||''}" style="width:120px">
      <input class="input" id="exp_${p.plan_key}" placeholder="export window" value="${p.export_window_days||''}" style="width:160px">
      <button class="btn" data-p="${p.plan_key}">Save</button>
    `;
    row.querySelector('button').onclick = async (e)=>{
      const k = e.target.getAttribute('data-p');
      const body = {
        plan_key: k,
        retention_days: Number(document.getElementById('ret_'+k).value)||null,
        export_window_days: Number(document.getElementById('exp_'+k).value)||null
      };
      await Auth.api('/api/admin/retention/plans', {method:'POST', body: JSON.stringify(body)});
      alert('Saved');
    };
    box.appendChild(row);
  });
}
document.getElementById('save').onclick = async ()=>{
  const body = {
    retention_days: Number(document.getElementById('days').value)||30,
    export_window_days: Number(document.getElementById('export').value)||7
  };
  await Auth.api('/api/admin/retention/defaults', {method:'POST', body: JSON.stringify(body)});
  alert('Saved');
};
load();
</script>
</body>
</html>

