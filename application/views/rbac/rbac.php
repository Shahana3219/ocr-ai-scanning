<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
// Group the flat $modules result set by module_group so we can render
// it as an accordion (Group -> list of Title/Function rows), matching
// the old RBAC screen's layout.
$grouped = [];
if (!empty($modules)) {
    foreach ($modules as $row) {
        $groupName = $row->module_group ?? 'Ungrouped';
        $grouped[$groupName][] = $row;
    }
    ksort($grouped);
}

// Split the group names into two columns (left / right) like the old UI.
$groupNames  = array_keys($grouped);
$half        = (int) ceil(count($groupNames) / 2);
$leftGroups  = array_slice($groupNames, 0, $half);
$rightGroups = array_slice($groupNames, $half);

// Only the most recently added row should show edit/delete actions.
$latestModuleId = 0;
if (!empty($modules)) {
    foreach ($modules as $row) {
        $latestModuleId = max($latestModuleId, (int) ($row->id ?? 0));
    }
}

// Helper to build a safe id/slug for accordion targets.
if (!function_exists('rbac_slug')) {
    function rbac_slug($value)
    {
        return preg_replace('/[^a-z0-9]+/', '-', strtolower(trim((string) $value)));
    }
}
?>

<div class="container-fluid py-4">
  <div class="card mb-4 shadow-sm border-0">
    <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2 bg-white border-0">
      <h5 class="mb-0 text-dark">RBAC Modules</h5>
      <button type="button" class="btn btn-primary btn-sm mb-0 btn-add-module"
              data-toggle="modal" data-bs-toggle="modal"
              data-target="#rbacModuleModal" data-bs-target="#rbacModuleModal">
        <i class="fas fa-plus"></i> Add Module
      </button>
    </div>

    <div class="card-body">

      <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?= $this->session->flashdata('success') ?>
          <button type="button" class="btn-close" data-dismiss="alert" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= $this->session->flashdata('error') ?>
          <button type="button" class="btn-close" data-dismiss="alert" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <div class="row g-2 mb-3 align-items-center">
        <div class="col-md-5 col-lg-4">
          <input type="text" id="rbacSearch" list="rbacSuggestions" class="form-control form-control-sm border-radius-lg"
                 placeholder="Search group / title / function"
                 value="<?= html_escape($keyword ?? '') ?>" autocomplete="off">
          <datalist id="rbacSuggestions"></datalist>
        </div>
        <div class="col-auto">
          <button type="button" id="rbacSearchBtn" class="btn btn-sm btn-primary">
            <i class="fas fa-search"></i> Search
          </button>
          <?php if (!empty($keyword)): ?>
            <a href="<?= site_url('rbac/rbac') ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
          <?php endif; ?>
        </div>
      </div>

      <div class="row g-4">
        <!-- Left column of groups -->
        <div class="col-lg-6">
          <div class="accordion rbac-accordion" id="rbacAccordionLeft">
            <?php foreach ($leftGroups as $groupName): ?>
              <?php
                $slug = rbac_slug($groupName) ?: uniqid('grp-');
              ?>
              <div class="accordion-item rbac-group" data-search-group="<?= htmlspecialchars(strtolower($groupName), ENT_QUOTES, 'UTF-8') ?>">
                <h2 class="accordion-header text-sm" id="heading-<?= $slug ?>">
                  <button class="accordion-button collapsed fw-normal py-1" type="button"
                          data-bs-target="#collapse-<?= $slug ?>" data-target="#collapse-<?= $slug ?>"
                          aria-expanded="false" aria-controls="collapse-<?= $slug ?>">
                    <?= html_escape($groupName) ?>
                  </button>
                </h2>
                <div id="collapse-<?= $slug ?>" class="accordion-collapse collapse"
                     aria-labelledby="heading-<?= $slug ?>" data-bs-parent="#rbacAccordionLeft">
                  <div class="accordion-body p-0">
                    <table class="table table-sm table-bordered mb-0 rbac-group-table text-xxs">
                      <thead class="table-light">
                        <tr>
                          <th>Module Title</th>
                          <th>Function Name</th>
                          <th class="text-center" style="width:100px;">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($grouped[$groupName] as $row): ?>
                          <?php $showActionButtons = ((int) ($row->id ?? 0) === (int) $latestModuleId); ?>
                          <tr class="rbac-row" data-id="<?= (int) $row->id ?>"
                              data-module-group="<?= htmlspecialchars($row->module_group ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-module-title="<?= htmlspecialchars($row->module_title ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-module-function="<?= htmlspecialchars($row->function_name ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-status="<?= (int) ($row->status ?? 0) ?>"
                              data-search-text="<?= htmlspecialchars(strtolower(($row->module_group ?? '') . ' ' . ($row->module_title ?? '') . ' ' . ($row->function_name ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                            <td><?= html_escape($row->module_title ?? '') ?> [<?= (int) ($row->id ?? 0) ?>]</td>
                            <td><?= html_escape($row->function_name ?? '') ?></td>
                            <td class="text-center rbac-actions">
                              <?php if ($showActionButtons): ?>
                                <div class="rbac-action-buttons d-inline-flex align-items-center gap-1">
                                  <button type="button"
                                          class="btn btn-sm btn-outline-primary btn-edit-module"
                                          title="Edit"
                                          data-id="<?= (int) $row->id ?>"
                                          data-module-group="<?= htmlspecialchars($row->module_group ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                          data-module-title="<?= htmlspecialchars($row->module_title ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                          data-module-function="<?= htmlspecialchars($row->function_name ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                          data-status="<?= (int) ($row->status ?? 0) ?>">
                                    <i class="fas fa-pen"></i>
                                  </button>
                                  <a href="<?= site_url('rbac/rbac_delete/' . $row->id) ?>"
                                     class="btn btn-sm btn-outline-danger btn-delete-module"
                                     title="Delete">
                                    <i class="fas fa-trash"></i>
                                  </a>
                                </div>
                              <?php else: ?>
                                <span class="text-muted">—</span>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Right column of groups -->
        <div class="col-lg-6">
          <div class="accordion rbac-accordion" id="rbacAccordionRight">
            <?php foreach ($rightGroups as $groupName): ?>
              <?php
                $slug = rbac_slug($groupName) ?: uniqid('grp-');
              ?>
              <div class="accordion-item rbac-group" data-search-group="<?= htmlspecialchars(strtolower($groupName), ENT_QUOTES, 'UTF-8') ?>">
                <h2 class="accordion-header text-sm" id="heading-<?= $slug ?>">
                  <button class="accordion-button collapsed fw-normal py-1" type="button"
                          data-bs-target="#collapse-<?= $slug ?>" data-target="#collapse-<?= $slug ?>"
                          aria-expanded="false" aria-controls="collapse-<?= $slug ?>">
                    <?= html_escape($groupName) ?>
                  </button>
                </h2>
                <div id="collapse-<?= $slug ?>" class="accordion-collapse collapse"
                     aria-labelledby="heading-<?= $slug ?>" data-bs-parent="#rbacAccordionRight">
                  <div class="accordion-body p-0">
                    <table class="table table-sm table-bordered mb-0 rbac-group-table text-xxs">
                      <thead class="table-light">
                        <tr>
                          <th>Module Title</th>
                          <th>Function Name</th>
                          <th class="text-center" style="width:100px;">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($grouped[$groupName] as $row): ?>
                          <?php $showActionButtons = ((int) ($row->id ?? 0) === (int) $latestModuleId); ?>
                          <tr class="rbac-row" data-id="<?= (int) $row->id ?>"
                              data-module-group="<?= htmlspecialchars($row->module_group ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-module-title="<?= htmlspecialchars($row->module_title ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-module-function="<?= htmlspecialchars($row->function_name ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-status="<?= (int) ($row->status ?? 0) ?>"
                              data-search-text="<?= htmlspecialchars(strtolower(($row->module_group ?? '') . ' ' . ($row->module_title ?? '') . ' ' . ($row->function_name ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                            <td><?= html_escape($row->module_title ?? '') ?> [<?= (int) ($row->id ?? 0) ?>]</td>
                            <td><?= html_escape($row->function_name ?? '') ?></td>
                            <td class="text-center">
                              <?php if ($showActionButtons): ?>
                                <div class="rbac-action-buttons d-inline-flex align-items-center gap-1">
                                  <button type="button"
                                          class="btn btn-sm btn-outline-primary btn-edit-module"
                                          title="Edit"
                                          data-id="<?= (int) $row->id ?>"
                                          data-module-group="<?= htmlspecialchars($row->module_group ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                          data-module-title="<?= htmlspecialchars($row->module_title ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                          data-module-function="<?= htmlspecialchars($row->function_name ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                          data-status="<?= (int) ($row->status ?? 0) ?>">
                                    <i class="fas fa-pen"></i>
                                  </button>
                                  <a href="<?= site_url('rbac/rbac_delete/' . $row->id) ?>"
                                     class="btn btn-sm btn-outline-danger btn-delete-module" title="Delete">
                                    <i class="fas fa-trash"></i>
                                  </a>
                                </div>
                              <?php else: ?>
                                <span class="text-muted">—</span>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div id="rbacNoResults" class="text-center text-muted py-4 d-none">No modules found.</div>

    </div>
  </div>
</div>

<div class="modal fade" id="rbacModuleModal" tabindex="-1" aria-labelledby="rbacModuleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="rbacModuleForm" method="post" action="<?= site_url('rbac/rbac_add') ?>">
        <input type="hidden" name="status" id="rbac_status" value="0">
        <div class="modal-header border-bottom">
          <h5 class="modal-title text-dark" id="rbacModuleModalLabel">Add Module</h5>
          <button type="button" class="btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">&times;</button>
        </div>

     <div class="modal-body">

    <?php if (validation_errors()): ?>
        <div class="alert alert-danger">
            <?= validation_errors() ?>
        </div>
    <?php endif; ?>

    <input type="hidden" id="rbac_module_id">

    <div class="row g-3">
            <div class="col-md-6">
              <label for="rbac_module_group" class="form-label">Module Group</label>
              <input type="text" class="form-control" id="rbac_module_group" name="module_group"
                     list="rbacGroupSuggestions" autocomplete="off" required>
              <datalist id="rbacGroupSuggestions"></datalist>
            </div>
            <div class="col-md-6">
              <label for="rbac_module_title" class="form-label">Module Title</label>
              <input type="text" class="form-control" id="rbac_module_title" name="module_title" autocomplete="off" required>
            </div>
            <div class="col-md-6">
              <label for="rbac_function_name" class="form-label">Function Name</label>
              <input type="text" class="form-control" id="rbac_function_name" name="function_name" autocomplete="off" required>
            </div>
          </div>
        </div>

        <div class="modal-body pt-2">
          <div id="rbacDuplicateWarning" class="alert alert-warning py-2 mb-0 d-none" role="alert"></div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" id="rbacSubmitBtn">Save Module</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var modalEl          = document.getElementById('rbacModuleModal');
  var form              = document.getElementById('rbacModuleForm');
  var modalTitle        = document.getElementById('rbacModuleModalLabel');
  var submitBtn         = document.getElementById('rbacSubmitBtn');
  var moduleIdInput     = document.getElementById('rbac_module_id');
  var moduleGroupInput  = document.getElementById('rbac_module_group');
  var moduleTitleInput  = document.getElementById('rbac_module_title');
  var functionNameInput = document.getElementById('rbac_function_name');
  var noResultsEl        = document.getElementById('rbacNoResults');
  var searchInput        = document.getElementById('rbacSearch');
  var searchBtn          = document.getElementById('rbacSearchBtn');
  var suggestList        = document.getElementById('rbacSuggestions');
  var groupSuggestList   = document.getElementById('rbacGroupSuggestions');
  var duplicateWarning   = document.getElementById('rbacDuplicateWarning');
  var editBaseUrl        = '<?= site_url('rbac/rbac_edit') ?>';
  var addBaseUrl         = '<?= site_url('rbac/rbac_add') ?>';

  // ------------------------------------------------------------------
  // Version-agnostic modal open/close.
  // Works whether the page ships Bootstrap 5 (window.bootstrap),
  // Bootstrap 4 + jQuery ($.fn.modal), or (as a last resort) neither.
  // ------------------------------------------------------------------
  function showModal(el) {
    if (window.bootstrap && window.bootstrap.Modal) {
      window.bootstrap.Modal.getOrCreateInstance(el).show();
      return;
    }
    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
      window.jQuery(el).modal('show');
      return;
    }
    console.warn('RBAC modal: neither window.bootstrap nor jQuery.fn.modal was found. ' +
                  'Falling back to manual show — please verify bootstrap.bundle.min.js ' +
                  '(or jQuery + bootstrap.js) is actually included on this page.');
    el.style.display = 'block';
    el.classList.add('show');
    el.removeAttribute('aria-hidden');
    document.body.classList.add('modal-open');
    if (!document.getElementById('rbacManualBackdrop')) {
      var backdrop = document.createElement('div');
      backdrop.id = 'rbacManualBackdrop';
      backdrop.className = 'modal-backdrop fade show';
      document.body.appendChild(backdrop);
    }
  }

  function hideModal(el) {
    if (window.bootstrap && window.bootstrap.Modal) {
      var instance = window.bootstrap.Modal.getInstance(el);
      if (instance) instance.hide();
      return;
    }
    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
      window.jQuery(el).modal('hide');
      return;
    }
    el.style.display = 'none';
    el.classList.remove('show');
    document.body.classList.remove('modal-open');
    var backdrop = document.getElementById('rbacManualBackdrop');
    if (backdrop) backdrop.remove();
  }

  // Version-agnostic accordion collapse expand/close (used by search and
  // by clicking a module group name).
  function expandCollapse(collapseEl) {
    collapseEl.classList.add('show');
    var btn = document.querySelector('[data-bs-target="#' + collapseEl.id + '"], [data-target="#' + collapseEl.id + '"]');
    if (btn) {
      btn.classList.remove('collapsed');
      btn.setAttribute('aria-expanded', 'true');
    }
  }

  function collapseCollapse(collapseEl) {
    collapseEl.classList.remove('show');
    var btn = document.querySelector('[data-bs-target="#' + collapseEl.id + '"], [data-target="#' + collapseEl.id + '"]');
    if (btn) {
      btn.classList.add('collapsed');
      btn.setAttribute('aria-expanded', 'false');
    }
  }

  // ------------------------------------------------------------------
  // Clicking a module group name expands/collapses it. Handled fully
  // by hand (rather than relying on data-bs-toggle) so it works the
  // same whether or not Bootstrap's JS collapse plugin is loaded.
  // ------------------------------------------------------------------
  document.querySelectorAll('.rbac-accordion .accordion-button').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();

      var targetSel = btn.getAttribute('data-bs-target') || btn.getAttribute('data-target');
      if (!targetSel) return;

      var collapseEl = document.querySelector(targetSel);
      if (!collapseEl) return;

      var isOpen = collapseEl.classList.contains('show');

      // Accordion behavior: opening one group closes the others in the
      // same column (mirrors the old data-bs-parent behavior).
      var container = btn.closest('.accordion');
      if (container) {
        container.querySelectorAll('.accordion-collapse.show').forEach(function (openEl) {
          if (openEl !== collapseEl) collapseCollapse(openEl);
        });
      }

      if (isOpen) {
        collapseCollapse(collapseEl);
      } else {
        expandCollapse(collapseEl);
      }
    });
  });

  // All permission rows across both accordion columns.
  function allRows() {
    return document.querySelectorAll('.rbac-row[data-id]');
  }

  function buildSuggestions() {
    var values = {};
    var groups = {};

    allRows().forEach(function (row) {
      ['data-module-group', 'data-module-title', 'data-module-function'].forEach(function (attr) {
        var value = row.getAttribute(attr);
        if (value) values[value.trim()] = true;
      });
      var group = row.getAttribute('data-module-group');
      if (group) groups[group.trim()] = true;
    });

    if (suggestList) {
      suggestList.innerHTML = '';
      Object.keys(values).sort().forEach(function (v) {
        var opt = document.createElement('option');
        opt.value = v;
        suggestList.appendChild(opt);
      });
    }
    if (groupSuggestList) {
      groupSuggestList.innerHTML = '';
      Object.keys(groups).sort().forEach(function (v) {
        var opt = document.createElement('option');
        opt.value = v;
        groupSuggestList.appendChild(opt);
      });
    }
  }

  function checkDuplicate() {
    if (!functionNameInput || !duplicateWarning || !submitBtn) {
      return;
    }

    var funcName = (functionNameInput.value || '').trim();
    var currentId = moduleIdInput && moduleIdInput.value ? String(moduleIdInput.value).trim() : '';

    duplicateWarning.classList.add('d-none');
    duplicateWarning.textContent = '';
    submitBtn.disabled = false;

    if (!funcName) {
      return;
    }

    var duplicateFound = false;
    allRows().forEach(function (row) {
      var rowId = String(row.getAttribute('data-id') || '').trim();
      var rowFunction = (row.getAttribute('data-module-function') || '').trim();

      if (currentId && rowId && rowId === currentId) {
        return;
      }

      if (rowFunction.toLowerCase() === funcName.toLowerCase()) {
        duplicateFound = true;
      }
    });

    if (duplicateFound) {
      duplicateWarning.textContent = 'This function name already exists. Function names must be unique.';
      duplicateWarning.classList.remove('d-none');
      submitBtn.disabled = true;
    }
  }

  // ------------------------------------------------------------------
  // Search is click/Enter triggered only — no live/AJAX filtering as
  // the user types.
  // ------------------------------------------------------------------
  function runSearch() {
    var keyword = (searchInput ? searchInput.value : '').trim().toLowerCase();
    var anyGroupVisible = false;

    document.querySelectorAll('.rbac-group').forEach(function (groupEl) {
      var collapseEl = groupEl.querySelector('.accordion-collapse');
      var rows = groupEl.querySelectorAll('.rbac-row');
      var groupHasMatch = false;

      if (!keyword) {
        // No keyword: show every group/row, collapse back to default state.
        rows.forEach(function (row) { row.style.display = ''; });
        groupEl.style.display = '';
        if (collapseEl) collapseCollapse(collapseEl);
        anyGroupVisible = true;
        return;
      }

      rows.forEach(function (row) {
        var searchText = (row.getAttribute('data-search-text') || '').toLowerCase();
        var isMatch = searchText.indexOf(keyword) !== -1;
        row.style.display = isMatch ? '' : 'none';
        if (isMatch) groupHasMatch = true;
      });

      groupEl.style.display = groupHasMatch ? '' : 'none';

      if (groupHasMatch) {
        anyGroupVisible = true;
        if (collapseEl) expandCollapse(collapseEl);
      } else if (collapseEl) {
        collapseCollapse(collapseEl);
      }
    });

    if (noResultsEl) {
      noResultsEl.classList.toggle('d-none', anyGroupVisible);
    }
  }

  if (searchInput) {
    // Enter key still triggers a search, but typing alone does not.
    searchInput.addEventListener('keydown', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        runSearch();
      }
    });
  }
  if (searchBtn) searchBtn.addEventListener('click', runSearch);

  buildSuggestions();
  runSearch(); // apply any keyword pre-filled from the server (?keyword=...)

  if (moduleGroupInput && functionNameInput) {
    moduleGroupInput.addEventListener('input', checkDuplicate);
    functionNameInput.addEventListener('input', checkDuplicate);
  }

  if (duplicateWarning) {
    duplicateWarning.classList.add('d-none');
  }

  function resetForm() {
    if (!form) return;
    form.reset();
    moduleIdInput.removeAttribute('name');
    moduleIdInput.value = '';
    modalTitle.textContent = 'Add Module';
    submitBtn.textContent = 'Save Module';
    submitBtn.disabled = false;
    duplicateWarning.classList.add('d-none');
    duplicateWarning.textContent = '';
    form.setAttribute('action', addBaseUrl);
  }

  function openAddModal() {
    resetForm();
    showModal(modalEl);
  }

  function openEditModal(button) {
    var id = button.getAttribute('data-id');
    moduleIdInput.setAttribute('name', 'id');
    moduleIdInput.value = id;
    moduleGroupInput.value = button.getAttribute('data-module-group') || '';
    moduleTitleInput.value = button.getAttribute('data-module-title') || '';
    functionNameInput.value = button.getAttribute('data-module-function') || '';
    modalTitle.textContent = 'Edit Module';
    submitBtn.textContent = 'Update Module';
    submitBtn.disabled = false;
    duplicateWarning.classList.add('d-none');
    duplicateWarning.textContent = '';
    form.setAttribute('action', editBaseUrl + '/' + id);
    checkDuplicate();
    showModal(modalEl);
  }

  // ---- Add button (direct listener as a backup to data-toggle attrs) ----
  document.querySelectorAll('.btn-add-module').forEach(function (button) {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      openAddModal();
    });
  });

  // ---- Edit / Delete via event delegation (rows live in two accordions) ----
  document.body.addEventListener('click', function (e) {
    var editBtn = e.target.closest('.btn-edit-module');
    var delBtn  = e.target.closest('.btn-delete-module');

    if (editBtn) {
      e.preventDefault();
      openEditModal(editBtn);
    }

    if (delBtn) {
      if (!confirm('Delete this module? This cannot be undone.')) {
        e.preventDefault();
      }
    }
  });

  // ---- close buttons inside modal (backup for data-dismiss) ----
  if (modalEl) {
    modalEl.querySelectorAll('[data-dismiss="modal"], [data-bs-dismiss="modal"]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        hideModal(modalEl);
      });
    });

    modalEl.addEventListener('hidden.bs.modal', resetForm);
    if (window.jQuery) {
      window.jQuery(modalEl).on('hidden.bs.modal', resetForm);
    }
  }
});
</script>