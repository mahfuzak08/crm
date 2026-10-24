<?php
use App\Services\NumberGeneratorService as NG;
$ro = !can('settings.edit');
$old = App\Core\Session::getFlash('_old');
?>
<form method="post" action="<?= url('/settings/invoice') ?>">
  <?= csrf_field() ?>
  <fieldset<?= $ro ? ' disabled' : '' ?>>
  <div class="card mb-3"><div class="card-header">Invoice defaults</div><div class="card-body"><div class="row g-3">
    <?= form_input('invoice_default_due_days', 'Default due days', null, ['value' => $old['invoice_default_due_days'] ?? $s['invoice.default_due_days'], 'col' => 'col-md-4', 'type' => 'number']) ?>
    <?= form_input('invoice_default_tax_percent', 'Default tax / VAT %', null, ['value' => $old['invoice_default_tax_percent'] ?? $s['invoice.default_tax_percent'], 'col' => 'col-md-4']) ?>
    <?= form_select('invoice_template', 'Print template', ['classic' => 'Classic', 'modern' => 'Modern (brand header)'], null, ['value' => $old['invoice_template'] ?? $s['invoice.template'], 'col' => 'col-md-4']) ?>
    <?= form_textarea('invoice_terms', 'Default terms & conditions', null, ['value' => $old['invoice_terms'] ?? $s['invoice.terms'], 'rows' => 3]) ?>
    <div class="col-12 small text-muted">Invoice / receipt footers are edited in <a href="<?= url('/settings/company') ?>">Company Setup</a>.</div>
  </div></div></div>

  <div class="card"><div class="card-header">Document numbering</div>
    <div class="table-responsive"><table class="table">
      <thead><tr><th>Document</th><th>Prefix</th><th>Next number</th><th>Digits</th><th>Reset yearly</th><th>Next preview</th></tr></thead>
      <tbody>
      <?php foreach ($seqs as $q): $k = $q['seq_key']; $o = $old['seq'][$k] ?? $q; ?>
        <tr>
          <td class="fw-medium"><?= e(NG::SEQUENCES[$k][0] ?? $k) ?></td>
          <td><input name="seq[<?= e($k) ?>][prefix]" class="form-control form-control-sm" style="max-width:110px" value="<?= e($o['prefix']) ?>" required></td>
          <td><input name="seq[<?= e($k) ?>][next_number]" type="number" min="1" class="form-control form-control-sm" style="max-width:120px" value="<?= e($o['next_number']) ?>" required></td>
          <td><input name="seq[<?= e($k) ?>][number_length]" type="number" min="1" max="12" class="form-control form-control-sm" style="max-width:80px" value="<?= e($o['number_length']) ?>" required></td>
          <td><input type="hidden" name="seq[<?= e($k) ?>][yearly_reset]" value="0"><input class="form-check-input" type="checkbox" name="seq[<?= e($k) ?>][yearly_reset]" value="1"<?= checked($o['yearly_reset'] ?? 0) ?>></td>
          <td><code><?= e(NG::format($q, (int) $q['next_number'])) ?></code></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <div class="card-body pt-0 small text-muted">Numbers already used are skipped automatically, so lowering "next number" never creates duplicates.</div>
  </div>
  <?php if (!$ro): ?><div class="mt-3"><button class="btn btn-brand"><i class="bi bi-check2 me-1"></i>Save settings</button></div><?php endif; ?>
  </fieldset>
</form>
