<?php
$profit = array_sum(array_map(static fn($r) => $r['status'] === 'confirmed' ? (float) $r['director_profit'] : 0, $referrals));
$refShares = array_sum(array_map(static fn($r) => $r['status'] === 'confirmed' ? (int) $r['shares'] : 0, $referrals));
$ownShares = array_sum(array_map(static fn($r) => $r['status'] === 'confirmed' ? (int) $r['shares'] : 0, $purchases));
App\Core\View::start('actions'); ?>
<?php if (can('sales.create')): ?><a href="<?= url('/sales/create', ['director' => $d['id']]) ?>" class="btn btn-light"><i class="bi bi-person-up me-1"></i>Referral sale</a><?php endif; ?>
<?php if ($d['customer_id'] && can('sales.create')): ?><a href="<?= url('/sales/create', ['customer' => $d['customer_id']]) ?>" class="btn btn-outline-brand"><i class="bi bi-cart-plus me-1"></i>Sell to director</a><?php endif; ?>
<?php if (can('directors.manage')): ?><a href="<?= url('/directors/' . $d['id'] . '/edit') ?>" class="btn btn-brand"><i class="bi bi-pencil me-1"></i>Edit</a>
  <?= post_button('/directors/' . $d['id'] . '/delete', '', ['html' => '<i class="bi bi-trash"></i>', 'class' => 'btn btn-outline-danger', 'confirm' => 'Remove this director from the board? (The customer / employee record is kept.)']) ?><?php endif; ?>
<?php App\Core\View::end(); ?>

<div class="row g-3 mb-3">
  <?php foreach ([['Own shares', number_format($ownShares), 'violet', 'grid-3x3-gap'], ['Referred shares', number_format($refShares), 'blue', 'people'], ['Referral profit', money((string) $profit), 'green', 'graph-up-arrow']] as [$l, $v, $col, $i]): ?>
  <div class="col-6 col-xl-4"><div class="card stat"><span class="stat-icon <?= $col ?>"><i class="bi bi-<?= $i ?>"></i></span><div><div class="stat-label"><?= $l ?></div><div class="stat-value"><?= $v ?></div></div></div></div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card"><div class="card-body">
      <div class="d-flex align-items-center gap-3 mb-3"><span class="avatar lg"><?= e(initials($d['name'])) ?></span>
        <div><div class="fw-semibold fs-5"><?= e($d['name']) ?></div><div class="text-muted small"><?= e($d['designation'] ?? 'Director') ?> · <?= status_badge($d['status']) ?></div></div></div>
      <dl class="dl-grid">
        <dt>Phone</dt><dd><?= e($d['phone'] ?: '—') ?></dd>
        <dt>Email</dt><dd><?= e($d['email'] ?: '—') ?></dd>
        <dt>Customer</dt><dd><?= $d['customer_id'] ? '<a href="' . url('/customers/' . $d['customer_id']) . '">' . e($d['customer_code']) . '</a>' : '—' ?></dd>
        <dt>Employee</dt><dd><?= $d['employee_id'] ? '<a href="' . url('/employees/' . $d['employee_id']) . '">' . e($d['employee_code']) . '</a>' : '—' ?></dd>
        <dt>On board since</dt><dd><?= e(fdate($d['joined_on']) ?: '—') ?></dd>
      </dl>
      <?php if (!$d['customer_id']): ?><div class="alert alert-warning small mt-3 mb-0">Not linked to a customer record — the director's own share purchases cannot be shown. <?php if (can('directors.manage')): ?><a href="<?= url('/directors/' . $d['id'] . '/edit') ?>">Link one</a>.<?php endif; ?></div><?php endif; ?>
      <?php if ($d['notes']): ?><hr><div class="small" style="white-space:pre-line"><?= e($d['notes']) ?></div><?php endif; ?>
    </div></div>
  </div>
  <div class="col-lg-8">
    <?php if (can('sales.view')): ?>
    <div class="card mb-3"><div class="card-header">Referral sales <span class="small text-muted fw-normal">company books the package price; the difference is the director's profit</span></div>
      <div class="table-responsive"><table class="table">
      <thead><tr><th>Sale</th><th>Date</th><th>Customer</th><th class="text-end">Shares</th><th class="text-end">Office price</th><th class="text-end">Customer price</th><th class="text-end">Profit</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($referrals as $r): ?><tr<?= $r['status'] === 'cancelled' ? ' class="text-muted"' : '' ?>>
        <td><a href="<?= url('/sales/' . $r['id']) ?>"><?= e($r['sale_number']) ?></a></td><td class="small"><?= e(fdate($r['sale_date'])) ?></td>
        <td class="small"><a href="<?= url('/customers/' . $r['customer_id']) ?>" class="text-reset"><?= e($r['customer_name']) ?></a><div class="text-muted"><?= e($r['project_name']) ?></div></td>
        <td class="text-end"><?= (int) $r['shares'] ?></td><td class="text-end text-nowrap"><?= money($r['grand_total']) ?></td><td class="text-end text-nowrap"><?= money($r['customer_total']) ?></td>
        <td class="text-end text-nowrap text-success fw-semibold"><?= money($r['director_profit']) ?></td><td><?= status_badge($r['status']) ?></td></tr><?php endforeach; ?>
      <?php if (!$referrals) echo empty_row(8, 'No referral sales.'); ?>
      </tbody></table></div></div>

    <div class="card"><div class="card-header">Own purchases</div><div class="table-responsive"><table class="table">
      <thead><tr><th>Sale</th><th>Date</th><th>Project</th><th class="text-end">Shares</th><th class="text-end">Total</th><th class="text-end">Due</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($purchases as $s): ?><tr><td><a href="<?= url('/sales/' . $s['id']) ?>"><?= e($s['sale_number']) ?></a></td><td class="small"><?= e(fdate($s['sale_date'])) ?></td><td class="small"><?= e($s['project_name']) ?></td><td class="text-end"><?= (int) $s['shares'] ?></td><td class="text-end"><?= money($s['grand_total']) ?></td><td class="text-end"><?= money($s['due_amount']) ?></td><td><?= status_badge($s['status']) ?></td></tr><?php endforeach; ?>
      <?php if (!$purchases) echo empty_row(7, 'No own purchases.'); ?>
      </tbody></table></div></div>
    <?php endif; ?>
  </div>
</div>
