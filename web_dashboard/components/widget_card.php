<?php
/**
 * Widget Card Component
 * ------------------------------------------------------------
 * Renders a standard card shell for a widget and mounts a JS widget
 * instance into it using WidgetRegistry (from widget_loader.js).
 *
 * Usage:
 *   include __DIR__.'/widget_card.php';
 *   render_widget_card([
 *     'id'    => 101,                  // optional numeric/string id
 *     'type'  => 'gauge',              // widget key registered in JS
 *     'title' => 'Room Temp',          // optional title
 *     'cfg'   => [                     // config passed to widget factory
 *       'topic_tele' => 'ten/1/dev/d1/tele',
 *       'metric'     => 'temp'
 *     ],
 *     'class' => 'span-2'              // optional extra CSS class
 *   ]);
 *
 * Requires (already on your pages):
 *   <script src="/assets/js/widget_loader.js"></script>
 *   + the specific widget's JS loaded via WidgetRegistry.autoload(...)
 */

if (!function_exists('render_widget_card')) {
  function render_widget_card(array $w) {
    $id    = htmlspecialchars((string)($w['id'] ?? uniqid('w_')), ENT_QUOTES, 'UTF-8');
    $type  = htmlspecialchars((string)($w['type'] ?? 'unknown'), ENT_QUOTES, 'UTF-8');
    $title = htmlspecialchars((string)($w['title'] ?? ucfirst($type)), ENT_QUOTES, 'UTF-8');
    $class = htmlspecialchars((string)($w['class'] ?? ''), ENT_QUOTES, 'UTF-8');

    // JSON config safely encoded for JS bootstrap
    $cfg   = $w['cfg'] ?? [];
    $cfgJson = json_encode($cfg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    echo <<<HTML
<div class="card widget-card {$class}" data-widget-type="{$type}" data-widget-id="{$id}">
  <div class="card-head">
    <h3>{$title}</h3>
    <div class="actions">
      <button class="btn ghost" data-action="refresh" title="Refresh">⟳</button>
      <button class="btn ghost" data-action="remove"  title="Remove">✕</button>
    </div>
  </div>
  <div class="widget-body" id="mount_{$id}"></div>
</div>
<script>
(function(){
  // Avoid duplicate mounts on partial reloads
  if (!window.__mountedWidgets) window.__mountedWidgets = {};
  var wid = "{$id}";
  if (window.__mountedWidgets[wid]) return;

  var type = document.querySelector('[data-widget-id="{$id}"]').getAttribute('data-widget-type');
  var cfg  = {$cfgJson} || {};
  // Create instance from the JS widget registry and attach into mount div
  try{
    var inst = WidgetRegistry.create(type, cfg);
    var mount = document.getElementById('mount_{$id}');
    if (inst && inst.el) mount.appendChild(inst.el);
    // Hook actions
    var root = mount.closest('.widget-card');
    root.querySelector('[data-action="refresh"]').onclick = function(){
      if (inst && inst.update) inst.update(cfg); // simple nudge
    };
    root.querySelector('[data-action="remove"]').onclick = function(){
      if (inst && inst.destroy) inst.destroy();
      root.parentNode.removeChild(root);
    };
    window.__mountedWidgets[wid] = inst;
  }catch(e){ console.error('Widget mount failed', e); }
})();
</script>
HTML;
  }
}
