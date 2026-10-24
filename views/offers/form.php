<form method="post" action="<?= url($offer ? '/offers/' . $offer['id'] : '/offers') ?>" data-share-calc data-fee-override="<?= $feeOverride ? '1' : '0' ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card"><div class="card-header">Offer details</div><div class="card-body">
        <?= partial('partials/share_calc', ['rec' => $rec, 'projects' => $projects, 'feeOverride' => $feeOverride]) ?>
      </div></div>
    </div>
    <div class="col-lg-4">
      <div class="card"><div class="card-header">Recipient</div><div class="card-body"><div class="row g-3">
        <div class="col-12">
          <label class="form-label" for="f_customer_id">Customer</label>
          <input type="search" class="form-control form-control-sm mb-1" placeholder="Search customers…" data-lookup="/api/lookup/customers" data-lookup-target="#f_customer_id">
          <select name="customer_id" id="f_customer_id" class="form-select<?= invalid('customer_id') ?>"><option value="">— select —</option>
            <?php foreach ($customers as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, fv($rec, 'customer_id')) ?>><?= e($n) ?></option><?php endforeach; ?></select>
          <?= error_for('customer_id') ?>
        </div>
        <div class="col-12 text-center small text-muted">— or —</div>
        <?= form_select('lead_id', 'Lead', $leads, $rec, ['empty' => '— select —', 'col' => 'col-12']) ?>
        <?= form_input('valid_until', 'Valid until', $rec, ['type' => 'date', 'col' => 'col-12']) ?>
        <?= form_textarea('notes', 'Notes', $rec, ['rows' => 2]) ?>
        <?= form_textarea('terms', 'Terms (blank = default)', $rec, ['rows' => 3]) ?>
      </div></div></div>
    </div>
  </div>
  <div class="mt-3 d-flex gap-2"><button class="btn btn-brand"><i class="bi bi-check2 me-1"></i>Save offer</button>
    <a href="<?= url($offer ? '/offers/' . $offer['id'] : '/offers') ?>" class="btn btn-light">Cancel</a></div>
</form>
