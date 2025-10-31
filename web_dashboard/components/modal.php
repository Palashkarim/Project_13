<?php
/**
 * Modal Component
 * ------------------------------------------------------------
 * Minimal modal overlay. Emits HTML + lightweight JS controller.
 *
 * Usage:
 *   include __DIR__.'/modal.php';
 *   render_modal('confirmDelete', [
 *     'title' => 'Confirm Delete',
 *     'body'  => '<p>Are you sure?</p>',
 *     'ok'    => 'Delete',
 *     'cancel'=> 'Cancel'
 *   ]);
 *   // Open via JS: Modal.open('confirmDelete', { onOk(){...}, onCancel(){...} });
 */

if (!function_exists('render_modal')) {
  function render_modal(string $id, array $opts = []) {
    $title  = htmlspecialchars((string)($opts['title'] ?? 'Modal'), ENT_QUOTES, 'UTF-8');
    $body   = (string)($opts['body']  ?? '');
    $okText = htmlspecialchars((string)($opts['ok'] ?? 'OK'), ENT_QUOTES, 'UTF-8');
    $cancel = htmlspecialchars((string)($opts['cancel'] ?? 'Cancel'), ENT_QUOTES, 'UTF-8');

    echo <<<HTML
<div class="modal-overlay" id="modal_{$id}" style="display:none">
  <div class="modal">
    <div class="modal-head">
      <h3>{$title}</h3>
      <button class="btn ghost" data-close>✕</button>
    </div>
    <div class="modal-body">{$body}</div>
    <div class="modal-foot">
      <button class="btn" data-cancel>{$cancel}</button>
      <button class="btn primary" data-ok>{$okText}</button>
    </div>
  </div>
</div>
<script>
(function(){
  if (!window.Modal) {
    window.Modal = {
      _handlers: {},
      open: function(id, handlers){
        var el = document.getElementById('modal_'+id);
        if (!el) return;
        this._handlers[id] = handlers || {};
        el.style.display = 'grid';
      },
      close: function(id, why){
        var el = document.getElementById('modal_'+id);
        if (!el) return;
        el.style.display = 'none';
        var h = this._handlers[id] || {};
        if (why === 'ok' && typeof h.onOk === 'function') h.onOk();
        if (why === 'cancel' && typeof h.onCancel === 'function') h.onCancel();
        delete this._handlers[id];
      }
    };
  }
  var root = document.getElementById('modal_{$id}');
  if (!root.__wired) {
    root.__wired = true;
    root.querySelector('[data-close]').onclick  = function(){ Modal.close('{$id}', 'cancel'); };
    root.querySelector('[data-cancel]').onclick = function(){ Modal.close('{$id}', 'cancel'); };
    root.querySelector('[data-ok]').onclick     = function(){ Modal.close('{$id}', 'ok'); };
    root.addEventListener('click', function(e){ if (e.target === root) Modal.close('{$id}', 'cancel'); });
  }
})();
</script>
HTML;
  }
}
