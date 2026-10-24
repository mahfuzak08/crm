<?php
/** Generic report filter bar. vars: $type, $f, $r, $options */
$use = $r['filters'] ?? [];
$sel = static function (string $name, string $label, array $opts) use ($f) {
    $h = '<select name="' . $name . '" class="form-select"><option value="">' . e($label) . '</option>';
    foreach ($opts as $id => $n) {
        $h .= '<option value="' . e($id) . '"' . selected($id, $f[$name] ?? '') . '>' . e($n) . '</option>';
    }
    return $h . '</select>';
};
?>
<form class="filters" method="get" action="<?= url('/reports/' . $type) ?>" data-live-filter data-target="#list">
  <input type="search" name="q" class="form-control search" placeholder="Search…" value="<?= e($f['q']) ?>">
  <?php if (!empty($options['companies'])): ?>
    <select name="company" class="form-select"><option value="">Current company</option><option value="all"<?= selected('all', $f['company']) ?>>All companies</option>
      <?php foreach ($options['companies'] as $c): ?><option value="<?= (int) $c['id'] ?>"<?= selected($c['id'], $f['company']) ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
  <?php endif; ?>
  <?php if (in_array('date', $use, true)): ?>
    <input type="date" name="from" class="form-control" value="<?= e($f['from']) ?>" title="<?= e(($r['date_label'] ?? 'Date') . ' from') ?>">
    <input type="date" name="to" class="form-control" value="<?= e($f['to']) ?>" title="<?= e(($r['date_label'] ?? 'Date') . ' to') ?>">
  <?php endif; ?>
  <?php if (isset($options['projects'])) echo $sel('project', 'All projects', $options['projects']); ?>
  <?php if (isset($options['customers'])) echo $sel('customer', 'All customers', $options['customers']); ?>
  <?php if (isset($options['users'])) echo $sel('user', 'All users / sales persons', $options['users']); ?>
  <?php if (isset($options['methods'])) echo $sel('method', 'All methods', $options['methods']); ?>
  <?php if (isset($options['categories'])) echo $sel('category', 'All categories', $options['categories']); ?>
  <?php if (isset($options['lead_statuses'])) echo $sel('lead_status', 'All statuses', $options['lead_statuses']); ?>
  <?php if (isset($options['sources'])) echo $sel('source', 'All sources', $options['sources']); ?>
  <?php if (isset($options['departments'])) echo $sel('department', 'All departments', $options['departments']); ?>
  <?php if (in_array('sale_status', $use, true)) echo $sel('status', 'Confirmed', ['reserved' => 'Reserved', 'cancelled' => 'Cancelled']); ?>
  <?php if (in_array('emp_status', $use, true)) echo $sel('emp_status', 'All statuses', ['active' => 'Active', 'inactive' => 'Inactive', 'resigned' => 'Resigned']); ?>
  <?php if (in_array('only_due', $use, true)) echo $sel('only_due', 'All customers', ['1' => 'Only with due']); ?>
</form>
