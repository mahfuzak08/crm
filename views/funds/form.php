<form method="post" action="<?= url($isEdit ? '/funds/' . $x['id'] : '/funds') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="card" style="max-width:820px"><div class="card-body"><div class="row g-3">
    <?= form_select('user_id', 'Director / user', $contributors, $x, ['empty' => '— Other contributor (enter name) —', 'col' => 'col-md-6', 'help' => 'Pick a director, or leave empty for an outside contributor.']) ?>
    <?= form_input('contributor_name', 'Contributor name (if other)', !empty($x['user_id']) ? null : $x, ['col' => 'col-md-6']) ?>
    <?= form_input('contributor_phone', 'Contributor phone', $x, ['col' => 'col-md-4']) ?>
    <?= form_select('fund_type', 'Fund type', App\Services\FundService::TYPES, $x, ['required' => true, 'col' => 'col-md-4']) ?>
    <?= form_select('project_id', 'Project (optional)', $projects, $x, ['empty' => '—', 'col' => 'col-md-4']) ?>
    <?= form_input('contribution_date', 'Date', $x, ['type' => 'date', 'required' => true, 'col' => 'col-md-4']) ?>
    <?= form_input('amount', 'Amount', $x, ['type' => 'money', 'required' => true, 'col' => 'col-md-4']) ?>
    <?= form_select('payment_method_id', 'Payment method', $methods, $x, ['empty' => '—', 'col' => 'col-md-4']) ?>
    <?= form_input('purpose', 'Purpose', $x, ['col' => 'col-md-8', 'placeholder' => 'e.g. Land development, office setup…']) ?>
    <?= form_input('reference', 'Reference', $x, ['col' => 'col-md-4', 'placeholder' => 'Cheque / transaction no.']) ?>
    <?= form_textarea('notes', 'Notes', $x, ['rows' => 3]) ?>
    <div class="col-12"><label class="form-label">Attachment (deposit slip / agreement)</label><input type="file" name="attachment" class="form-control<?= invalid('attachment') ?>" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"><?= error_for('attachment') ?>
      <?php if (!empty($x['attachment'])): ?><div class="form-text">Current: <a href="<?= url('/funds/' . $x['id'] . '/attachment') ?>" target="_blank"><?= e($x['attachment_name']) ?></a> (upload to replace)</div><?php endif; ?></div>
  </div></div></div>
  <div class="mt-3 d-flex gap-2"><button class="btn btn-brand"><i class="bi bi-check2 me-1"></i>Save contribution</button><a href="<?= url($isEdit ? '/funds/' . $x['id'] : '/funds') ?>" class="btn btn-light">Cancel</a></div>
</form>
