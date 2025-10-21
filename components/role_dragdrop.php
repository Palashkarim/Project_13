<?php
/**
 * role_dragdrop.php
 *
 * Admin UI component to drag and assign permissions to roles.
 * Uses Bootstrap + SortableJS (or similar).
 */
?>
<div class="card shadow-sm rounded-3 mb-3">
  <div class="card-header bg-light">
    <h6 class="mb-0 fw-bold"><i class="bi bi-people"></i> Role Permission Manager</h6>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <h6>Available Permissions</h6>
        <ul id="permission-pool" class="list-group min-vh-50">
          <?php foreach ($available_permissions ?? [] as $perm): ?>
          <li class="list-group-item" draggable="true"><?= htmlspecialchars($perm) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="col-md-6">
        <h6>Assigned to Role: <?= htmlspecialchars($role_name ?? 'Role') ?></h6>
        <ul id="role-permissions" class="list-group">
          <?php foreach ($role_permissions ?? [] as $perm): ?>
          <li class="list-group-item bg-success-subtle" draggable="true"><?= htmlspecialchars($perm) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <div class="mt-3 text-end">
      <button class="btn btn-primary btn-sm" id="save-role-perms"><i class="bi bi-save"></i> Save</button>
    </div>
  </div>
</div>

<script>
// Simple drag and drop
const pool = document.getElementById('permission-pool');
const roleList = document.getElementById('role-permissions');

[pool, roleList].forEach(list => {
  list.addEventListener('dragstart', e => {
    e.dataTransfer.setData('text/plain', e.target.textContent);
  });
  list.addEventListener('dragover', e => e.preventDefault());
  list.addEventListener('drop', e => {
    e.preventDefault();
    const text = e.dataTransfer.getData('text/plain');
    const item = document.createElement('li');
    item.classList.add('list-group-item');
    item.textContent = text;
    e.currentTarget.appendChild(item);
  });
});

document.getElementById('save-role-perms')?.addEventListener('click', () => {
  const assigned = [...roleList.querySelectorAll('li')].map(li => li.textContent);
  fetch('api/save_role_permissions.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({role: '<?= addslashes($role_name ?? "") ?>', permissions: assigned})
  })
  .then(r => r.json())
  .then(d => alert(d.message || 'Saved'))
  .catch(() => alert('Error saving'));
});
</script>
