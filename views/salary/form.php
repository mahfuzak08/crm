<div class="row g-3">
  <div class="col-lg-7">
    <div class="card"><div class="card-body">
      <form method="get" action="<?= url('/salary/create') ?>" class="mb-3">
        <label class="form-label">Employee <span class="req">*</span></label>
        <select name="employee" class="form-select" data-autosubmit><option value="">— select employee —</option>
          <?php foreach ($employees as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $rec['employee_id']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
        <input type="hidden" name="month" value="<?= e($rec['salary_month']) ?>">
      </form>
      <?php if ($emp): ?>
      <form method="post" action="<?= url('/salary') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="employee_id" value="<?= (int) $emp['id'] ?>">
        <div class="row g-3">
          <?= form_input('salary_month', 'Salary month', $rec, ['type' => 'month', 'required' => true, 'col' => 'col-md-6']) ?>
          <?= form_input('payment_date', 'Payment date', $rec, ['type' => 'date', 'required' => true, 'col' => 'col-md-6']) ?>
          <?= form_input('extra_bonus', 'Additional bonus', $rec, ['type' => 'money', 'col' => 'col-md-4']) ?>
          <?= form_input('extra_commission', 'Additional commission', $rec, ['type' => 'money', 'col' => 'col-md-4']) ?>
          <?= form_input('extra_deduction', 'Additional deduction', $rec, ['type' => 'money', 'col' => 'col-md-4']) ?>
          <?= form_select('payment_method_id', 'Payment method', $methods, $rec, ['empty' => '—', 'col' => 'col-md-6']) ?>
          <?= form_input('reference', 'Reference', $rec, ['col' => 'col-md-6']) ?>
          <?= form_textarea('notes', 'Notes', $rec, ['rows' => 2]) ?>
        </div>
        <button class="btn btn-success mt-3"><i class="bi bi-check2 me-1"></i>Pay salary</button>
        <div class="small text-muted mt-2">Net salary is calculated by the server from the structure plus the adjustments above. Only one payment per employee per month.</div>
      </form>
      <?php endif; ?>
    </div></div>
  </div>
  <?php if ($preview): ?>
  <div class="col-lg-5">
    <div class="card"><div class="card-header">Structure — <?= e($emp['name']) ?></div>
      <table class="table mb-0">
        <tr><td>Basic</td><td class="text-end"><?= money($preview['basic']) ?></td></tr>
        <?php foreach ($preview['lines'] as $l): ?><tr><td><?= e($l['name']) ?></td><td class="text-end"><?= $l['type'] === 'deduction' ? '−' : '' ?><?= money($l['amount']) ?></td></tr><?php endforeach; ?>
        <tr class="fw-bold"><td>Net (before adjustments)</td><td class="text-end"><?= money($preview['net_salary']) ?></td></tr>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>
