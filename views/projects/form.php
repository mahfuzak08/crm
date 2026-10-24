<form method="post" action="<?= url($project ? '/projects/' . $project['id'] : '/projects') ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card h-100"><div class="card-header">Project details</div><div class="card-body"><div class="row g-3">
        <?= form_input('project_name', 'Project name', $project, ['required' => true, 'col' => 'col-md-8']) ?>
        <?= form_input('project_code', 'Code', $project, ['col' => 'col-md-4', 'help' => $project ? null : 'Leave blank to auto-generate']) ?>
        <?= form_input('location', 'Location', $project, ['col' => 'col-12']) ?>
        <?= form_select('status_id', 'Status', $statuses, $project, ['empty' => '— select —', 'col' => 'col-md-4']) ?>
        <?= form_input('start_date', 'Start date', $project, ['type' => 'date', 'col' => 'col-md-4']) ?>
        <?= form_input('expected_completion_date', 'Expected completion', $project, ['type' => 'date', 'col' => 'col-md-4']) ?>
        <?= form_textarea('description', 'Description', $project, ['rows' => 5]) ?>
      </div></div></div>
    </div>
    <div class="col-lg-5">
      <div class="card h-100"><div class="card-header">Land &amp; shares</div><div class="card-body"><div class="row g-3">
        <?= form_input('total_land_area', 'Total land area', $project, ['col' => 'col-7']) ?>
        <?= form_input('land_unit', 'Unit', $project, ['col' => 'col-5', 'placeholder' => 'Katha / Decimal / Sq ft', 'attrs' => ['list' => 'land-units']]) ?>
        <datalist id="land-units"><option value="Katha"><option value="Decimal"><option value="Bigha"><option value="Sq ft"><option value="Acre"></datalist>
        <?= form_input('total_shares', 'Total shares', $project, ['type' => 'number', 'required' => true, 'col' => 'col-6', 'attrs' => ['min' => 0]]) ?>
        <?= form_input('share_price', 'Price per share', $project, ['type' => 'money', 'required' => true, 'col' => 'col-6']) ?>
        <?= form_input('share_unit', 'Share unit description', $project, ['col' => 'col-12', 'placeholder' => 'e.g. 1 share = 0.5 Katha land']) ?>
        <?= form_input('registration_fee', 'Registration fee (per share)', $project, ['type' => 'money', 'col' => 'col-6', 'default' => '0.00']) ?>
        <?= form_input('development_fee', 'Development fee (per share)', $project, ['type' => 'money', 'col' => 'col-6', 'default' => '0.00']) ?>
      </div></div></div>
    </div>
  </div>
  <div class="mt-3 d-flex gap-2"><button class="btn btn-brand"><i class="bi bi-check2 me-1"></i>Save project</button>
    <a href="<?= url($project ? '/projects/' . $project['id'] : '/projects') ?>" class="btn btn-light">Cancel</a></div>
</form>
