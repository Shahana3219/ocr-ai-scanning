<?php $prefill = $prefill ?? []; ?>

<!-- =====================================================================
     PAGE-SPECIFIC STYLES (scope under .invoice-page to avoid conflicts)
     ===================================================================== -->
<style>
/* ---- Card section headers ---- */
.inv-section-title {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: #fff;
  background: linear-gradient(195deg, #42424a, #191919);
  border-radius: 8px 8px 0 0;
  padding: 10px 16px;
  margin: 0;
}
.inv-card {
  border-radius: 0 0 8px 8px;
  border: 1px solid #e9ecef;
  background: #fff;
  padding: 16px 20px 10px;
  margin-bottom: 20px;
}
/* ---- Labels ---- */
.inv-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #7b809a;
  margin-bottom: 3px;
  display: block;
}
/* ---- Inputs inherit form-control but smaller ---- */
.inv-card .form-control,
.inv-card select.form-control {
  font-size: 0.82rem;
  padding: 6px 10px;
  height: auto;
  margin-bottom: 0;
  border-radius: 6px;
}
.inv-field-warning,
.select2-container .select2-selection.inv-field-warning {
  border-color: #f59e0b !important;
  box-shadow: 0 0 0 1px rgba(245, 158, 11, .18);
}
/* ---- Totals box ---- */
.totals-table td {
  padding: 5px 10px;
  font-size: 0.83rem;
  border-bottom: 1px solid #f0f0f0;
}
.totals-table tr:last-child td { border-bottom: none; }
.totals-table .tot-label { color: #7b809a; font-weight: 600; width: 55%; }
.totals-table .tot-val   { text-align: right; font-weight: 700; color: #344767; }
.totals-table .grand-row td { background: #f8f9fa; font-size: 0.92rem; }

/* ---- Line-items table ---- */
#itemsTable { font-size: 0.78rem; }
#itemsTable thead th {
  background: linear-gradient(195deg, #42424a, #191919);
  color: #fff;
  font-size: 0.7rem;
  letter-spacing: .06em;
  text-transform: uppercase;
  padding: 8px 6px;
  border: none;
  white-space: nowrap;
}
#itemsTable tbody tr:hover { background: #f8f9ff; }
#itemsTable tbody td {
  padding: 4px 4px;
  vertical-align: middle;
  border-bottom: 1px solid #f0f0f0;
}
#itemsTable tbody td input,
#itemsTable tbody td select {
  font-size: 0.78rem;
  padding: 4px 6px;
  height: auto;
  margin-bottom: 0;
}
/* Review modal fields: same look as .inv-card fields (modal lives outside the card) */
#reviewModal .form-control,
#reviewModal select.form-control,
#reviewModal textarea.form-control {
  font-size: 0.82rem;
  padding: 6px 10px;
  height: auto;
  margin-bottom: 0;
  border: 1px solid #d2d6da;
  border-radius: 6px;
  background: #fff;
}
#reviewModal .row > [class*="col-"] { margin-bottom: 10px; }
#reviewModal .select2-selection__clear {
  position: absolute;
  top: 50%;
  right: 24px;
  z-index: 2;
  transform: translateY(-50%);
  color: #dc3545;
  font-size: 1rem;
}
.select2-container { width: 100% !important; }
.select2-container--default .select2-selection--single {
  min-height: 36px;
  border: 1px solid #d2d6da;
  border-radius: 6px;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
  line-height: 34px;
  padding-left: 10px;
  padding-right: 44px;
  font-size: 0.82rem;
}
.select2-container--default .select2-selection--single .select2-selection__arrow { height: 34px; }
.select2-dropdown { z-index: 11001; }

/* ---- Modals ---- */
.inv-modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  z-index: 1050;
  justify-content: center;
  align-items: center;
  backdrop-filter: blur(2px);
}
.inv-modal-overlay.active {
  display: flex;
}
.inv-modal-box {
  background: #fff;
  border-radius: 12px;
  padding: 24px;
  width: 100%;
  max-width: 600px;
  overflow: visible;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  max-height: 90vh;
  overflow-y: auto;
}
.inv-modal-box.wide { max-width: 900px; }
.inv-modal-hdr { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.inv-modal-hdr h5 { margin: 0; font-weight: 700; font-size: 1rem; color: #344767; }
.inv-modal-close { cursor: pointer; font-size: 1.4rem; color: #7b809a; line-height: 1; background: none; border: none; }
.inv-modal-close:hover { color: #344767; }

/* mini review table inside modal */
table.rev-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 0.78rem; }
table.rev-table th { background: #f8f9fa; color: #344767; padding: 6px; font-weight: 700; border: 1px solid #dee2e6; white-space: nowrap; }
table.rev-table td { border: 1px solid #dee2e6; padding: 4px 5px; vertical-align: middle; }
#reviewModal .inv-modal-box.wide { max-width: 1280px; width: 96vw; }
#reviewItemsTable { min-width: 1180px; table-layout: fixed; }
#reviewItemsTable th,
#reviewItemsTable td { vertical-align: top !important; }
#reviewItemsTable th:nth-child(1) { width: 17%; }
#reviewItemsTable th:nth-child(2) { width: 14%; }
#reviewItemsTable th:nth-child(3) { width: 15%; }
#reviewItemsTable th:nth-child(4) { width: 11%; }
#reviewItemsTable th:nth-child(5) { width: 9%; }
#reviewItemsTable th:nth-child(6) { width: 9%; }
#reviewItemsTable th:nth-child(7) { width: 6%; }
#reviewItemsTable th:nth-child(8) { width: 8%; }
#reviewItemsTable th:nth-child(9) { width: 9%; }
#reviewItemsTable th:nth-child(10) { width: 4%; }
#reviewItemsTable .select2-container--default .select2-selection--single .select2-selection__rendered {
  text-overflow: ellipsis;
  padding-right: 44px !important;
}
#reviewItemsTable tbody td > input[type=date] { padding: 6px 4px !important; }
#reviewItemsTable tbody td > input,
#reviewItemsTable tbody td > select,
#reviewItemsTable tbody td > .select2-container {
  display: block;
  width: 100% !important;
  min-width: 0 !important;
  max-width: 100%;
  height: 36px;
  min-height: 36px;
  box-sizing: border-box;
  margin: 0;
}
#reviewItemsTable tbody td > input,
#reviewItemsTable .select2-container--default .select2-selection--single {
  border: 1px solid #d2d6da;
  border-radius: 6px;
  background: #fff;
}
#reviewItemsTable tbody td > input {
  padding: 6px !important;
  font-size: .75rem;
  line-height: 22px;
}
#reviewItemsTable .select2-container--default .select2-selection--single {
  height: 36px;
  min-height: 36px;
}
#reviewItemsTable .select2-container--default .select2-selection--single .select2-selection__rendered {
  line-height: 34px;
  padding-left: 8px;
  padding-right: 20px;
  font-size: .75rem;
}
#reviewItemsTable .select2-container--default .select2-selection--single .select2-selection__arrow {
  height: 34px;
}
#reviewItemsTable .rev-item-match-status {
  margin-top: 4px !important;
  line-height: 1.3;
  overflow-wrap: anywhere;
}
#reviewItemsTable .btn-show-all-items { line-height: 1.3; }
#reviewItemsTable .btnRemoveReviewItem {
  width: 30px !important;
  min-width: 30px !important;
  height: 30px !important;
  min-height: 30px !important;
  padding: 3px 6px !important;
}

/* ---- OCR Loader ---- */
.ocr-loader-overlay { 
  display:none;
  position:fixed; 
  inset:0; 
  background:rgba(15,23,42,.72); 
  z-index:11000; 
  justify-content:center; 
  align-items:center; 
  backdrop-filter:blur(2px); 
}
.ocr-loader-overlay.active {
  display: flex;
}
.ocr-loader-box { background:rgba(255,255,255,.97); padding:28px 34px; border-radius:16px; max-width:360px; width:100%; text-align:center; box-shadow:0 18px 50px rgba(15,23,42,.22); }
.ocr-loader-spinner { width:52px; height:52px; margin:0 auto 16px; border:5px solid rgba(59,130,246,.18); border-top-color:#2563eb; border-radius:50%; animation:spin 1s linear infinite; }
.ocr-loader-text { font-size:0.95rem; color:#111827; line-height:1.6; margin-bottom:4px; font-weight:600; }
.ocr-loader-subtext { font-size:0.8rem; color:#6b7280; }
.ocr-loader-progress { height:5px; margin:18px 0 12px; overflow:hidden; background:#e5e7eb; border-radius:999px; }
.ocr-loader-progress::after { content:""; display:block; width:38%; height:100%; border-radius:inherit; background:linear-gradient(90deg,#2563eb,#60a5fa); animation:ocr-progress 1.5s ease-in-out infinite; }
.ocr-review-alert { display:flex; align-items:flex-start; gap:10px; padding:12px 14px; margin-bottom:16px; border:1px solid #fca5a5; border-left:5px solid #dc2626; border-radius:7px; background:#fef2f2; color:#991b1b; font-size:.84rem; line-height:1.5; }
.ocr-review-alert .material-icons-round { flex:0 0 auto; font-size:1.2rem; }
.ocr-review-alert strong { color:#7f1d1d; }
@keyframes spin { to { transform:rotate(360deg); } }
@keyframes ocr-progress { 0% { transform:translateX(-110%); } 100% { transform:translateX(290%); } }

/* Flash messages */
.inv-flash { padding: 10px 14px; border-radius: 6px; margin-bottom: 14px; font-size: 0.83rem; }
.inv-flash-success { background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; }
.inv-flash-error   { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
#pocrFlashMessages { position: fixed; top: 16px; right: 16px; z-index: 12001; width: min(420px, calc(100vw - 32px)); }
#pocrFlashMessages .inv-flash { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; box-shadow: 0 8px 24px rgba(15,23,42,.18); }
.inv-flash-close { border: 0; background: transparent; color: inherit; cursor: pointer; font-size: 1rem; line-height: 1; padding: 0; }

/* Bank details table */
.bank-table td { padding: 6px 10px; font-size: 0.82rem; }
.bank-table td:first-child { color: #7b809a; font-weight: 600; width: 35%; }

/* Info badge */
.inv-badge { display:inline-block; padding: 2px 8px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; }

/* Vendor add modal - specific */
.vendor-form-group { margin-bottom: 12px; }

/* link-style button */
.link-btn { background:none; border:none; color:#3b82f6; font-size:0.75rem; text-decoration:underline; cursor:pointer; padding:0; margin-top:2px; }

/* File Preview Styles */
#filePreviewContainer {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px dashed #d1d5db;
}
#filePreviewContent {
    max-width: 100%;
    max-height: 500px;
    overflow: auto;
    text-align: center;
}
#filePreviewImage {
    max-width: 100%;
    max-height: 450px;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.file-detail-item {
    padding: 4px 0;
    font-size: 0.82rem;
    border-bottom: 1px solid #f0f0f0;
}
.file-detail-item:last-child { border-bottom: none; }
.file-detail-item strong { color: #344767; }
</style>

<div class="container-fluid py-2">

  <div id="pocrFlashMessages" aria-live="polite"></div>

  <!-- Flash messages -->
  <?php if($this->session->flashdata('error')): ?>
    <div class="inv-flash inv-flash-error"><?= html_escape($this->session->flashdata('error')); ?></div>
  <?php endif; ?>
  <?php if($this->session->flashdata('success')): ?>
    <div class="inv-flash inv-flash-success"><?= html_escape($this->session->flashdata('success')); ?></div>
  <?php endif; ?>

<!-- ==========================================================
     OCR UPLOAD MODAL
     ========================================================== -->
<div class="inv-modal-overlay active" id="uploadModal">
  <div class="inv-modal-box">
    <div class="inv-modal-hdr">
      <h5><i class="material-icons-round align-middle" style="font-size:1rem">upload_file</i> Upload Expense Document (OCR)</h5>
      <button type="button" class="inv-modal-close" id="btnCloseModal">&times;</button>
    </div>

    <?= form_open_multipart('expocr/upload_invoice_ocr', ['id'=>'ocrForm']); ?>
    <div class="mb-3 mt-2">
      <label class="inv-label">Select Bill or Expense File</label>
      <input type="file" name="invoice_file" id="invoice_file" class="form-control"
             accept=".png,.jpg,.jpeg,.pdf" required>
    </div>
    <div class="ocr-review-alert" role="alert">
      <i class="material-icons-round" aria-hidden="true">warning</i>
      <div><strong>Please verify all extracted information.</strong> OCR may misread the expense voucher. Check every item, description, unit, quantity, rate, tax, total, supplier and expense number before confirming.</div>
    </div>
    <div class="card bg-light p-3" style="border-radius:8px;font-size:0.78rem;color:#374151;line-height:1.7">
      <strong>How this works:</strong><br>
      • Upload expense voucher → Preview the file<br>
      • Confirm → System extracts data automatically<br>
      • Review &amp; edit the extracted fields<br>
      • Confirm to fill the form<br><br>
      <strong>Rules:</strong><br>
      • Max file size: <strong>5 MB</strong><br>
      • Formats: PNG, JPG, JPEG, PDF<br>
      • Clear, well-lit images give best results
    </div>
    <div class="d-flex justify-content-end mt-3">
      <button type="button" class="btn btn-info mb-0" id="btnContinueOCR">
        <i class="material-icons-round" style="font-size:.9rem;vertical-align:middle">chevron_right</i>
        Preview File
      </button>
    </div>
    <?= form_close(); ?>
  </div>
</div>

<!-- ==========================================================
     FILE PREVIEW MODAL
     ========================================================== -->
<div class="inv-modal-overlay" id="filePreviewModal">
  <div class="inv-modal-box wide">
    <div class="inv-modal-hdr">
      <h5><i class="material-icons-round align-middle" style="font-size:1rem">image</i> Preview Expense Voucher File</h5>
      <button type="button" class="inv-modal-close" id="btnCloseFilePreview">&times;</button>
    </div>
    <div class="ocr-review-alert" role="alert">
      <i class="material-icons-round" aria-hidden="true">warning</i>
      <div><strong>OCR results are not guaranteed to be correct.</strong> Review the extracted values against this expense voucher image before saving.</div>
    </div>
    
    <div class="row">
      <div class="col-md-8">
        <div id="filePreviewContainer">
          <div id="filePreviewContent">
            <img id="filePreviewImage" src="" alt="Expense Voucher Preview" style="max-width:100%; max-height:450px; display:none;">
            <div id="filePreviewPdf" style="display:none;">
              <i class="material-icons-round" style="font-size:64px; color:#6b7280;">picture_as_pdf</i>
              <p class="mt-2">PDF file selected. Click "Process OCR" to extract data.</p>
            </div>
            <div id="filePreviewPlaceholder" style="color:#9ca3af; padding:60px;">
              <i class="material-icons-round" style="font-size:64px;">upload_file</i>
              <p class="mt-2">No file selected</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card p-3" style="background:#f8f9fa; border-radius:8px;">
          <h6 style="font-weight:700; color:#344767;">File Details</h6>
          <hr class="my-2">
          <div id="fileDetails">
            <div class="file-detail-item"><strong>Name:</strong> <span id="previewFileName">-</span></div>
            <div class="file-detail-item"><strong>Size:</strong> <span id="previewFileSize">-</span></div>
            <div class="file-detail-item"><strong>Type:</strong> <span id="previewFileType">-</span></div>
          </div>
          <div class="mt-3" style="border-top:1px solid #dee2e6; padding-top:12px;">
            <p style="font-size:0.78rem; color:#6b7280; margin-bottom:0;">
              <i class="material-icons-round" style="font-size:1rem; vertical-align:middle;">info</i>
              Review the file before processing
            </p>  
          </div>
        </div>
      </div>
    </div>
    
    <div class="d-flex justify-content-end mt-3" style="gap:8px">
      <button type="button" class="btn btn-outline-secondary btn-sm mb-0" id="btnBackToUpload">← Back to Upload</button>
      <button type="button" class="btn btn-info btn-sm mb-0" id="btnProcessOCR">
        <i class="material-icons-round" style="font-size:.9rem;vertical-align:middle">auto_awesome</i>
        Process OCR
      </button>
    </div>
  </div>
</div>

<!-- ==========================================================
     REVIEW MODAL (expense voucher fields)
     ========================================================== -->
<div class="inv-modal-overlay" id="reviewModal">
  <div class="inv-modal-box wide">
    <div class="inv-modal-hdr">
      <h5><i class="material-icons-round align-middle" style="font-size:1rem">rate_review</i> Review Expense Data</h5>
      <button type="button" class="inv-modal-close" id="btnCloseReview">&times;</button>
    </div>
    <div class="ocr-review-alert" role="alert">
      <i class="material-icons-round" aria-hidden="true">warning</i>
      <div><strong>Check and correct every field before continuing.</strong> OCR can make mistakes. Compare the profile, categories, amounts and tax with the original document.</div>
    </div>

    <div class="row g-2">
      <div class="col-md-3 col-sm-6">
        <label class="inv-label">Date</label>
        <input type="date" id="rev_expense_date" class="form-control">
      </div>
      <div class="col-md-3 col-sm-6">
        <label class="inv-label">Branch Name</label>
        <select id="rev_branch_id" class="form-control"></select>
      </div>
      <div class="col-md-3 col-sm-6">
        <label class="inv-label">Filter</label>
        <select id="rev_filter" class="form-control">
          <option value=""></option>
          <option value="2">Vendors</option>
          <option value="0">Customers</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="inv-label">Select Profile</label>
        <div class="row align-items-center">
          <div class="col-9"><select id="rev_profile_id" class="form-control"></select></div>
        
        </div>
        <div id="revProfileMatchStatus" class="small text-muted mt-1" role="status"></div>
        <button type="button" class="link-btn" id="btnShowAllProfiles" style="display:none">Show all profiles</button>
      </div>
      <div class="col-md-6" id="paymentToWrap">
        <label class="inv-label">Payment To</label>
        <input type="text" id="rev_payment_to" class="form-control" placeholder="Enter payee name">
      </div>

      <div class="col-md-4 col-sm-6">
        <label class="inv-label">Mode of Payment</label>
        <select id="rev_mop" class="form-control"></select>
        <div id="revMopBalance" class="small text-muted mt-1" role="status" aria-live="polite"></div>
      </div>
      <div class="col-md-4 col-sm-6">
        <label class="inv-label">Project</label>
        <select id="rev_project_id" class="form-control"></select>
      </div>
      <div class="col-md-4 col-sm-6">
        <label class="inv-label">Job</label>
        <select id="rev_job_id" class="form-control"></select>
      </div>
      <div class="col-md-12">
        <label class="inv-label">Note</label>
        <textarea id="rev_note" class="form-control" rows="2"></textarea>
      </div>
    </div>
    <input type="hidden" id="reference_no_prefill" value="<?= isset($prefill_ref) ? html_escape($prefill_ref) : '' ?>">

    <hr class="my-2">
    <div class="d-flex justify-content-between align-items-center">
      <label class="inv-label mb-0">Expense Lines</label>
      <button type="button" class="btn btn-outline-info btn-sm mb-0" id="btnAddLine">+ Add Line</button>
    </div>
    <div class="table-responsive">
      <table class="rev-table" id="reviewItemsTable">
        <thead>
          <tr>
            <th>Category</th><th>Cost Center</th><th>Description</th><th>Date</th>
            <th>Bill Number</th><th>Amount</th><th>Tax %</th><th>Tax</th><th>Total</th><th></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
    <div class="text-end mt-2"><strong>Total Amount: <span id="revGrandTotal">0.00</span></strong></div>

    <div class="d-flex justify-content-end mt-3">
      <button type="button" class="btn btn-info mb-0" id="btnGoToPreview">
        Continue to Preview <i class="material-icons-round" style="font-size:.9rem;vertical-align:middle">chevron_right</i>
      </button>
    </div>
  </div>
</div>

<!-- ==========================================================
     PREVIEW MODAL
     ========================================================== -->
<div class="inv-modal-overlay" id="previewModal">
  <div class="inv-modal-box wide">
    <div class="inv-modal-hdr">
      <h5><i class="material-icons-round align-middle" style="font-size:1rem">preview</i> Preview Expense Data</h5>
      <span class="inv-modal-close" id="btnClosePreview">&times;</span>
    </div>
    <div class="ocr-review-alert" role="alert">
      <i class="material-icons-round" aria-hidden="true">warning</i>
      <div><strong>Final check before saving.</strong> Confirm the profile and every line match the original document.</div>
    </div>
    <div class="card bg-light p-3 mb-3" id="previewSummary" style="font-size:0.82rem"></div>
    <div class="table-responsive">
      <table class="rev-table" id="previewItemsTable">
        <thead>
          <tr>
            <th>Category</th><th>Cost Center</th><th>Description</th><th>Date</th>
            <th>Bill Number</th><th>Amount</th><th>Tax %</th><th>Tax</th><th>Total</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
    <div class="d-flex justify-content-end mt-3" style="gap:8px">
      <button type="button" class="btn btn-outline-secondary btn-sm mb-0" id="btnBackToReview">← Back to Edit</button>
      <button type="button" class="btn btn-success btn-sm mb-0" id="btnConfirmFillForm">
        <i class="material-icons-round" style="font-size:.9rem;vertical-align:middle">check_circle</i>
        Confirm &amp; Save Voucher
      </button>
    </div>
  </div>
</div>

<!-- OCR Loader Overlay -->
<div class="ocr-loader-overlay" id="ocrLoader" role="status" aria-live="polite" aria-busy="true">
  <div class="ocr-loader-box">
    <div class="ocr-loader-spinner" aria-hidden="true"></div>
    <div class="ocr-loader-text" id="ocrLoaderText">Preparing OCR...</div>
    <div class="ocr-loader-progress" aria-hidden="true"></div>
    <div class="ocr-loader-subtext" id="ocrLoaderSubtext">This can take a little while. Your document is being processed; please keep this page open.</div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  window.ALL_CUSTOMERS   = <?= json_encode($customers   ?? []) ?>;
  window.ALL_VENDORS     = <?= json_encode($vendors     ?? []) ?>;
  window.ALL_PROJECTS    = <?= json_encode($projects    ?? []) ?>;
  window.ALL_CATEGORIES  = <?= json_encode($accounts    ?? []) ?>;
  window.ALL_COSTCENTERS = <?= json_encode($costcenters ?? []) ?>;
  window.ALL_ACCOUNTS    = <?= json_encode($accounts    ?? []) ?>;
  window.ALL_JOBS        = <?= json_encode($jobs        ?? []) ?>;
  window.ALL_BRANCHES    = <?= json_encode($branches    ?? []) ?>;
  window.SESSION_BRANCH  = <?= json_encode((string)($session_branch_id ?? '')) ?>;
</script>

<script>
$(function(){

  // ---------- helpers ----------
  function showFlash(type, message){
    const $flash = $('<div class="inv-flash"></div>')
      .addClass(type === 'success' ? 'inv-flash-success' : 'inv-flash-error')
      .attr('role', type === 'success' ? 'status' : 'alert');
    $('<span></span>').text(message || 'Something went wrong. Please try again.').appendTo($flash);
    $('<button type="button" class="inv-flash-close" aria-label="Dismiss">&times;</button>')
      .on('click', function(){ $flash.remove(); }).appendTo($flash);
    $('#pocrFlashMessages').empty().append($flash);
    window.setTimeout(function(){ $flash.fadeOut(250, function(){ $(this).remove(); }); }, 7000);
  }
  function getRequestError(xhr, fallback){
    return (xhr.responseJSON && xhr.responseJSON.message) || fallback + ' (HTTP ' + xhr.status + ').';
  }
  function escapeHtml(str){ return $('<div>').text(str == null ? '' : str).html(); }
  function num(v){ v = String(v == null ? '' : v).replace(/,/g,'').replace(/[^\d.-]/g,''); return parseFloat(v) || 0; }
  function showModal(id){ $('#'+id).addClass('active'); }
  function hideModal(id){ $('#'+id).removeClass('active'); }
  function showOcrLoader(msg, sub){
    $('#ocrLoaderText').text(msg || 'Processing your document...');
    if(sub) $('#ocrLoaderSubtext').text(sub);
    $('#ocrLoader').attr('aria-busy','true').addClass('active');
  }
  function hideOcrLoader(){ $('#ocrLoader').removeClass('active').attr('aria-busy','false'); }

  document.addEventListener('mousedown', function(e){
    const clearButton = e.target instanceof Element ? e.target.closest('#reviewModal .select2-selection__clear') : null;
    if(!clearButton) return;
    const container = clearButton.closest('.select2-container');
    const select = container && container.previousElementSibling;
    if(!select || select.tagName !== 'SELECT') return;
    e.preventDefault();
    e.stopImmediatePropagation();
    $(select).val('').trigger('input').trigger('change');
  }, true);

  $('#btnCloseModal').on('click', function(){ hideModal('uploadModal'); });
  $('#uploadModal').on('click', function(e){ if(e.target===this) hideModal('uploadModal'); });
  $('#btnCloseFilePreview').on('click', function(){ hideModal('filePreviewModal'); showModal('uploadModal'); });
  $('#filePreviewModal').on('click', function(e){ if(e.target===this){ hideModal('filePreviewModal'); showModal('uploadModal'); } });
  $('#btnCloseReview').on('click', function(){ hideModal('reviewModal'); });
  $('#btnClosePreview').on('click', function(){ hideModal('previewModal'); });
  $('#btnBackToUpload').on('click', function(){ hideModal('filePreviewModal'); showModal('uploadModal'); });
  $('#btnBackToReview').on('click', function(){ hideModal('previewModal'); showModal('reviewModal'); });
  $('#ocrForm').on('submit', function(e){ e.preventDefault(); });

  // ---------- searchable selects ----------
  function initSelect($s, placeholder, parent){
    if($s.hasClass('select2-hidden-accessible')) $s.select2('destroy');
    $s.select2({ width:'100%', placeholder:placeholder, allowClear:true, dropdownParent: parent || $(document.body) });
  }
  function setOptions($s, list, placeholder, selectedId){
    if($s.hasClass('select2-hidden-accessible')) $s.select2('destroy');
    $s.empty().append(new Option('', '', !selectedId, !selectedId));
    (list||[]).forEach(function(it){
      const sel = selectedId != null && selectedId !== '' && String(it.id) === String(selectedId);
      $s.append(new Option(it.name || '', it.id, sel, sel));
    });
    initSelect($s, placeholder, $('#reviewModal'));
  }
  function optionsHtml(list, selectedId, blank){
    let h = '<option value="">'+blank+'</option>';
    (list||[]).forEach(function(it){
      h += '<option value="'+escapeHtml(String(it.id))+'"'+(String(it.id)===String(selectedId)?' selected':'')+'>'+escapeHtml(it.name)+'</option>';
    });
    return h;
  }

  setOptions($('#rev_branch_id'),  window.ALL_BRANCHES, 'Select branch...', window.SESSION_BRANCH);
  setOptions($('#rev_mop'),        window.ALL_ACCOUNTS, 'Select mode of payment...');
  setOptions($('#rev_project_id'), window.ALL_PROJECTS, 'Select project...');
  setOptions($('#rev_job_id'),     window.ALL_JOBS,     'Select job...');

  let balanceRequest = null;
  let balanceRequestId = 0;
  $('#rev_mop').on('change', function(){
    const accountId = $(this).val();
    const requestId = ++balanceRequestId;
    if(balanceRequest){
      balanceRequest.abort();
      balanceRequest = null;
    }
    if(!accountId){
      $('#revMopBalance').text('').removeClass('text-danger');
      return;
    }

    $('#revMopBalance').text('Loading balance...').removeClass('text-danger');
    balanceRequest = $.ajax({
      url: '<?= site_url("expocr/get_account_balance") ?>',
      type: 'POST',
      dataType: 'json',
      data: { account_id: accountId }
    }).done(function(res){
      if(requestId !== balanceRequestId) return;
      balanceRequest = null;
      if(res.status !== 'success' || !Number.isFinite(Number(res.balance))){
        $('#revMopBalance').text('Balance unavailable').addClass('text-danger');
        showFlash('error', res.message || 'Could not retrieve the account balance.');
        return;
      }
      const balance = Number(res.balance).toLocaleString(undefined, {
        minimumFractionDigits: 3,
        maximumFractionDigits: 3
      });
      $('#revMopBalance').text('Balance: ' + balance);
    }).fail(function(xhr, status){
      if(status === 'abort' || requestId !== balanceRequestId) return;
      balanceRequest = null;
      $('#revMopBalance').text('Balance unavailable').addClass('text-danger');
      showFlash('error', getRequestError(xhr, 'Could not retrieve the account balance'));
    });
  });

  // ---------- profile (vendor / customer filter) ----------
  let currentOcrRes = null;
  let lastBill = '';
  function profileList(){
    const f = $('#rev_filter').val();
    return f === '0' ? window.ALL_CUSTOMERS : (f === '2' ? window.ALL_VENDORS : allProfiles());
  }
  function profileMatches(){
    if(!currentOcrRes) return [];
    const f = $('#rev_filter').val();
    if(f === '0') return currentOcrRes.customer_matches || [];
    if(f === '2') return currentOcrRes.vendor_matches || [];
    return (currentOcrRes.vendor_matches||[]).concat(currentOcrRes.customer_matches||[]);
  }
  // selectedId: profile to keep selected ('' = none). Nothing is ever auto-picked except an OCR match on first load.
  function loadProfiles(selectedId){
    const matches = profileMatches();
    const list = (selectedId === 'auto' && matches.length) ? matches : profileList();
    const sel = selectedId === 'auto' ? (matches[0] ? matches[0].id : '') : selectedId;
    setOptions($('#rev_profile_id'), list, 'Search profiles...', sel);
    $('#btnShowAllProfiles').toggle(selectedId === 'auto' && matches.length > 0).text('Show all profiles');
    $('#revProfileMatchStatus').text(selectedId === 'auto' && matches.length ? matches.length+' similar match'+(matches.length===1?'':'es')+' found.' : '');
    togglePaymentTo();
  }
  // Payment To is shown only while no profile is selected.
  function togglePaymentTo(){ $('#paymentToWrap').toggle(!$('#rev_profile_id').val()); }
  // Filter: searchable, with an x to clear (clear = show every vendor + customer)
  function allProfiles(){ return (window.ALL_VENDORS||[]).concat(window.ALL_CUSTOMERS||[]); }
  initSelect($('#rev_filter'), 'All', $('#reviewModal'));
  $('#rev_profile_id').on('change', function(){ if(!$(this).val()) $('#revProfileMatchStatus').text(''); togglePaymentTo(); });
  $('#rev_filter').on('change', function(){
    const cur = $('#rev_profile_id').val() || '';
    const keep = profileList().some(function(it){ return String(it.id) === String(cur); }) ? cur : '';
    loadProfiles(keep);
  });
  $('#btnShowAllProfiles').on('click', function(){
    setOptions($('#rev_profile_id'), profileList(), 'Search all profiles...', $('#rev_profile_id').val());
    $('#revProfileMatchStatus').text('');
    $(this).hide();
  });

  // ---------- line rows ----------
  function buildRow(it){
    it = it || {};
    const style = 'font-size:.75rem;padding:3px 6px';
    return '<tr>'+
      '<td style="min-width:140px"><select class="form-control rev-cat">'+optionsHtml(window.ALL_CATEGORIES, it.category_id, '-- Category --')+'</select></td>'+
      '<td style="min-width:120px"><select class="form-control rev-cc">'+optionsHtml(window.ALL_COSTCENTERS, it.cost_center_id, '-- Cost Center --')+'</select></td>'+
      '<td><input type="text" class="form-control rev-desc" value="'+escapeHtml(it.description)+'" style="'+style+';min-width:120px"></td>'+
      '<td><input type="date" class="form-control rev-date" value="'+escapeHtml(it.item_date)+'" style="'+style+'"></td>'+
      '<td><input type="text" class="form-control rev-bill" value="'+escapeHtml(it.bill_number)+'" style="'+style+';min-width:80px"></td>'+
      '<td><input type="number" step="0.01" class="form-control rev-amount" value="'+escapeHtml(it.amount)+'" style="'+style+';width:85px"></td>'+
      '<td><input type="number" step="0.01" class="form-control rev-taxperc" value="'+escapeHtml(it.tax_perc != null ? it.tax_perc : 15)+'" style="'+style+';width:60px"></td>'+
      '<td><input type="number" step="0.01" class="form-control rev-tax" value="" readonly style="'+style+';width:75px;background:#f9fafb"></td>'+
      '<td><input type="number" step="0.01" class="form-control rev-total" value="" readonly style="'+style+';width:85px;background:#f9fafb"></td>'+
      '<td><button type="button" class="btn btn-sm btn-outline-danger mb-0 btnRemoveLine" title="Remove line" aria-label="Remove expense line" style="padding:2px 6px"><i class="material-icons-round" style="font-size:.8rem">delete</i></button></td>'+
      '</tr>';
  }
  function addRow(it){
    const $row = $(buildRow(it));
    $row.find('.btnRemoveLine').on('click', function(){
      $row.remove();
      recalcGrand();
    });
    $('#reviewItemsTable tbody').append($row);
    initSelect($row.find('.rev-cat'), 'Category...', $('#reviewModal'));
    initSelect($row.find('.rev-cc'), 'Cost center...', $('#reviewModal'));
    recalcRow($row);
  }
  function recalcRow($tr){
    const amount = num($tr.find('.rev-amount').val());
    const perc   = num($tr.find('.rev-taxperc').val());
    const tax    = Math.round(amount * perc) / 100;
    $tr.find('.rev-tax').val(tax.toFixed(2));
    $tr.find('.rev-total').val((amount + tax).toFixed(2));
    recalcGrand();
  }
  function recalcGrand(){
    let g = 0;
    $('#reviewItemsTable tbody .rev-total').each(function(){ g += num($(this).val()); });
    $('#revGrandTotal').text(g.toFixed(2));
  }
  $(document).on('input change', '.rev-amount, .rev-taxperc', function(){ recalcRow($(this).closest('tr')); });
  $('#btnAddLine').on('click', function(){
    addRow({ item_date: $('#rev_expense_date').val(), bill_number: lastBill, tax_perc: 15 });
  });

  // ---------- file select / preview ----------
  let selectedFile = null;

  $('#btnContinueOCR').on('click', function(){
    const input = $('#invoice_file')[0];
    if(!input.files.length){ showFlash('error','Please select a file.'); return; }
    const f = input.files[0];
    const ext = (f.name.split('.').pop()||'').toLowerCase();
    if(['png','jpg','jpeg','pdf'].indexOf(ext) === -1){ showFlash('error','Invalid file type. Allowed: PNG, JPG, JPEG, PDF.'); return; }
    if(f.size > 5*1024*1024){ showFlash('error','File must be below 5 MB.'); return; }
    selectedFile = f;
    showFilePreview(f);
    hideModal('uploadModal');
    showModal('filePreviewModal');
  });

  function showFilePreview(file){
    $('#previewFileName').text(file.name);
    $('#previewFileSize').text((file.size/1024).toFixed(1)+' KB');
    $('#previewFileType').text(file.type || 'Unknown');
    const ext = (file.name.split('.').pop()||'').toLowerCase();
    $('#filePreviewPlaceholder').hide();
    if(ext === 'pdf'){
      $('#filePreviewImage').hide();
      $('#filePreviewPdf').show();
    } else {
      const reader = new FileReader();
      reader.onload = function(e){ $('#filePreviewImage').attr('src', e.target.result).show(); $('#filePreviewPdf').hide(); };
      reader.readAsDataURL(file);
    }
  }

  // ---------- OCR (reuses Pocr upload + run_ocr) ----------
  const btnOcrHtml = '<i class="material-icons-round" style="font-size:.9rem;vertical-align:middle">auto_awesome</i> Process OCR';
  function ocrDone(){ $('#btnProcessOCR').prop('disabled', false).html(btnOcrHtml); hideOcrLoader(); }

  $('#btnProcessOCR').on('click', function(){
    if(!selectedFile){ showFlash('error','No file selected. Please upload a file first.'); return; }
    $(this).prop('disabled', true).text('Processing...');
    showOcrLoader('Uploading your document...', 'Securely sending the file for processing. Please keep this page open.');
    const fd = new FormData();
    fd.append('invoice_file', selectedFile);
    $.ajax({
      url: '<?= site_url("expocr/upload_invoice_ocr") ?>', type:'POST', data:fd, processData:false, contentType:false, dataType:'json',
      success: function(res){
        if(res.status !== 'success'){ ocrDone(); showFlash('error','Upload failed: '+(res.message||'Unknown error')); return; }
        showOcrLoader('Reading your document...', 'Identifying details and line items. Processing time depends on the file.');
        $.ajax({
          url: '<?= site_url("expocr/run_ocr") ?>', type:'POST', dataType:'json',
          data: { file_data:res.file_data, file_name:res.file_name, mime_type:res.mime_type },
          success: function(ocrRes){
            ocrDone();
            if(ocrRes.status === 'success'){ $('#pocrFlashMessages').empty(); hideModal('filePreviewModal'); openReviewModal(ocrRes); }
            else showFlash('error','OCR failed: '+(ocrRes.message||'Unknown error'));
          },
          error: function(xhr){ ocrDone(); showFlash('error', getRequestError(xhr,'OCR processing failed')); }
        });
      },
      error: function(xhr){ ocrDone(); showFlash('error', getRequestError(xhr,'Server error during upload')); }
    });
  });

  function openReviewModal(ocrRes){
    currentOcrRes = ocrRes;
    const p = ocrRes.prefill || {};
    const items = (ocrRes.parsed && ocrRes.parsed.line_items) || [];
    // Bill number: header-level value, else first line that has one; fill blank lines with it.
    const firstBill = (items.filter(function(it){ return $.trim(it.bill_number); })[0] || {}).bill_number;
    lastBill = $.trim(p.bill_number || firstBill || '');
    items.forEach(function(it){ if(!$.trim(it.bill_number)) it.bill_number = lastBill; });

    $('#rev_expense_date').val(p.expense_date || '');
    $('#rev_branch_id').val(p.branch_id || window.SESSION_BRANCH || '').trigger('change');
    $('#rev_note').val(p.note || '');
    $('#rev_mop').val(p.mop_id || '').trigger('change');
    $('#rev_project_id').val(p.project_id || '').trigger('change');
    $('#rev_job_id').val(p.job_id || '').trigger('change');

    // Vendors by default; Customers when the form said so, or only a customer matched.
    const vm = ocrRes.vendor_matches || [], cm = ocrRes.customer_matches || [];
    const useCust = (!vm.length && cm.length) || (p.filter === '0' && cm.length);
    // No profile in the document -> leave filter and profile empty.
    $('#rev_filter').val(vm.length || cm.length ? (useCust ? '0' : '2') : '').trigger('change.select2');
    loadProfiles('auto');
    $('#rev_payment_to').val(p.profile_name || '');
    togglePaymentTo();

    $('#reviewItemsTable tbody').empty();
    if(items.length){
      items.forEach(function(it){
                if(!it.item_date) it.item_date = p.expense_date || '';
        addRow(it);
      });
    } else {
      addRow({ item_date: p.expense_date || '', amount: p.total_amount || '', tax_perc: 15 });
    }

    if(ocrRes.warning) showFlash('error', ocrRes.warning);
    showModal('reviewModal');
  }
  // ---------- collect / validate / preview ----------
  function collectData(){
    const lines = [];
    $('#reviewItemsTable tbody tr').each(function(){
      const $tr = $(this);
      lines.push({
        category_id: $tr.find('.rev-cat').val() || '',
        category_label: $tr.find('.rev-cat option:selected').text(),
        cost_center_id: $tr.find('.rev-cc').val() || '',
        cost_center_label: $tr.find('.rev-cc option:selected').text(),
        description: $tr.find('.rev-desc').val(),
        item_date: $tr.find('.rev-date').val(),
        bill_number: $tr.find('.rev-bill').val(),
        amount: $tr.find('.rev-amount').val(),
        tax_perc: $tr.find('.rev-taxperc').val(),
        tax_amt: $tr.find('.rev-tax').val(),
        total: $tr.find('.rev-total').val()
      });
    });
    return {
      expense_date: $('#rev_expense_date').val(),
      branch_id: $('#rev_branch_id').val() || '',
      branch_text: $('#rev_branch_id').val() ? $('#rev_branch_id option:selected').text() : '',
      filter_type: $('#rev_filter').val(),
      profile_id: $('#rev_profile_id').val(),
      profile_text: $('#rev_profile_id').val() ? $('#rev_profile_id option:selected').text() : '',
      payment_to: $('#rev_profile_id').val() ? '' : $.trim($('#rev_payment_to').val()),
      mode_of_payment: $('#rev_mop').val(),
      mop_text: $('#rev_mop option:selected').text(),
      project_id: $('#rev_project_id').val(),
      project_text: $('#rev_project_id option:selected').text(),
      job_id: $('#rev_job_id').val(),
      job_text: $('#rev_job_id option:selected').text(),
      note: $('#rev_note').val(),
      lines: lines
    };
  }

  function validate(){
    if(!$('#rev_profile_id').val() && !$.trim($('#rev_payment_to').val())){ showFlash('error','Please select a profile or enter Payment To.'); $('#rev_payment_to').trigger('focus'); return false; }
    if(!$('#rev_branch_id').val()){ showFlash('error','Please select a branch.'); $('#rev_branch_id').select2('open'); return false; }
    if(!$('#rev_mop').val()){ showFlash('error','Please select a mode of payment before continuing.'); $('#rev_mop').select2('open'); return false; }
    if(!$('#reviewItemsTable tbody tr').length){ showFlash('error','Please add at least one expense line.'); return false; }
    const $noCat = $('#reviewItemsTable .rev-cat').filter(function(){ return !$(this).val(); }).first();
    if($noCat.length){ showFlash('error','Please select a category for every expense line before continuing.'); $noCat.select2('open'); return false; }
    const $noAmt = $('#reviewItemsTable .rev-amount').filter(function(){ return num($(this).val()) <= 0; }).first();
    if($noAmt.length){ showFlash('error','Enter an amount greater than 0 for every expense line.'); $noAmt.trigger('focus'); return false; }
    const $noTotal = $('#reviewItemsTable .rev-total').filter(function(){ return num($(this).val()) <= 0; }).first();
    if($noTotal.length){ showFlash('error','Each expense line must have a total greater than 0. Please check the amount and tax.'); $noTotal.trigger('focus'); return false; }
    return true;
  }

  let reviewedData = null;
  $('#btnGoToPreview').on('click', function(){
    if(!validate()) return;
    reviewedData = collectData();
    const d = reviewedData;
    let grand = 0;
    d.lines.forEach(function(l){ grand += num(l.total); });
    $('#previewSummary').html(
      '<div class="row g-2" style="font-size:0.8rem">'+
      '<div class="col-md-4"><strong>Date:</strong> '+escapeHtml(d.expense_date)+'</div>'+
      '<div class="col-md-4"><strong>Branch:</strong> '+escapeHtml(d.branch_text)+'</div>'+
      (d.profile_id
        ? '<div class="col-md-4"><strong>'+(d.filter_type==='0'?'Customer':'Vendor')+':</strong> '+escapeHtml(d.profile_text)+'</div>'
        : '<div class="col-md-4"><strong>Payment To:</strong> '+escapeHtml(d.payment_to)+'</div>')+
      '<div class="col-md-4"><strong>Mode of Payment:</strong> '+escapeHtml(d.mop_text)+'</div>'+
      '<div class="col-md-4"><strong>Project:</strong> '+escapeHtml(d.project_text)+'</div>'+
      '<div class="col-md-4"><strong>Job:</strong> '+escapeHtml(d.job_text)+'</div>'+
      '<div class="col-md-4"><strong>Total Amount:</strong> '+grand.toFixed(2)+'</div>'+
      '<div class="col-md-12"><strong>Note:</strong> '+escapeHtml(d.note)+'</div>'+
      '</div>'
    );
    const $tb = $('#previewItemsTable tbody').empty();
    d.lines.forEach(function(l){
      $tb.append('<tr><td>'+escapeHtml(l.category_label)+'</td><td>'+escapeHtml(l.cost_center_id ? l.cost_center_label : '')+'</td>'+
        '<td>'+escapeHtml(l.description)+'</td><td>'+escapeHtml(l.item_date)+'</td><td>'+escapeHtml(l.bill_number)+'</td>'+
        '<td>'+escapeHtml(l.amount)+'</td><td>'+escapeHtml(l.tax_perc)+'</td><td>'+escapeHtml(l.tax_amt)+'</td><td>'+escapeHtml(l.total)+'</td></tr>');
    });
    hideModal('reviewModal');
    showModal('previewModal');
  });

  // ---------- save + redirect to Add Expense ----------
  $('#btnConfirmFillForm').on('click', function(){
    if(!reviewedData) return;
    const d = reviewedData;
    const $btn = $(this).prop('disabled', true).text('Saving...');
    showOcrLoader('Saving the expense...', 'Validating the details and saving your expense lines. Please keep this page open.');
    const fd = new FormData();
    fd.append('reference_id', $('#reference_no_prefill').val() || '');
    fd.append('expense_date', d.expense_date);
    fd.append('branch_id', d.branch_id || '');
    fd.append('filter_type', d.filter_type);
    fd.append('profile_id', d.profile_id || '');
    fd.append('payment_to', d.payment_to || '');
    fd.append('mode_of_payment', d.mode_of_payment);
    fd.append('project_id', d.project_id || '');
    fd.append('job_id', d.job_id || '');
    fd.append('note', d.note);
    fd.append('items_json', JSON.stringify(d.lines));
    if(selectedFile) fd.append('document', selectedFile);
    $.ajax({
      url: '<?= site_url("expocr/save_expense_ocr") ?>', type:'POST', dataType:'json',
      data: fd, processData:false, contentType:false,
      success: function(res){
        $btn.prop('disabled', false).text('Confirm & Save Voucher');
        hideOcrLoader();
        if(res.status === 'success'){
          hideModal('previewModal');
          if(res.redirect_url){ showFlash('success', res.message); setTimeout(function(){ window.location.href = res.redirect_url; }, 1500); }
          else showFlash('success', res.message);
        } else showFlash('error','Save failed: '+(res.message||'Unknown error'));
      },
      error: function(xhr){
        $btn.prop('disabled', false).text('Confirm & Save Voucher');
        hideOcrLoader();
        showFlash('error', getRequestError(xhr,'Server error while saving'));
      }
    });
  });

});
</script>
