<?php
/**
 * Role Drag & Drop Component
 * ------------------------------------------------------------
 * Visual tool to assign permissions to roles by dragging chips between
 * "Available" and "Granted" columns. Saves to API when requested.
 *
 * Usage:
 *   include __DIR__.'/role_dragdrop.php';
 *   render_role_dragdrop([
 *     'roleKey'    => 'technician',
 *     'allPerms'   => ['users.read','users.write','devices.read','devices.write','mqtt.manage','boards.manage','widgets.manage','exports.manage'],
 *     'granted'    => ['devices.read','boards.manage'],
 *     'saveButton' => true
 *   ]);
 *
 * Expected backend endpoint:
 *   POST /api/admin/roles/permissions
 *   Body: [{ role: "<roleKey>", permissions: ["perm1","perm2"] }]
 */

if (!function_exists('render_role_dragdrop')) {
  function render_role_dragdrop(array $opts = []) {
    $roleKey  = htmlspecialchars((string)($opts['roleKey'] ?? 'role'), ENT_QUOTES, 'UTF-8');
    $allPerms = $opts['allPerms'] ?? [];
    $granted  = $opts['granted']  ?? [];
    $saveBtn  = (bool)($opts['saveButton'] ?? true);

    // Encode for JS
    $allJson = json_encode(array_values($allPerms), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $grJson  = json_encode(array_values($granted),  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    echo <<<HTML
<div class="card" id="role_dd_{$roleKey}">
  <h3>Role: {$roleKey}</h3>
  <div class="row" style="gap:12px;align-items:flex-start;flex-wrap:wrap">
    <div class="card" style="min-width:260px;flex:1">
      <h4>Available</h4>
      <div class="boards-grid" id="avail_{$roleKey}"></div>
    </div>
    <div class="card" style="min-width:260px;flex:1">
      <h4>Granted</h4>
      <div class="boards-grid" id="grant_{$roleKey}"></div>
    </div>
  </div>
  <div class="meta">Drag permissions between lists. Double-click a chip to move quickly.</div>
  <!-- Optional Save button -->
  <div style="margin-top:8px">
HTML;
    if ($saveBtn) {
      echo '<button class="btn primary" id="save_'.$roleKey.'">Save Permissions</button>';
    }
    echo <<<HTML
  </div>
</div>

<script>
(function(){
  var role  = "{$roleKey}";
  var all   = {$allJson} || [];
  var grant = new Set({$grJson} || []);
  var avail = all.filter(p => !grant.has(p));

  var boxA = document.getElementById('avail_'+role);
  var boxG = document.getElementById('grant_'+role);

  function chip(text){
    var d = document.createElement('div');
    d.className = 'badge';
    d.draggable = true;
    d.textContent = text;
    d.addEventListener('dblclick', function(){
      if (grant.has(text)) { grant.delete(text); avail.push(text); }
      else { grant.add(text); avail = avail.filter(x => x !== text); }
      render();
    });
    d.addEventListener('dragstart', function(ev){ ev.dataTransfer.setData('text/plain', text); });
    return d;
  }

  function makeDropZone(el, target){
    el.addEventListener('dragover', function(e){ e.preventDefault(); el.style.outline='1px dashed var(--border)'; });
    el.addEventListener('dragleave',function(){ el.style.outline='none'; });
    el.addEventListener('drop', function(e){
      e.preventDefault(); el.style.outline='none';
      var p = e.dataTransfer.getData('text/plain');
      if (!p) return;
      if (target === 'grant') {
        if (!grant.has(p)) { grant.add(p); avail = avail.filter(x => x !== p); }
      } else {
        if (grant.has(p)) { grant.delete(p); if (!avail.includes(p)) avail.push(p); }
      }
      render();
    });
  }

  function render(){
    boxA.innerHTML=''; boxG.innerHTML='';
    // Keep stable sort
    avail.sort(); Array.from(grant).sort().forEach(()=>{});
    avail.forEach(p => boxA.appendChild(chip(p)));
    Array.from(grant).sort().forEach(p => boxG.appendChild(chip(p)));
  }

  makeDropZone(boxA, 'avail');
  makeDropZone(boxG, 'grant');
  render();

  var saveBtn = document.getElementById('save_'+role);
  if (saveBtn && window.Auth){
    saveBtn.onclick = async function(){
      var body = [{ role: role, permissions: Array.from(grant) }];
      await Auth.api('/api/admin/roles/permissions', {method:'POST', body: JSON.stringify(body)});
      alert('Permissions saved for '+role);
    };
  }
})();
</script>
HTML;
  }
}
