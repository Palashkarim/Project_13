<?php
/**
 * modal.php
 *
 * Bootstrap modal component for reusable dialogs.
 * Expects:
 *   $modal_id, $modal_title, $modal_body, $modal_footer (HTML strings)
 */
?>

<div class="modal fade" id="<?= htmlspecialchars($modal_id ?? 'genericModal') ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-3 shadow">
      <div class="modal-header">
        <h5 class="modal-title"><?= htmlspecialchars($modal_title ?? 'Dialog') ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?= $modal_body ?? '<p>No content</p>' ?>
      </div>
      <?php if (!empty($modal_footer)): ?>
      <div class="modal-footer">
        <?= $modal_footer ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
