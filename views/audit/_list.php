<?php
$fmt = static function (?string $json): string {
    if (!$json) return '';
    $a = json_decode($json, true);
    if (!is_array($a)) return e($json);
    $out = [];
    foreach ($a as $k => $v) {
        $out[] = '<span class="text-muted">' . e($k) . ':</span> ' . e(is_scalar($v) || $v === null ? (string) $v : json_encode($v));
    }
    return implode('<br>', $out);
};
?>
<div class="table-responsive"><table class="table table-sm">
  <thead><tr><th>Date</th><th>User</th><th>Action</th><th>Module</th><th>Changes</th><th>IP</th></tr></thead>
  <tbody>
  <?php foreach ($p['rows'] as $r): ?>
    <tr>
      <td class="text-nowrap small"><?= e(fdate($r['created_at'], true)) ?></td>
      <td class="small"><?= e($r['user_name'] ?? 'System') ?></td>
      <td><span class="badge text-bg-light text-dark"><?= e($r['action']) ?></span></td>
      <td class="small"><?= e($r['module']) ?><?= $r['record_id'] ? ' #' . (int) $r['record_id'] : '' ?></td>
      <td class="small" style="max-width:420px">
        <?php if ($r['old_values'] || $r['new_values']): ?>
        <details><summary class="text-muted">view</summary>
          <div class="row g-2 mt-1">
            <?php if ($r['old_values']): ?><div class="col-sm-6"><div class="fw-semibold">Before</div><?= $fmt($r['old_values']) ?></div><?php endif; ?>
            <?php if ($r['new_values']): ?><div class="col-sm-6"><div class="fw-semibold">After</div><?= $fmt($r['new_values']) ?></div><?php endif; ?>
          </div>
        </details>
        <?php endif; ?>
      </td>
      <td class="small text-muted"><?= e($r['ip']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$p['rows']) echo empty_row(6); ?>
  </tbody>
</table></div>
<?= partial('partials/pagination', ['p' => $p]) ?>
