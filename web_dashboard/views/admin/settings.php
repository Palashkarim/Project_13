<?php
// Admin · Settings — platform-wide flags (branding, theme, SMTP, SMS, push keys)
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin · Settings</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/base.css">
  <link rel="stylesheet" href="/assets/css/dashboard.css">
  <link rel="stylesheet" href="/assets/css/themes/dark.css" id="theme-dark">
</head>
<body>
<div class="app-shell">
  <div class="topbar"><div class="brand"><a href="/views/dashboard.php">IoT Platform</a> · Admin / Settings</div></div>
  <div class="content">
    <div class="card">
      <h3>Branding</h3>
      <div class="row" style="gap:8px;flex-wrap:wrap">
        <input class="input" id="brand" placeholder="Brand name">
        <input class="input" id="logo" placeholder="Logo URL">
        <button class="btn" id="saveBrand">Save</button>
      </div>
    </div>

    <div class="card" style="margin-top:12px">
      <h3>Email (SMTP)</h3>
      <div class="row" style="gap:8px;flex-wrap:wrap">
        <input class="input" id="smtp_host" placeholder="SMTP host">
        <input class="input" id="smtp_user" placeholder="SMTP user">
        <input class="input" id="smtp_pass" placeholder="SMTP pass">
        <button class="btn" id="saveSMTP">Save</button>
      </div>
    </div>

    <div class="card" style="margin-top:12px">
      <h3>Push (Web Push/PWA)</h3>
      <div class="row" style="gap:8px;flex-wrap:wrap">
        <input class="input" id="vapid_pub" placeholder="VAPID public key">
        <input class="input" id="vapid_priv" placeholder="VAPID private key">
        <button class="btn" id="savePush">Save</button>
      </div>
    </div>
  </div>
</div>
<script src="/assets/js/auth.js"></script>
<script>
async function load(){
  const s = await Auth.api('/api/admin/settings') || {};
  (id=>document.getElementById(id).value = s.brand_name || '')('brand');
  (id=>document.getElementById(id).value = s.brand_logo || '')('logo');
  (id=>document.getElementById(id).value = s.smtp_host || '')('smtp_host');
  (id=>document.getElementById(id).value = s.smtp_user || '')('smtp_user');
  (id=>document.getElementById(id).value = s.smtp_pass || '')('smtp_pass');
  (id=>document.getElementById(id).value = s.vapid_pub || '')('vapid_pub');
  (id=>document.getElementById(id).value = s.vapid_priv || '')('vapid_priv');
}
document.getElementById('saveBrand').onclick = async ()=>{
  await Auth.api('/api/admin/settings', {method:'POST', body: JSON.stringify({
    brand_name: document.getElementById('brand').value,
    brand_logo: document.getElementById('logo').value
  })});
  alert('Saved branding');
};
document.getElementById('saveSMTP').onclick = async ()=>{
  await Auth.api('/api/admin/settings', {method:'POST', body: JSON.stringify({
    smtp_host: document.getElementById('smtp_host').value,
    smtp_user: document.getElementById('smtp_user').value,
    smtp_pass: document.getElementById('smtp_pass').value
  })});
  alert('Saved SMTP');
};
document.getElementById('savePush').onclick = async ()=>{
  await Auth.api('/api/admin/settings', {method:'POST', body: JSON.stringify({
    vapid_pub: document.getElementById('vapid_pub').value,
    vapid_priv: document.getElementById('vapid_priv').value
  })});
  alert('Saved Push keys');
};
load();
</script>
</body>
</html>
