<div class="table-responsive"><table class="table table-hover">
  <thead><tr><th><?= sort_link('code', 'Code') ?></th><th><?= sort_link('name', 'Employee') ?></th><th>Department / Designation</th><th>Contact</th><th><?= sort_link('joining', 'Joined') ?></th><?php if (can('salary.view')): ?><th class="text-end"><?= sort_link('salary', 'Basic') ?></th><?php endif; ?><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($p['rows'] as $e): ?>
    <tr>
      <td class="small"><a href="<?= url('/employees/' . $e['id']) ?>"><?= e($e['employee_code']) ?></a></td>
      <td><a href="<?= url('/employees/' . $e['id']) ?>" class="fw-medium text-reset"><?= e($e['name']) ?></a></td>
      <td class="small"><?= e($e['department']) ?><div class="text-muted"><?= e($e['designation']) ?></div></td>
      <td class="small"><?= e($e['phone']) ?><div class="text-muted"><?= e($e['email']) ?></div></td>
      <td class="small text-nowrap"><?= e(fdate($e['joining_date'])) ?></td>
      <?php if (can('salary.view')): ?><td class="text-end text-nowrap"><?= money($e['salary']) ?></td><?php endif; ?>
      <td><?= status_badge($e['status']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$p['rows']) echo empty_row(7, 'No employees.'); ?>
  </tbody>
</table></div>
<?= partial('partials/pagination', ['p' => $p]) ?>
