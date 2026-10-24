<form method="post" action="<?= url($lead ? '/leads/' . $lead['id'] : '/leads') ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card"><div class="card-header">Lead information</div><div class="card-body"><div class="row g-3">
        <?= form_input('name', 'Full name', $lead, ['required' => true, 'col' => 'col-md-12']) ?>
        <?= form_input('phone', 'Phone', $lead, ['placeholder' => '01XXXXXXXXX']) ?>
        <?= form_input('email', 'Email', $lead, ['type' => 'email']) ?>
        <?= form_textarea('address', 'Address', $lead, ['rows' => 2]) ?>
        <?= form_textarea('notes', 'Notes', $lead, ['rows' => 4]) ?>
        <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="allow_duplicate" value="1" id="dup"><label class="form-check-label small" for="dup">Allow duplicate phone number</label></div></div>
      </div></div></div>
    </div>
    <div class="col-lg-4">
      <div class="card"><div class="card-header">Pipeline</div><div class="card-body"><div class="row g-3">
        <?= form_select('status_id', 'Status', $statuses, $lead, ['col' => 'col-12', 'default' => array_key_first($statuses)]) ?>
        <?= form_select('source_id', 'Source', $sources, $lead, ['empty' => '— select —', 'col' => 'col-12']) ?>
        <?= form_select('project_id', 'Interested project', $projects, $lead, ['empty' => '— select —', 'col' => 'col-12']) ?>
        <?php if (can('leads.assign')): ?>
          <?= form_select('assigned_to', 'Assigned to', $users, $lead, ['empty' => '— unassigned —', 'col' => 'col-12', 'default' => auth_id()]) ?>
        <?php endif; ?>
        <?= form_input('follow_up_date', 'Next follow-up', $lead, ['type' => 'date', 'col' => 'col-12']) ?>
      </div></div></div>
    </div>
  </div>
  <div class="mt-3 d-flex gap-2"><button class="btn btn-brand"><i class="bi bi-check2 me-1"></i>Save lead</button>
    <a href="<?= url($lead ? '/leads/' . $lead['id'] : '/leads') ?>" class="btn btn-light">Cancel</a></div>
</form>
