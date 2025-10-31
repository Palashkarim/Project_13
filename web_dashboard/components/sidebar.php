<?php
/**
 * Sidebar Component
 * ------------------------------------------------------------
 * Vertical navigation list with active state.
 *
 * Usage:
 *   include __DIR__.'/sidebar.php';
 *   render_sidebar([
 *     'active' => 'boards', // key of the active link
 *     'items'  => [
 *       ['key'=>'boards','href'=>'/views/dashboard.php','label'=>'Boards'],
 *       ['key'=>'analytics','href'=>'/views/analytics.php','label'=>'Analytics'],
 *       ['key'=>'firmware','href'=>'/views/firmware_repo.php','label'=>'Firmware'],
 *       ['key'=>'export','href'=>'/views/user/data_export.php','label'=>'Export']
 *     ],
 *     'sections' => [
 *       ['title'=>'Admin', 'items'=>[
 *          ['key'=>'admin_users','href'=>'/views/admin/users.php','label'=>'Users'],
 *          ['key'=>'admin_widgets','href'=>'/views/admin/widgets.php','label'=>'Widgets']
 *       ]]
 *     ]
 *   ]);
 */

if (!function_exists('render_sidebar')) {
  function render_sidebar(array $opts = []) {
    $active = (string)($opts['active'] ?? '');
    $items  = $opts['items'] ?? [];
    $sections = $opts['sections'] ?? [];

    echo '<div class="sidebar">';
    if (!empty($items)) {
      echo '<div class="section"><h4>Navigation</h4>';
      foreach ($items as $it) {
        $key   = (string)($it['key'] ?? '');
        $href  = htmlspecialchars((string)($it['href']  ?? '#'), ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars((string)($it['label'] ?? 'Item'), ENT_QUOTES, 'UTF-8');
        $cls   = 'link'.($key === $active ? ' active' : '');
        echo '<a class="'.$cls.'" href="'.$href.'">'.$label.'</a>';
      }
      echo '</div>';
    }

    foreach ($sections as $sec) {
      $title = htmlspecialchars((string)($sec['title'] ?? ''), ENT_QUOTES, 'UTF-8');
      echo '<div class="section"><h4>'.$title.'</h4>';
      foreach (($sec['items'] ?? []) as $it) {
        $key   = (string)($it['key'] ?? '');
        $href  = htmlspecialchars((string)($it['href']  ?? '#'), ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars((string)($it['label'] ?? 'Item'), ENT_QUOTES, 'UTF-8');
        $cls   = 'link'.($key === $active ? ' active' : '');
        echo '<a class="'.$cls.'" href="'.$href.'">'.$label.'</a>';
      }
      echo '</div>';
    }

    echo '</div>';
  }
}

