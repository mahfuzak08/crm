<?php App\Core\View::start('actions'); ?>
<?php if (can('customers.statement')): ?><a href="<?= url('/statements', ['customer' => $c['id']]) ?>" class="btn btn-light"><i class="bi bi-journal-text me-1"></i>Statement</a><?php endif; ?>
<?php if (can('offers.create')): ?><a href="<?= url('/offers/create', ['customer' => $c['id']]) ?>" class="btn btn-light"><i class="bi bi-send me-1"></i>Offer</a><?php endif; ?>
<?php if (can('sales.create')): ?><a href="<?= url('/sales/create', ['customer' => $c['id']]) ?>" class="btn btn-outline-brand"><i class="bi bi-cart-plus me-1"></i>Sell shares</a><?php endif; ?>
<?php if (can('customers.edit')): ?><a href="<?= url('/customers/' . $c['id'] . '/edit') ?>" class="btn btn-brand"><i class="bi bi-pencil me-1"></i>Edit</a><?php endif; ?>
<?php if (can('customers.delete')): ?><?= post_button('/customers/' . $c['id'] . '/delete', '', ['html' => '<i class="bi bi-trash"></i>', 'class' => 'btn btn-outline-danger', 'confirm' => 'Delete this customer?']) ?><?php endif; ?>
<?php App\Core\View::end(); ?>

<div class="row g-3 mb-3">
  <?php foreach ([['Shares owned', number_format($summary['shares']), 'violet', 'grid-3x3-gap'], ['Invoiced', money($summary['invoiced']), 'blue', 'receipt'], ['Paid', money($summary['paid']), 'green', 'cash-coin'], ['Due', money($summary['due']), 'red', 'exclamation-circle']] as [$l, $v, $col, $i]): ?>
  <div class="col-6 col-xl-3"><div class="card stat"><span class="stat-icon <?= $col ?>"><i class="bi bi-<?= $i ?>"></i></span><div><div class="stat-label"><?= $l ?></div><div class="stat-value"><?= $v ?></div></div></div></div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card mb-3"><div class="card-body">
      <div class="d-flex align-items-center gap-3 mb-3">
        <?php if ($c['photo']): ?><img src="<?= url('/customers/' . $c['id'] . '/file/photo') ?>" alt="" class="rounded-circle border" style="width:64px;height:64px;object-fit:cover">
        <?php else: ?><span class="avatar lg"><?= e(initials($c['name'])) ?></span><?php endif; ?>
        <div><div class="fw-semibold fs-5"><?= e($c['name']) ?></div><div class="text-muted small"><?= e($c['customer_code']) ?> · <?= status_badge($c['status']) ?></div></div>
      </div>
      <div class="d-flex flex-wrap gap-2 mb-3">
        <?php if ($c['phone']): ?><a class="btn btn-sm btn-light" href="tel:<?= e(phone_digits($c['phone'])) ?>"><i class="bi bi-telephone"></i> Call</a><?php endif; ?>
        <?php if ($whatsapp): ?><a class="btn btn-sm btn-success" href="<?= e($whatsapp) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> WhatsApp</a><?php endif; ?>
        <?php if ($c['email']): ?><a class="btn btn-sm btn-light" href="mailto:<?= e($c['email']) ?>"><i class="bi bi-envelope"></i> Email</a><?php endif; ?>
      </div>
      <dl class="dl-grid">
        <dt>Type</dt><dd><?= e($c['type_name'] ?? '—') ?></dd>
        <dt>Phone</dt><dd><?= e(implode(', ', array_filter([$c['phone'], $c['alternate_phone']])) ?: '—') ?></dd>
        <dt>Email</dt><dd><?= e($c['email'] ?: '—') ?></dd>
        <dt>Father</dt><dd><?= e($c['father_name'] ?: '—') ?></dd>
        <dt>Mother</dt><dd><?= e($c['mother_name'] ?: '—') ?></dd>
        <?php if ($c['spouse_name']): ?><dt>Spouse</dt><dd><?= e($c['spouse_name']) ?></dd><?php endif; ?>
        <dt>NID</dt><dd><?= e($c['nid'] ?: '—') ?></dd>
        <?php if ($c['passport']): ?><dt>Passport</dt><dd><?= e($c['passport']) ?></dd><?php endif; ?>
        <dt>Date of birth</dt><dd><?= e(fdate($c['date_of_birth']) ?: '—') ?></dd>
        <dt>Occupation</dt><dd><?= e($c['occupation'] ?: '—') ?></dd>
        <dt>Address</dt><dd><?= e(implode(', ', array_filter([$c['address'], $c['city'], $c['district'], $c['country']])) ?: '—') ?></dd>
        <?php if ($c['lead_id']): ?><dt>From lead</dt><dd><a href="<?= url('/leads/' . $c['lead_id']) ?>">View lead</a></dd><?php endif; ?>
      </dl>
      <?php if ($c['nid_front'] || $c['nid_back']): ?>
        <div class="d-flex gap-2 mt-3">
          <?php foreach (['nid_front' => 'NID front', 'nid_back' => 'NID back'] as $f => $l): if (!$c[$f]) continue; ?>
            <a href="<?= url('/customers/' . $c['id'] . '/file/' . $f) ?>" target="_blank" class="small text-center"><img src="<?= url('/customers/' . $c['id'] . '/file/' . $f) ?>" alt="<?= $l ?>" class="rounded border d-block" style="width:110px;height:70px;object-fit:cover"><?= $l ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if ($c['notes']): ?><hr><div class="small" style="white-space:pre-line"><?= e($c['notes']) ?></div><?php endif; ?>
    </div></div>
    <?php if (can('documents.view')): ?><?= partial('partials/documents_panel', ['documents' => $documents, 'field' => 'customer_id', 'subjectId' => $c['id'], 'docTypes' => $docTypes]) ?><?php endif; ?>
  </div>

  <div class="col-lg-8">
    <ul class="nav nav-tabs mb-3" role="tablist">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#t-sales" type="button">Shares (<?= count($sales) ?>)</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-inv" type="button">Invoices (<?= count($invoices) ?>)</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-pay" type="button">Payments (<?= count($payments) ?>)</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-off" type="button">Offers (<?= count($offers) ?>)</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-time" type="button">Timeline</button></li>
    </ul>
    <div class="tab-content">
      <div class="tab-pane fade show active" id="t-sales"><div class="card"><div class="table-responsive"><table class="table">
        <thead><tr><th>Sale</th><th>Date</th><th>Project</th><th class="text-end">Shares</th><th class="text-end">Total</th><th class="text-end">Due</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($sales as $s): ?><tr><td><a href="<?= url('/sales/' . $s['id']) ?>"><?= e($s['sale_number']) ?></a></td><td class="small"><?= e(fdate($s['sale_date'])) ?></td><td class="small"><?= e($s['project_name']) ?></td><td class="text-end"><?= (int) $s['shares'] ?></td><td class="text-end"><?= money($s['grand_total']) ?></td><td class="text-end"><?= money($s['due_amount']) ?></td><td><?= status_badge($s['status']) ?></td></tr><?php endforeach; ?>
        <?php if (!$sales) echo empty_row(7, 'No share purchases.'); ?></tbody></table></div></div></div>

      <div class="tab-pane fade" id="t-inv"><div class="card"><div class="table-responsive"><table class="table">
        <thead><tr><th>Invoice</th><th>Date</th><th>Due date</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Due</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($invoices as $i): ?><tr><td><a href="<?= url('/invoices/' . $i['id']) ?>"><?= e($i['invoice_number']) ?></a></td><td class="small"><?= e(fdate($i['invoice_date'])) ?></td><td class="small"><?= e(fdate($i['due_date'])) ?></td><td class="text-end"><?= money($i['grand_total']) ?></td><td class="text-end"><?= money($i['paid_amount']) ?></td><td class="text-end"><?= money($i['due_amount']) ?></td><td><?= status_badge($i['status']) ?></td></tr><?php endforeach; ?>
        <?php if (!$invoices) echo empty_row(7, 'No invoices.'); ?></tbody></table></div></div></div>

      <div class="tab-pane fade" id="t-pay"><div class="card"><div class="table-responsive"><table class="table">
        <thead><tr><th>Receipt</th><th>Date</th><th>Invoice</th><th>Method</th><th class="text-end">Amount</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($payments as $p): ?><tr><td><a href="<?= url('/payments/' . $p['id']) ?>"><?= e($p['payment_number']) ?></a></td><td class="small"><?= e(fdate($p['payment_date'])) ?></td><td class="small"><?= e($p['invoice_number']) ?></td><td class="small"><?= e($p['method']) ?></td><td class="text-end"><?= money($p['amount']) ?></td><td><?= status_badge($p['status']) ?></td></tr><?php endforeach; ?>
        <?php if (!$payments) echo empty_row(6, 'No payments.'); ?></tbody></table></div></div></div>

      <div class="tab-pane fade" id="t-off"><div class="card"><div class="table-responsive"><table class="table">
        <thead><tr><th>Offer</th><th>Date</th><th class="text-end">Total</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($offers as $o): ?><tr><td><a href="<?= url('/offers/' . $o['id']) ?>"><?= e($o['offer_number']) ?></a></td><td class="small"><?= e(fdate($o['created_at'])) ?></td><td class="text-end"><?= money($o['total']) ?></td><td><?= status_badge($o['status']) ?></td></tr><?php endforeach; ?>
        <?php if (!$offers) echo empty_row(4, 'No offers.'); ?></tbody></table></div></div></div>

      <div class="tab-pane fade" id="t-time">
        <?php if (can('activities.view')): ?><?= partial('partials/timeline', ['timeline' => $timeline, 'subject' => 'customer', 'subjectId' => $c['id'], 'activityTypes' => $activityTypes]) ?><?php endif; ?>
      </div>
    </div>
  </div>
</div>
