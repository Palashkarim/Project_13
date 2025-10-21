<?php
/**
 * widget_card.php
 * 
 * Reusable component for displaying a widget inside a dashboard or board view.
 * Use: include 'components/widget_card.php';
 *
 * Expects variables:
 *   $widget_title  - string, title text
 *   $widget_body   - HTML content for the widget
 *   $widget_id     - optional unique DOM id
 */
?>

<div class="card shadow-sm rounded-2xl mb-3" id="<?= htmlspecialchars($widget_id ?? '') ?>">
  <div class="card-header bg-light d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-bold"><?= htmlspecialchars($widget_title ?? 'Widget') ?></h6>
    <div>
      <button class="btn btn-sm btn-outline-secondary" title="Refresh">
        <i class="bi bi-arrow-clockwise"></i>
      </button>
    </div>
  </div>
  <div class="card-body">
    <?= $widget_body ?? '<em>No content</em>' ?>
  </div>
</div>
