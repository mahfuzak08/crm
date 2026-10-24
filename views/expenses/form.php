<form method="post" action="<?= url($isEdit ? '/expenses/' . $x['id'] : '/expenses') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="card" style="max-width:820px"><div class="card-body"><div class="row g-3">
    <?= form_select('category_id', 'Category', $categories, $x, ['required' => true, 'empty' => '— select —', 'col' => 'col-md-6']) ?>
    <?= form_input('expense_date', 'Date', $x, ['type' => 'date', 'required' => true, 'col' => 'col-md-3']) ?>
    <?= form_input('amount', 'Amount', $x, ['type' => 'money', 'required' => true, 'col' => 'col-md-3']) ?>
    <?= form_select('payment_method_id', 'Payment method', $methods, $x, ['empty' => '—', 'col' => 'col-md-4']) ?>
    <?= form_input('reference', 'Reference', $x, ['col' => 'col-md-4']) ?>
    <?= form_select('project_id', 'Project (optional)', $projects, $x, ['empty' => '—', 'col' => 'col-md-4']) ?>
    <?= form_textarea('description', 'Description', $x, ['rows' => 3]) ?>
    <div class="col-12"><label class="form-label">Attachment (bill / voucher)</label><input type="file" name="attachment" class="form-control<?= invalid('attachment') ?>" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"><?= error_for('attachment') ?>
      <?php if (!empty($x['attachment'])): ?><div class="form-text">Current: <a href="<?= url('/expenses/' . $x['id'] . '/attachment') ?>" target="_blank"><?= e($x['attachment_name']) ?></a> (upload to replace)</div><?php endif; ?></div>
  </div></div></div>
  <div class="mt-3 d-flex gap-2"><button class="btn btn-brand"><i class="bi bi-check2 me-1"></i>Save expense</button><a href="<?= url($isEdit ? '/expenses/' . $x['id'] : '/expenses') ?>" class="btn btn-light">Cancel</a></div>
</form>
