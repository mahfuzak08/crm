<?php
$tot = max(1, $inv['total']);
App\Core\View::start('actions'); ?>
<?php if (can('gallery.view')): ?><a href="<?= url('/gallery', ['project' => $project['id']]) ?>" class="btn btn-light"><i class="bi bi-images me-1"></i>Gallery (<?= (int) $galleryCount ?>)</a><?php endif; ?>
<?php if (can('sales.create')): ?><a href="<?= url('/sales/create', ['project' => $project['id']]) ?>" class="btn btn-outline-brand"><i class="bi bi-cart-plus me-1"></i>Sell shares</a><?php endif; ?>
<?php if (can('projects.edit')): ?><a href="<?= url('/projects/' . $project['id'] . '/edit') ?>" class="btn btn-brand"><i class="bi bi-pencil me-1"></i>Edit</a><?php endif; ?>
<?php if (can('projects.delete')): ?><?= post_button('/projects/' . $project['id'] . '/delete', '', ['html' => '<i class="bi bi-trash"></i>', 'class' => 'btn btn-outline-danger', 'confirm' => 'Delete this project?']) ?><?php endif; ?>
<?php App\Core\View::end(); ?>

<div class="row g-3 mb-3">
  <?php foreach ([['Total shares', $inv['total'], 'slate', 'grid-3x3-gap'], ['Sold', $inv['sold'], 'green', 'check2-circle'], ['Reserved', $inv['reserved'], 'blue', 'hourglass-split'], ['Available', $inv['available'], 'amber', 'box-seam']] as [$l, $v, $c, $i]): ?>
  <div class="col-6 col-xl-3"><div class="card stat"><span class="stat-icon <?= $c ?>"><i class="bi bi-<?= $i ?>"></i></span><div><div class="stat-label"><?= $l ?></div><div class="stat-value"><?= number_format($v) ?></div></div></div></div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card mb-3"><div class="card-header">Details <?= $project['status'] ? status_badge($project['status']['name'], $project['status']['color']) : '' ?></div><div class="card-body">
      <div class="inv-bar mb-3"><span style="width:<?= round($inv['sold'] / $tot * 100, 2) ?>%;background:#198754" title="Sold"></span><span style="width:<?= round($inv['reserved'] / $tot * 100, 2) ?>%;background:#0dcaf0" title="Reserved"></span></div>
      <dl class="dl-grid">
        <dt>Code</dt><dd><?= e($project['project_code']) ?></dd>
        <dt>Location</dt><dd><?= e($project['location'] ?: '—') ?></dd>
        <dt>Land area</dt><dd><?= $project['total_land_area'] !== null ? e(rtrim(rtrim((string) $project['total_land_area'], '0'), '.') . ' ' . $project['land_unit']) : '—' ?></dd>
        <dt>Share unit</dt><dd><?= e($project['share_unit'] ?: '—') ?></dd>
        <dt>Share price</dt><dd class="fw-semibold"><?= money($project['share_price']) ?></dd>
        <dt>Registration fee</dt><dd><?= money($project['registration_fee']) ?> / share</dd>
        <dt>Development fee</dt><dd><?= money($project['development_fee']) ?> / share</dd>
        <dt>Start</dt><dd><?= e(fdate($project['start_date']) ?: '—') ?></dd>
        <dt>Completion</dt><dd><?= e(fdate($project['expected_completion_date']) ?: '—') ?></dd>
        <dt>Cancelled</dt><dd><?= number_format($inv['cancelled']) ?> shares (released)</dd>
      </dl>
      <?php if ($project['description']): ?><hr><div class="small" style="white-space:pre-line"><?= e($project['description']) ?></div><?php endif; ?>
    </div></div>
  </div>

  <div class="col-lg-7">
    <div class="card mb-3" id="packages">
      <div class="card-header">Share packages</div>
      <div class="table-responsive"><table class="table">
        <thead><tr><th>Package</th><th>Type</th><th class="text-end">Shares</th><th class="text-end">Price</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($packages as $pk): ?>
          <tr>
            <td class="fw-medium"><?= e($pk['name']) ?><div class="small text-muted"><?= e($pk['description']) ?></div></td>
            <td class="small"><?= e($pk['share_type']) ?></td>
            <td class="text-end"><?= (int) $pk['shares_count'] ?></td>
            <td class="text-end text-nowrap"><?= money($pk['price']) ?></td>
            <td><?= status_badge($pk['status']) ?></td>
            <td class="actions">
              <?php if (can('shares.edit')): ?><a class="btn btn-sm btn-light" href="<?= url('/projects/' . $project['id'], ['package' => $pk['id']]) ?>#pkgform"><i class="bi bi-pencil"></i></a><?php endif; ?>
              <?php if (can('shares.delete')): ?><?= post_button('/projects/' . $project['id'] . '/packages/' . $pk['id'] . '/delete', '', ['html' => '<i class="bi bi-trash"></i>', 'confirm' => 'Remove this package?']) ?><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$packages) echo empty_row(6, 'No packages. Shares can still be sold individually at the project share price.'); ?>
        </tbody>
      </table></div>
      <?php if ($editPkg ? can('shares.edit') : can('shares.create')): ?>
      <div class="card-body border-top" id="pkgform">
        <div class="fw-semibold small mb-2"><?= $editPkg ? 'Edit package' : 'Add package' ?></div>
        <form method="post" action="<?= url('/projects/' . $project['id'] . '/packages' . ($editPkg ? '/' . $editPkg['id'] : '')) ?>">
          <?= csrf_field() ?>
          <div class="row g-2">
            <?= form_input('name', 'Name', $editPkg, ['required' => true, 'col' => 'col-md-4']) ?>
            <?= form_select('share_type_id', 'Type', $shareTypes, $editPkg, ['empty' => '—', 'col' => 'col-md-3']) ?>
            <?= form_input('shares_count', 'Shares', $editPkg, ['type' => 'number', 'required' => true, 'col' => 'col-md-2', 'default' => 1, 'attrs' => ['min' => 1]]) ?>
            <?= form_input('price', 'Package price', $editPkg, ['type' => 'money', 'required' => true, 'col' => 'col-md-3']) ?>
            <?= form_input('description', 'Description', $editPkg, ['col' => 'col-md-9']) ?>
            <?= form_select('status', 'Status', ['active' => 'Active', 'inactive' => 'Inactive'], $editPkg, ['col' => 'col-md-3']) ?>
          </div>
          <div class="mt-2 d-flex gap-2"><button class="btn btn-sm btn-brand"><?= $editPkg ? 'Update' : 'Add package' ?></button>
            <?php if ($editPkg): ?><a href="<?= url('/projects/' . $project['id']) ?>" class="btn btn-sm btn-light">Cancel</a><?php endif; ?></div>
        </form>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($ledger): ?>
    <div class="card"><div class="card-header">Share movements</div>
      <div class="table-responsive"><table class="table table-sm">
        <thead><tr><th>Date</th><th>Type</th><th class="text-end">Shares</th><th>Sale</th><th>Customer</th><th>By</th></tr></thead>
        <tbody>
        <?php foreach ($ledger as $l): ?>
          <tr><td class="small text-nowrap"><?= e(fdate($l['created_at'], true)) ?></td><td><?= status_badge($l['type']) ?></td><td class="text-end"><?= (int) $l['shares'] ?></td>
            <td class="small"><?php if ($l['sale_id']): ?><a href="<?= url('/sales/' . $l['sale_id']) ?>"><?= e($l['sale_number']) ?></a><?php endif; ?></td>
            <td class="small"><?= e($l['customer_name']) ?></td><td class="small text-muted"><?= e($l['user_name']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
    <?php endif; ?>
  </div>
</div>
