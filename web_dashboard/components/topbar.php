<?php
/**
 * Topbar Component
 * ------------------------------------------------------------
 * A simple top navigation bar with brand, user badge, and actions.
 *
 * Usage:
 *   include __DIR__.'/topbar.php';
 *   render_topbar([
 *     'brand'  => 'IoT Platform',
 *     'links'  => [
 *        ['href'=>'/views/dashboard.php', 'label'=>'Dashboard'],
 *        ['href'=>'/views/analytics.php', 'label'=>'Analytics'],
 *     ],
 *     'showUser' => true
 *   ]);
 *
 * Requires auth.js on the page for logout and fetching user.
 */

if (!function_exists('render_topbar')) {
  function render_topbar(array $opts = []) {
    $brand = htmlspecialchars((string)($opts['brand'] ?? 'IoT Platform'), ENT_QUOTES, 'UTF-8');
    $links = $opts['links'] ?? [];
    $showUser = (bool)($opts['showUser'] ?? true);

    echo '<div class="topbar">';
    echo '<div class="brand">'.$brand.'</div>';
    echo '<div class="right">';

    foreach ($links as $lnk) {
      $href  = htmlspecialchars((string)($lnk['href']  ?? '#'), ENT_QUOTES, 'UTF-8');
      $label = htmlspecialchars((string)($lnk['label'] ?? 'Link'), ENT_QUOTES, 'UTF-8');
      echo '<a class="btn ghost" href="'.$href.'">'.$label.'</a>';
    }

    if ($showUser) {
      echo '<span class="badge" id="tbUser">User</span>';
      echo '<button class="btn ghost" id="tbTheme">Theme</button>';
      echo '<a class="btn" href="/views/user/account.php">Account</a>';
      echo '<button class="btn" id="tbLogout">Logout</button>';
    }
    echo '</div></div>';

    // Small helper JS (only once)
    echo <<<HTML
<script>
(function(){
  if (window.__topbarInit) return; window.__topbarInit = true;
  document.addEventListener('DOMContentLoaded', async function(){
    if (!window.Auth) return;
    try {
      const me = await Auth.api('/api/me');
      var u = document.getElementById('tbUser');
      if (u) u.textContent = me.display_name || me.email || 'User';
    } catch(_){}

    var themeBtn = document.getElementById('tbTheme');
    if (themeBtn){
      themeBtn.onclick = function(){
        var dark = document.getElementById('theme-dark');
        if (dark) dark.disabled = !dark.disabled;
      };
    }
    var lo = document.getElementById('tbLogout');
    if (lo){ lo.onclick = function(){ Auth.logout(); location.href = '/views/login.php'; }; }
  });
})();
</script>
HTML;
  }
}
