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
.select2-container { width: 100% !important; }
.select2-container--default .select2-selection--single {
  min-height: 36px;
  border: 1px solid #d2d6da;
  border-radius: 6px;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
  line-height: 34px;
  padding-left: 10px;
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
#reviewItemsTable { min-width: 760px; table-layout: fixed; }
#reviewItemsTable th,
#reviewItemsTable td { vertical-align: top !important; }
#reviewItemsTable th:nth-child(1) { width: 16%; }
#reviewItemsTable th:nth-child(2) { width: 13%; }
#reviewItemsTable th:nth-child(3) { width: 18%; }
#reviewItemsTable th:nth-child(4) { width: 7%; }
#reviewItemsTable th:nth-child(5) { width: 11%; }
#reviewItemsTable th:nth-child(6) { width: 9%; }
#reviewItemsTable th:nth-child(7) { width: 8%; }
#reviewItemsTable th:nth-child(8) { width: 11%; }
#reviewItemsTable th:nth-child(9) { width: 7%; }
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
      <h5><i class="material-icons-round align-middle" style="font-size:1rem">upload_file</i> Upload Invoice (OCR)</h5>
      <button type="button" class="inv-modal-close" id="btnCloseModal">&times;</button>
    </div>

    <?= form_open_multipart('pocr/upload_invoice_ocr', ['id'=>'ocrForm']); ?>
    <div class="mb-3 mt-2">
      <label class="inv-label">Select Invoice File</label>
      <input type="file" name="invoice_file" id="invoice_file" class="form-control"
             accept=".png,.jpg,.jpeg,.pdf" required>
    </div>
    <div class="ocr-review-alert" role="alert">
      <i class="material-icons-round" aria-hidden="true">warning</i>
      <div><strong>Please verify all extracted information.</strong> OCR may misread the invoice. Check every item, description, unit, quantity, rate, tax, total, supplier and invoice number before confirming.</div>
    </div>
    <div class="card bg-light p-3" style="border-radius:8px;font-size:0.78rem;color:#374151;line-height:1.7">
      <strong>How this works:</strong><br>
      • Upload invoice → Preview the file<br>
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
      <h5><i class="material-icons-round align-middle" style="font-size:1rem">image</i> Preview Invoice File</h5>
      <button type="button" class="inv-modal-close" id="btnCloseFilePreview">&times;</button>
    </div>
    <div class="ocr-review-alert" role="alert">
      <i class="material-icons-round" aria-hidden="true">warning</i>
      <div><strong>OCR results are not guaranteed to be correct.</strong> Review the extracted values against this invoice image before saving.</div>
    </div>
    
    <div class="row">
      <div class="col-md-8">
        <div id="filePreviewContainer">
          <div id="filePreviewContent">
            <img id="filePreviewImage" src="" alt="Invoice Preview" style="max-width:100%; max-height:450px; display:none;">
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
     REVIEW MODAL (edit OCR data)
     ========================================================== -->
<div class="inv-modal-overlay" id="reviewModal">
  <div class="inv-modal-box wide">
    <div class="inv-modal-hdr">
      <h5><i class="material-icons-round align-middle" style="font-size:1rem">rate_review</i> Review Extracted Data</h5>
      <button type="button" class="inv-modal-close" id="btnCloseReview">&times;</button>
    </div>
    <div class="ocr-review-alert" role="alert">
      <i class="material-icons-round" aria-hidden="true">warning</i>
      <div><strong>Check and correct every field before continuing.</strong> OCR can make mistakes. Compare item descriptions, units, quantities, unit rates, discounts, VAT and totals with the original invoice.</div>
    </div>

    <div class="row g-2">
      <div class="col-md-3 col-sm-6">
        <label class="inv-label">Invoice Date</label>
        <input type="date" id="rev_invoice_date" class="form-control">
      </div>
      <div class="col-md-3 col-sm-6">
        <label class="inv-label">Time</label>
        <input type="time" id="rev_invoice_time" class="form-control">
      </div>
      <div class="col-md-3 col-sm-6">
        <label class="inv-label">Due Date</label>
        <input type="date" id="rev_due_date" class="form-control">
      </div>
      <div class="col-md-3 col-sm-6">
        <label class="inv-label">Currency</label>
        <input type="text" id="rev_currency" class="form-control">
      </div>
      <div class="col-md-4 col-sm-6">
        <label class="inv-label">Doc No / Order No</label>
        <input type="text" id="rev_order_no" class="form-control">
      </div>
      <div class="col-md-4 col-sm-6">
        <label class="inv-label">Reference No</label>
        <input type="text" id="rev_reference_no" class="form-control">
      </div>
      <div class="col-md-4 col-sm-6">
        <label class="inv-label">Supplier Invoice No</label>
        <input type="text" id="rev_supplier_invoice_no" class="form-control">
      </div>
      <div class="col-md-4 col-sm-6">
        <label class="inv-label">Contract / PO No</label>
        <input type="text" id="rev_contract_po_no" class="form-control">
      </div>
      <div class="col-md-4 col-sm-6">
        <label class="inv-label">Contract / PO Date</label>
        <input type="date" id="rev_contract_po_date" class="form-control">
      </div>
      <div class="col-md-4 col-sm-6">
        <label class="inv-label">Other Reference No</label>
        <input type="text" id="rev_other_reference_no" class="form-control">
      </div>

      <!-- Customer -->
      <div class="col-md-6 d-none">
        <label class="inv-label">Customer</label>
        <select id="rev_customer_id" class="form-control"></select>
      </div>
      <!-- Vendor -->
      <div class="col-md-6">
        <label class="inv-label">Vendor</label>
        <div class="row align-items-center">
          <div class="col-9">
            <select id="rev_vendor_id" class="form-control" required></select>
          </div>
          <div class="col-3 text-right">
            <a href="javascript:void(0);" onclick="window.open('<?= site_url('home/newvendor') ?>','invoiceWindow','width=700,height=700,scrollbars=yes,resizable=yes');">
              <span class="badge badge-sm bg-gradient-primary" style="cursor:pointer;">+ New Vendor</span>
            </a>
          </div>
        </div>
        <div id="revVendorMatchStatus" class="small text-muted mt-1" role="status"></div>
        <button type="button" class="link-btn" id="btnShowAllVendors" style="display:none">Show all vendors</button>
      </div>

      <div class="col-md-4 col-sm-6 d-none">
        <label class="inv-label">Customer VAT Reg. No</label>
        <input type="text" id="rev_vat" class="form-control">
      </div>
      <div class="col-md-4 col-sm-6">
        <label class="inv-label">Vendor VAT No</label>
        <input type="text" id="rev_vendor_vat" class="form-control">
      </div>
      <div class="col-md-4 d-none">
        <label class="inv-label">Address (Customer)</label>
        <textarea id="rev_address" class="form-control" rows="2"></textarea>
      </div>
      <div class="col-md-4">
        <label class="inv-label">Address (Vendor)</label>
        <textarea id="rev_vendor_address" class="form-control" rows="2"></textarea>
      </div>
    </div>
<input type="hidden" name="reference_no_prefill" id="reference_no_prefill"
       value="<?= isset($prefill_ref) ? html_escape($prefill_ref) : '' ?>">
    <hr class="my-2">
    <label class="inv-label">Line Items</label>
    <div class="table-responsive">
      <table class="rev-table" id="reviewItemsTable">
        <thead>
          <tr>
            <th>Item</th><th>Unit</th><th>Description</th>
            <th>Qty</th><th>Unit Rate</th><th>Discount</th>
            <th>VAT %</th><th>Total</th><th></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>

    <div class="d-flex justify-content-end mt-3">
      <button type="button" class="btn btn-info mb-0" id="btnGoToPreview">
        Continue to Preview <i class="material-icons-round" style="font-size:.9rem;vertical-align:middle">chevron_right</i>
      </button>
    </div>
  </div>
</div>

<!-- ==========================================================
     PREVIEW MODAL (read-only final check)
     ========================================================== -->
<div class="inv-modal-overlay" id="previewModal">
  <div class="inv-modal-box wide">
    <div class="inv-modal-hdr">
      <h5><i class="material-icons-round align-middle" style="font-size:1rem">preview</i> Preview Invoice Data</h5>
      <span class="inv-modal-close" id="btnClosePreview">&times;</span>
    </div>
    <div class="ocr-review-alert" role="alert">
      <i class="material-icons-round" aria-hidden="true">warning</i>
      <div><strong>Final check before saving.</strong> Confirm that the supplier, invoice details and every line-item value match the original document</div>
    </div>
    <div class="card bg-light p-3 mb-3" id="previewSummary" style="font-size:0.82rem"></div>
    <div class="table-responsive">
      <table class="rev-table" id="previewItemsTable">
        <thead>
          <tr>
            <th>Item</th><th>Unit</th><th>Description</th>
            <th>Qty</th><th>Unit Rate</th><th>Discount</th>
            <th>VAT %</th><th>Total</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
    <div class="d-flex justify-content-end mt-3" style="gap:8px">
      <button type="button" class="btn btn-outline-secondary btn-sm mb-0" id="btnBackToReview">← Back to Edit</button>
      <button type="button" class="btn btn-success btn-sm mb-0" id="btnConfirmFillForm">
        <i class="material-icons-round" style="font-size:.9rem;vertical-align:middle">check_circle</i>
        Confirm &amp; Fill Form
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
    <div class="ocr-loader-subtext" id="ocrLoaderSubtext">This can take a little while. Your invoice is being processed; please keep this page open.</div>
  </div>
</div>

<!-- ================================================================
     DATA BRIDGE: PHP → JS
     ================================================================ -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  window.ALL_CUSTOMERS = <?= json_encode($customers ?? []) ?>;
  window.ALL_VENDORS   = <?= json_encode($vendors   ?? []) ?>;
  window.ALL_ITEMS     = <?= json_encode($items     ?? []) ?>;
  window.ALL_PROJECTS  = <?= json_encode($projects  ?? []) ?>;
</script>

<!-- ================================================================
     MAIN JS
     ================================================================ -->
<script>
$(function(){

  function showFlash(type, message){
    const $flash = $('<div class="inv-flash"></div>')
      .addClass(type === 'success' ? 'inv-flash-success' : 'inv-flash-error')
      .attr('role', type === 'success' ? 'status' : 'alert');
    $('<span></span>').text(message || 'Something went wrong. Please try again.').appendTo($flash);
    $('<button type="button" class="inv-flash-close" aria-label="Dismiss">&times;</button>')
      .on('click', function(){ $flash.remove(); })
      .appendTo($flash);
    $('#pocrFlashMessages').empty().append($flash);
    window.setTimeout(function(){ $flash.fadeOut(250, function(){ $(this).remove(); }); }, 7000);
  }

  function getRequestError(xhr, fallback){
    return (xhr.responseJSON && xhr.responseJSON.message) || fallback + ' (HTTP ' + xhr.status + ').';
  }

  <?php if (!empty($prefill_ref)): ?>
    $('input[name="reference_no"]').val('<?= html_escape($prefill_ref) ?>');
  <?php endif; ?>

  // -------------------------------------------------------
  // HELPERS
  // -------------------------------------------------------
  function escapeHtml(str){
    return $('<div>').text(str || '').html();
  }

  // -------------------------------------------------------
  // MODAL CONTROLS
  // -------------------------------------------------------
  function showModal(id){ $('#'+id).addClass('active'); }
  function hideModal(id){ $('#'+id).removeClass('active'); }

  $('#btnCloseModal').on('click', function(){ hideModal('uploadModal'); });
  $('#uploadModal').on('click', function(e){ if(e.target===this) hideModal('uploadModal'); });

  $('#btnCloseFilePreview').on('click', function(){ hideModal('filePreviewModal'); showModal('uploadModal'); });
  $('#filePreviewModal').on('click', function(e){ if(e.target===this){ hideModal('filePreviewModal'); showModal('uploadModal'); } });

  $('#btnCloseReview').on('click', function(){ hideModal('reviewModal'); });
  $('#btnClosePreview').on('click', function(){ hideModal('previewModal'); });

  // -------------------------------------------------------
  // OCR LOADER
  // -------------------------------------------------------
  function showOcrLoader(msg, subtext){
    $('#ocrLoaderText').text(msg || 'Processing your invoice...');
    $('#ocrLoaderSubtext').text(subtext || 'This can take a little while. Your invoice is being processed; please keep this page open.');
    $('#ocrLoader').attr('aria-busy', 'true');
    $('#ocrLoader').addClass('active'); 
  }
  function hideOcrLoader(){
    $('#ocrLoader').removeClass('active');
    $('#ocrLoader').attr('aria-busy', 'false');
  }

  // -------------------------------------------------------
  // DROPDOWN POPULATION
  // -------------------------------------------------------
  function initializeSearchableSelect($select, placeholder, dropdownParent){
    if($select.hasClass('select2-hidden-accessible')){
      $select.select2('destroy');
    }
    $select.select2({
      width: '100%',
      placeholder: placeholder,
      allowClear: true,
      minimumResultsForSearch: 0,
      dropdownParent: dropdownParent || $(document.body)
    });
  }

  function populateSearchableSelect($select, list, placeholder, dropdownParent){
    $select.empty().append(new Option('', '', false, false));
    (list || []).forEach(function(item){
      $select.append(new Option(item.name || '', item.id, false, false));
    });
    initializeSearchableSelect($select, placeholder, dropdownParent);
  }

  function setSearchableSelectOptions($select, list, placeholder, dropdownParent, selectedId){
    if($select.hasClass('select2-hidden-accessible')){
      $select.select2('destroy');
    }
    $select.empty().append(new Option('', '', !selectedId, !selectedId));
    (list || []).forEach(function(item){
      const selected = selectedId !== undefined && selectedId !== null && String(item.id) === String(selectedId);
      $select.append(new Option(item.name || '', item.id, selected, selected));
    });
    initializeSearchableSelect($select, placeholder, dropdownParent);
  }

  function initializeItemRowSelects($row){
    initializeSearchableSelect($row.find('.item-select'), 'Search items...');
    initializeSearchableSelect($row.find('.item-unit'), 'Select unit...');
  }

  populateSearchableSelect($('#rev_customer_id'), window.ALL_CUSTOMERS, 'Search customers...', $('#reviewModal'));
  populateSearchableSelect($('#rev_vendor_id'), window.ALL_VENDORS, 'Search vendors...', $('#reviewModal'));

  // -------------------------------------------------------
  // LINE ITEMS – unit helpers
  // -------------------------------------------------------
  function buildUnitOptions(units, selectedUnitId, selectedUnitLabel){
    units = units || [];
    let html = '';
    let labelMatched = false;
    if(!units.length && !selectedUnitLabel) return '<option value="">--</option>';

    const selectedLabel = String(selectedUnitLabel || '').trim().toLowerCase();
    units.forEach(function(u){
      const unitLabel = u.unit_name || u.symbol || '';
      const matchesId = selectedUnitId && String(selectedUnitId) === String(u.unit_id);
      const matchesLabel = selectedLabel && selectedLabel === String(unitLabel).trim().toLowerCase();
      const sel = matchesId || matchesLabel ? ' selected' : '';
      if(matchesLabel) labelMatched = true;
      html += '<option value="'+u.unit_id+'"'+sel+'>'+escapeHtml(u.unit_name||u.symbol)+'</option>';
    });
    if(selectedUnitLabel && !labelMatched){
      html = '<option value="" selected>'+escapeHtml(selectedUnitLabel)+'</option>' + html;
    }
    if(!html) html = '<option value="">--</option>';
    return html;
  }

  function buildItemOptions(selectedId){
    let html='<option value="">-- Select Item --</option>';
    (window.ALL_ITEMS||[]).forEach(function(item){
      const isSel = String(item.id)===String(selectedId);
      html+='<option value="'+item.id+'"'+(isSel?' selected':'')+'>'+ escapeHtml(item.name)+'</option>';
    });
    return html;
  }

  function buildReviewItemOptions(matches, selectedId){
    let html = '<option value="">-- Select Item --</option>';
    (matches || []).forEach(function(item){
      const selected = String(item.id) === String(selectedId) ? ' selected' : '';
      html += '<option value="'+escapeHtml(String(item.id))+'"'+selected+'>'+escapeHtml(item.name || '')+'</option>';
    });
    return html;
  }

  function configureReviewVendor(ocrRes){
    const p = ocrRes.prefill || {};
    const matches = ocrRes.vendor_matches || [];
    const detectedName = p.vendor_name || '';
    const initialVendorId = p.vendor_id || (matches[0] && matches[0].id) || '';

    setSearchableSelectOptions(
      $('#rev_vendor_id'),
      matches,
      'Search matching vendors...',
      $('#reviewModal'),
      initialVendorId
    );

    // Keep the vendor dropdown as the source of truth; do not show helper text for matches.
    $('#revVendorMatchStatus').text('');
    $('#btnShowAllVendors').show().text('Show all vendors');
  }

  $('#btnShowAllVendors').on('click', function(){
    const $button = $(this);
    const ocrRes = currentOcrRes || {};
    const matches = ocrRes.vendor_matches || [];
    const selectedId = $('#rev_vendor_id').val();
    setSearchableSelectOptions(
      $('#rev_vendor_id'),
      window.ALL_VENDORS || [],
      'Search all vendors...',
      $('#reviewModal'),
      selectedId
    );
    $('#revVendorMatchStatus').text('');
    $button.hide();
  });

  // -------------------------------------------------------
  // LINE ITEMS – build row HTML
  // -------------------------------------------------------
  var itemRowCounter = 0;
  function buildItemRowHtml(it){
    it = it || {};
    itemRowCounter++;
    const item  = (window.ALL_ITEMS||[]).find(x=>String(x.id)===String(it.item_id||''));
    const units = item ? item.units : [];
    const rowNum = itemRowCounter;
    return '<tr>'+
      '<td style="text-align:center;color:#7b809a">'+rowNum+'</td>'+
      '<td><select name="item_id[]" class="form-control item-select" style="min-width:140px">'+buildItemOptions(it.item_id||'')+'</select></td>'+
      '<td><select name="unit_id[]" class="form-control item-unit" style="min-width:70px">'+buildUnitOptions(units,it.unit_id)+'</select></td>'+
      '<td><input type="text" name="description[]" class="form-control item-description" value="'+escapeHtml(it.description||'')+'" style="min-width:130px"></td>'+
      '<td><input type="number" name="qty[]" class="form-control" value="'+(it.quantity??it.qty??1)+'" style="min-width:55px"></td>'+
      '<td><input type="number" step="0.01" name="unit_price[]" class="form-control" value="'+(it.unit_price??'')+'" style="min-width:75px"></td>'+
      '<td><input type="number" step="0.01" name="discount[]" class="form-control" value="'+(it.discount??0)+'" style="min-width:65px"></td>'+
      '<td><input type="number" step="0.01" name="taxable_amount[]" class="form-control" value="'+(it.taxable_amount??'')+'" readonly style="background:#f9fafb;min-width:80px"></td>'+
      '<td><input type="number" step="0.01" name="vat_rate[]" class="form-control" value="'+(it.vat_rate??15)+'" style="min-width:55px"></td>'+
      '<td><input type="number" step="0.01" name="vat_amount[]" class="form-control" value="'+(it.vat_amount??'')+'" readonly style="background:#f9fafb;min-width:75px"></td>'+
      '<td><input type="number" step="0.01" name="total[]" class="form-control" value="'+(it.total??'')+'" readonly style="background:#f9fafb;min-width:80px"></td>'+
      '<td><button type="button" class="btn btn-sm btn-outline-danger mb-0 btnRemove" title="Remove"><i class="material-icons-round" style="font-size:.85rem">delete</i></button></td>'+
      '</tr>';
  }

  const $initialItemRow = $(buildItemRowHtml({ quantity:1, vat_rate:15 }));
  $('#itemsTable tbody').append($initialItemRow);
  initializeItemRowSelects($initialItemRow);

  $(document).on('click', '#addItem', function(e){
    e.preventDefault();
    const $row = $(buildItemRowHtml({ quantity:1, vat_rate:15 }));
    $('#itemsTable tbody').append($row);
    initializeItemRowSelects($row);
    recalcTotals();
  });

  $(document).on('click', '.btnRemove', function(){
    $(this).closest('tr').remove();
    renumberRows();
    recalcTotals();
  });

  function renumberRows(){
    $('#itemsTable tbody tr').each(function(i){
      $(this).find('td:first').text(i+1);
    });
    itemRowCounter = $('#itemsTable tbody tr').length;
  }

  // -------------------------------------------------------
  // ROW CALCULATION
  // -------------------------------------------------------
  $(document).on('keyup change', 'input[name="qty[]"], input[name="unit_price[]"], input[name="discount[]"], input[name="vat_rate[]"]', function(){
    const $tr = $(this).closest('tr');
    const qty      = parseFloat($tr.find('input[name="qty[]"]').val())        || 0;
    const price    = parseFloat($tr.find('input[name="unit_price[]"]').val())  || 0;
    const discount = parseFloat($tr.find('input[name="discount[]"]').val())    || 0;
    const vatPct   = parseFloat($tr.find('input[name="vat_rate[]"]').val())    || 0;
    const taxable  = Math.max(0, qty * price - discount);
    const vatAmt   = taxable * vatPct / 100;
    const total    = taxable + vatAmt;
    $tr.find('input[name="taxable_amount[]"]').val(taxable.toFixed(2));
    $tr.find('input[name="vat_amount[]"]').val(vatAmt.toFixed(2));
    $tr.find('input[name="total[]"]').val(total.toFixed(2));
    recalcTotals();
  });

  // -------------------------------------------------------
  // TOTALS RECALCULATION
  // -------------------------------------------------------
  function recalcTotals(){
    let subtotal=0, tax=0, grand=0;
    $('#itemsTable tbody tr').each(function(){
      subtotal += parseFloat($(this).find('input[name="taxable_amount[]"]').val()) || 0;
      tax      += parseFloat($(this).find('input[name="vat_amount[]"]').val())     || 0;
      grand    += parseFloat($(this).find('input[name="total[]"]').val())          || 0;
    });
    const discount = parseFloat($('#discount_amount').val()) || 0;
    const net      = grand - discount;
    $('#tot_subtotal').text(subtotal.toFixed(2));
    $('#tot_tax').text(tax.toFixed(2));
    $('#tot_grand').text(grand.toFixed(2));
    $('#tot_net').text(net.toFixed(2));
    $('#amount_in_words').val(numberToWords(net));
  }

  $('#discount_amount').on('keyup change', recalcTotals);

  function numberToWords(num){
    if(!num || isNaN(num)) return '';
    num = Math.abs(parseFloat(num.toFixed(2)));
    const whole = Math.floor(num);
    const cents  = Math.round((num - whole)*100);
    const ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
      'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
    const tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    function convert(n){
      if(n < 20)   return ones[n];
      if(n < 100)  return tens[Math.floor(n/10)] + (n%10 ? ' '+ones[n%10] : '');
      if(n < 1000) return ones[Math.floor(n/100)]+' Hundred'+(n%100 ? ' '+convert(n%100) : '');
      if(n < 1000000) return convert(Math.floor(n/1000))+' Thousand'+(n%1000 ? ' '+convert(n%1000) : '');
      return convert(Math.floor(n/1000000))+' Million'+(n%1000000 ? ' '+convert(n%1000000) : '');
    }
    let result = (whole > 0 ? convert(whole)+' SAR' : '');
    if(cents > 0) result += (result?' and ':'')+convert(cents)+' Halalas';
    return result ? result+' Only' : 'Zero';
  }

  // -------------------------------------------------------
  // ITEM SELECT
  // -------------------------------------------------------
  $(document).on('change', '.item-select', function(){
    const $tr = $(this).closest('tr');
    const selectedId   = $(this).val();
    const item         = (window.ALL_ITEMS||[]).find(x=>String(x.id)===String(selectedId));
    const selectedText = $(this).find('option:selected').text();
    if(selectedText && selectedText !== '-- Select Item --'){
      $tr.find('.item-description').val(selectedText);
    }
    const units = item ? item.units : [];
    $tr.find('.item-unit').html(buildUnitOptions(units));
    if(units.length){
      const primary = units.find(u=>u.is_base) || units[0];
      $tr.find('.item-unit').val(primary.unit_id);
      if(primary.sales_price) $tr.find('input[name="unit_price[]"]').val(primary.sales_price).trigger('change');
    }
  });

  $(document).on('change', '.item-unit', function(){
    const $tr = $(this).closest('tr');
    const item = (window.ALL_ITEMS||[]).find(x=>String(x.id)===String($tr.find('.item-select').val()));
    const unit = item&&item.units ? item.units.find(u=>String(u.unit_id)===String($(this).val())) : null;
    if(unit && unit.sales_price!=null && unit.sales_price!==''){
      $tr.find('input[name="unit_price[]"]').val(unit.sales_price).trigger('change');
    }
  });

  // -------------------------------------------------------
  // FILE PREVIEW
  // -------------------------------------------------------
  let selectedFile = null;

  $('#btnContinueOCR').on('click', function(){
    const fileInput = $('#invoice_file')[0];
    if(!fileInput.files.length){ showFlash('error', 'Please select a file.'); return; }
    const f = fileInput.files[0];
    const ext = (f.name.split('.').pop()||'').toLowerCase();
    if(['png','jpg','jpeg','pdf'].indexOf(ext)===-1){ 
        showFlash('error', 'Invalid file type. Allowed: PNG, JPG, JPEG, PDF.'); 
        return; 
    }
    if(f.size > 5 * 1024 * 1024){ 
        showFlash('error', 'File must be below 5 MB.'); 
        return; 
    }
    
    selectedFile = f;
    showFilePreview(f);
    hideModal('uploadModal');
    showModal('filePreviewModal');
  });

  function showFilePreview(file){
    const reader = new FileReader();
    
    $('#previewFileName').text(file.name);
    $('#previewFileSize').text((file.size / 1024).toFixed(1) + ' KB');
    $('#previewFileType').text(file.type || 'Unknown');
    
    const ext = (file.name.split('.').pop()||'').toLowerCase();
    
    if(ext === 'pdf'){
      $('#filePreviewImage').hide();
      $('#filePreviewPdf').show();
      $('#filePreviewPlaceholder').hide();
    } else if(['png','jpg','jpeg','gif','bmp','webp'].indexOf(ext) !== -1){
      reader.onload = function(e){
        $('#filePreviewImage').attr('src', e.target.result).show();
        $('#filePreviewPdf').hide();
        $('#filePreviewPlaceholder').hide();
      };
      reader.readAsDataURL(file);
    } else {
      $('#filePreviewImage').hide();
      $('#filePreviewPdf').hide();
      $('#filePreviewPlaceholder').show().html(`
        <i class="material-icons-round" style="font-size:64px;">insert_drive_file</i>
        <p class="mt-2">File type: ${ext}</p>
      `);
    }
  }

  $('#btnBackToUpload').on('click', function(){
    hideModal('filePreviewModal');
    showModal('uploadModal');
  });

  // -------------------------------------------------------
  // OCR UPLOAD FLOW
  // -------------------------------------------------------
  function startOcrUpload(file){
    $('#btnProcessOCR').prop('disabled', true).html('<i class="material-icons-round" style="font-size:.9rem;vertical-align:middle">hourglass_top</i> Processing...');
    showOcrLoader('Uploading your invoice...', 'Securely sending the file for processing. Please keep this page open.');
    
    const formData = new FormData();
    formData.append('invoice_file', file);
    
    $.ajax({
        url: '<?= site_url("pocr/upload_invoice_ocr") ?>',
        type: 'POST', 
        data: formData, 
        processData: false, 
        contentType: false, 
        dataType: 'json',
        success: function(res){
            if(res.status === 'success'){
                showOcrLoader('Reading your invoice...', 'Identifying invoice details and line items. Processing time depends on the file.');
                $.ajax({
                    url: '<?= site_url("pocr/run_ocr") ?>',
                    type: 'POST', 
                    dataType: 'json',
                    data: { 
                        file_data: res.file_data,
                        file_name: res.file_name,
                        mime_type: res.mime_type
                    },
                    success: function(ocrRes){
                        $('#btnProcessOCR').prop('disabled', false).html('<i class="material-icons-round" style="font-size:.9rem;vertical-align:middle">auto_awesome</i> Process OCR');
                        hideOcrLoader();
                        if(ocrRes.status === 'success'){ 
                            $('#pocrFlashMessages').empty();
                            hideModal('filePreviewModal');
                            openReviewModal(ocrRes); 
                        } else { 
                            showFlash('error', 'OCR failed: ' + (ocrRes.message || 'Unknown error')); 
                        }
                    },
                    error: function(xhr){
                        $('#btnProcessOCR').prop('disabled', false).html('<i class="material-icons-round" style="font-size:.9rem;vertical-align:middle">auto_awesome</i> Process OCR');
                        hideOcrLoader();
                        showFlash('error', getRequestError(xhr, 'OCR processing failed'));
                    }
                });
            } else {
                $('#btnProcessOCR').prop('disabled', false).html('<i class="material-icons-round" style="font-size:.9rem;vertical-align:middle">auto_awesome</i> Process OCR');
                hideOcrLoader();
                showFlash('error', 'Upload failed: ' + (res.message || 'Unknown error'));
            }
        },
        error: function(xhr){
            $('#btnProcessOCR').prop('disabled', false).html('<i class="material-icons-round" style="font-size:.9rem;vertical-align:middle">auto_awesome</i> Process OCR');
            hideOcrLoader();
            showFlash('error', getRequestError(xhr, 'Server error during upload'));
        }
    });
  }

  $('#btnProcessOCR').on('click', function(){
    if(!selectedFile){
      showFlash('error', 'No file selected. Please upload a file first.');
      return;
    }
    startOcrUpload(selectedFile);
  });

  $('#ocrForm').on('submit', function(e){ e.preventDefault(); });

  // -------------------------------------------------------
  // REVIEW MODAL
  // -------------------------------------------------------
  let currentOcrRes = null;

  function firstOcrItemValue(item, keys, fallback){
    for(let i = 0; i < keys.length; i++){
      const value = item[keys[i]];
      if(value !== undefined && value !== null && String(value).trim() !== ''){
        return value;
      }
    }
    return fallback;
  }

  function normalizeReviewItem(item){
    item = item || {};
    function numericValue(value){
      if(value === undefined || value === null || String(value).trim() === '') return '';
      return String(value).replace(/,/g, '').replace(/[^\d.-]/g, '');
    }
    return Object.assign({}, item, {
      description: firstOcrItemValue(item, ['description', 'item_description', 'itemDescription', 'item_name', 'itemName', 'product_name', 'service_name', 'item', 'name', 'raw_line'], ''),
      quantity: numericValue(firstOcrItemValue(item, ['quantity', 'qty', 'count'], 1)),
      unit_price: numericValue(firstOcrItemValue(item, ['unit_price', 'unitPrice', 'unit_rate', 'unitRate', 'rate', 'price', 'unit_cost'], '')),
      discount: numericValue(firstOcrItemValue(item, ['discount', 'discount_amount'], 0)),
      vat_rate: numericValue(firstOcrItemValue(item, ['vat_rate', 'vatRate', 'vat_percentage', 'vat_percent', 'tax_rate', 'tax_percent'], 15)),
      total: numericValue(firstOcrItemValue(item, ['total', 'total_amount', 'total_price', 'line_total', 'amount', 'total_inc_vat', 'total_with_vat', 'amount_including_vat', 'grand_total'], '')),
      unit: firstOcrItemValue(item, ['unit', 'unit_name', 'unitName', 'uom', 'measurement'], '')
    });
  }

  function openReviewModal(ocrRes){
    currentOcrRes = ocrRes;
    const p = ocrRes.prefill || {};

    $('#rev_invoice_date').val(p.invoice_date||'');
    $('#rev_invoice_time').val(p.invoice_time||'');
    $('#rev_due_date').val(p.due_date||'');
    $('#rev_order_no').val(p.order_no||'');
    $('#rev_reference_no').val(p.reference_no||'');
    $('#rev_supplier_invoice_no').val(p.supplier_invoice_number||'');
    $('#rev_contract_po_no').val(p.contract_po_no||p.reference_no||'');
    $('#rev_contract_po_date').val(p.contract_po_date||'');
    $('#rev_other_reference_no').val(p.other_reference_no||'');
    $('#rev_currency').val(p.currency||'');
    $('#rev_vat').val(p.vat||'');
    $('#rev_vendor_vat').val(p.vendor_vat||'');
    $('#rev_address').val(p.customer_address||p.address||'');
    $('#rev_vendor_address').val(p.vendor_address||'');

    const customerMatches = ocrRes.customer_matches || [];
    $('#rev_customer_id').val(p.customer_id || (customerMatches[0] && customerMatches[0].id) || '').trigger('change');
    configureReviewVendor(ocrRes);

    const parsed = ocrRes.parsed || {};
    const itemCandidates = [
      parsed.line_items,
      parsed.items,
      ocrRes.line_items,
      (ocrRes.data && ocrRes.data.line_items)
    ];
    let items = [];
    for(let i = 0; i < itemCandidates.length; i++){
      if(Array.isArray(itemCandidates[i]) && itemCandidates[i].length){
        items = itemCandidates[i];
        break;
      }
    }
    const reviewItems = items.length
      ? items.map(normalizeReviewItem)
      : [{ quantity: 1, discount: 0, vat_rate: 15 }];
    $('#reviewItemsTable tbody').empty();
    reviewItems.forEach(function(it, idx){
      const rowId = 'ritem_'+idx+'_'+Date.now();
      const matches = Array.isArray(it.item_matches) ? it.item_matches : [];
      const selectedItemId = (matches[0] && matches[0].id) || it.item_id || '';
      const $row  = $(buildReviewItemRowHtml(it, rowId, matches, selectedItemId));
      $row.data('item-matches', matches);
      $row.data('ocr-unit-label', it.unit || '');
      $('#reviewItemsTable tbody').append($row);
      initializeSearchableSelect($row.find('.rev-item-select'), 'Search items...', $('#reviewModal'));
      initializeSearchableSelect($row.find('.rev-item-unit'), 'Select unit...', $('#reviewModal'));
      updateReviewItemMatchStatus($row, matches, false);
    });

    showModal('reviewModal');
    updateReviewFieldWarnings();
  }

  function buildReviewItemRowHtml(it, rowId, matches, selectedItemId){
    it = it || {};
    const itemId = selectedItemId || it.item_id || '';
    const item  = (window.ALL_ITEMS||[]).find(x=>String(x.id)===String(itemId));
    const units = item ? item.units : [];
    return '<tr data-row-id="'+rowId+'">' +
      '<td>'+
        '<select class="form-control rev-item-select" required>'+buildReviewItemOptions(matches, itemId)+'</select>'+
        '<div class="rev-item-match-status small text-muted mt-1" role="status"></div>'+
        '<button type="button" class="link-btn btn-show-all-items" style="display:none">Show all items</button>'+
      '</td>'+
      '<td><select class="form-control rev-item-unit" style="font-size:.75rem;padding:3px 6px">'+buildUnitOptions(units,it.unit_id,it.unit)+'</select></td>'+
      '<td><input type="text" class="form-control rev-item-desc" value="'+escapeHtml(it.description || it.item_name || it.name || '')+'" style="font-size:.75rem;padding:3px 6px;min-width:110px"></td>'+
      '<td><input type="number" class="form-control rev-item-qty" value="'+(it.quantity??it.qty??1)+'" style="font-size:.75rem;padding:3px 6px;width:55px"></td>'+
      '<td><input type="number" step="0.01" class="form-control rev-item-price" value="'+(it.unit_price??'')+'" style="font-size:.75rem;padding:3px 6px;width:75px"></td>'+
      '<td><input type="number" step="0.01" class="form-control rev-item-discount" value="'+(it.discount??0)+'" style="font-size:.75rem;padding:3px 6px;width:65px"></td>'+
      '<td><input type="number" step="0.01" class="form-control rev-item-vat" value="'+(it.vat_rate??15)+'" style="font-size:.75rem;padding:3px 6px;width:55px"></td>'+
      '<td><input type="number" step="0.01" class="form-control rev-item-total" value="'+(it.total??'')+'" style="font-size:.75rem;padding:3px 6px;width:80px"></td>'+
      '<td><button type="button" class="btn btn-sm btn-outline-danger mb-0 btnRemoveReviewItem" style="padding:2px 6px"><i class="material-icons-round" style="font-size:.8rem">delete</i></button></td>'+
      '</tr>';
  }

  function updateReviewItemMatchStatus($row, matches, showingAll){
    const $status = $row.find('.rev-item-match-status');
    if(showingAll){
      $status.text(matches.length
        ? 'Showing all items; similar matches are listed first.'
        : 'No similar matches found. Showing all items.');
    }else if(matches.length){
      $status.text(matches.length+' similar item match'+(matches.length === 1 ? '' : 'es')+'.');
    }else{
      $status.text('No matching items found.');
    }
    if(showingAll){
      $row.find('.btn-show-all-items').hide();
    }else{
      $row.find('.btn-show-all-items').show().text('Show all items');
    }
  }

  $(document).on('click', '.btn-show-all-items', function(){
    const $row = $(this).closest('tr');
    const matches = $row.data('item-matches') || [];
    const selectedId = $row.find('.rev-item-select').val();
    const $select = $row.find('.rev-item-select');
    setSearchableSelectOptions($select, window.ALL_ITEMS || [], 'Search all items...', $('#reviewModal'), selectedId);
    updateReviewItemMatchStatus($row, matches, true);
  });

  $(document).on('click', '.btnRemoveReviewItem', function(){ $(this).closest('tr').remove(); });

  function updateReviewFieldWarnings(){
    $('#reviewModal').find('input, select, textarea').each(function(){
      const $field = $(this);
      if(this.disabled || this.type === 'hidden' || $field.closest('.d-none').length){
        $field.removeClass('inv-field-warning');
        $field.next('.select2-container').find('.select2-selection').removeClass('inv-field-warning');
        return;
      }

      const value = $field.val();
      const isEmpty = value === null || String(value).trim() === '';
      const isZeroUnitRate = $field.hasClass('rev-item-price') && !isEmpty && Number(value) === 0;
      const isInvalidQuantity = $field.hasClass('rev-item-qty') && !isEmpty && Number(value) < 1;
      const isWarning = isEmpty || isZeroUnitRate || isInvalidQuantity;
      $field.toggleClass('inv-field-warning', isWarning);
      $field.next('.select2-container').find('.select2-selection').toggleClass('inv-field-warning', isWarning);
    });
  }

  $(document).on('input change', '#reviewModal input, #reviewModal select, #reviewModal textarea', function(){
    updateReviewFieldWarnings();
  });

  $(document).on('keyup change', '.rev-item-qty,.rev-item-price,.rev-item-discount,.rev-item-vat', function(){
    const $tr      = $(this).closest('tr');
    const qty      = parseFloat($tr.find('.rev-item-qty').val())      || 0;
    const price    = parseFloat($tr.find('.rev-item-price').val())    || 0;
    const discount = parseFloat($tr.find('.rev-item-discount').val()) || 0;
    const vat      = parseFloat($tr.find('.rev-item-vat').val())      || 0;
    const taxable  = Math.max(0, qty*price - discount);
    $tr.find('.rev-item-total').val((taxable + taxable*vat/100).toFixed(2));
  });

  $(document).on('change', '.rev-item-select', function(){
    const $tr = $(this).closest('tr');
    const item = (window.ALL_ITEMS||[]).find(x=>String(x.id)===String($(this).val()));
    const units = item&&item.units ? item.units : [];
    const $unitSelect = $tr.find('.rev-item-unit');
    $unitSelect.html(buildUnitOptions(units, null, $tr.data('ocr-unit-label')));
    if(units.length){
      const base = units.find(u=>u.is_base)||units[0];
      $unitSelect.val(base.unit_id).trigger('change');
      if(String($tr.find('.rev-item-price').val() || '').trim() === '' && base.sales_price){
        $tr.find('.rev-item-price').val(base.sales_price).trigger('change');
      }
    }
  });

  $(document).on('change', '.rev-item-unit', function(){
    const $tr   = $(this).closest('tr');
    const itemId = $tr.find('.rev-item-select').val();
    const item  = (window.ALL_ITEMS||[]).find(x=>String(x.id)===String(itemId));
    const unit  = item&&item.units ? item.units.find(u=>String(u.unit_id)===String($(this).val())) : null;
    if(unit && unit.sales_price!=null && unit.sales_price!=='' && String($tr.find('.rev-item-price').val() || '').trim() === ''){
      $tr.find('.rev-item-price').val(unit.sales_price).trigger('change');
    }
  });

  // -------------------------------------------------------
  // COLLECT REVIEW DATA
  // -------------------------------------------------------
  function collectReviewData(){
    const items = [];
    $('#reviewItemsTable tbody tr').each(function(){
      const $tr       = $(this);
      const itemId    = $tr.find('.rev-item-select').val();
      const unitId    = $tr.find('.rev-item-unit').val();
      const unitLabel = $tr.find('.rev-item-unit option:selected').text();
      const selItem   = itemId ? (window.ALL_ITEMS||[]).find(x=>String(x.id)===String(itemId)) : null;
      items.push({
        description : $tr.find('.rev-item-desc').val(),
        quantity    : $tr.find('.rev-item-qty').val(),
        unit_price  : $tr.find('.rev-item-price').val(),
        discount    : $tr.find('.rev-item-discount').val(),
        vat_rate    : $tr.find('.rev-item-vat').val(),
        total       : $tr.find('.rev-item-total').val(),
        item_id     : itemId || null,
        item_label  : selItem ? selItem.name : $tr.find('.rev-item-desc').val(),
        unit_id     : unitId  || null,
        unit_label  : unitLabel && unitLabel!=='--' ? unitLabel : ''
      });
    });
    return {
      invoice_date          : $('#rev_invoice_date').val(),
      invoice_time          : $('#rev_invoice_time').val(),
      due_date              : $('#rev_due_date').val(),
      order_no              : $('#rev_order_no').val(),
      reference_no          : $('#rev_reference_no').val(),
      supplier_invoice_no   : $('#rev_supplier_invoice_no').val(),
      contract_po_no        : $('#rev_contract_po_no').val(),
      contract_po_date      : $('#rev_contract_po_date').val(),
      other_reference_no    : $('#rev_other_reference_no').val(),
      currency              : $('#rev_currency').val(),
      customer_id           : $('#rev_customer_id').val(),
      customer_text         : $('#rev_customer_id option:selected').text(),
      vendor_id             : $('#rev_vendor_id').val(),
      vendor_text           : $('#rev_vendor_id option:selected').text(),
      vat                   : $('#rev_vat').val(),
      vendor_vat            : $('#rev_vendor_vat').val(),
      address               : $('#rev_address').val(),
      vendor_address        : $('#rev_vendor_address').val(),
      invoice_no            : (currentOcrRes && currentOcrRes.prefill && currentOcrRes.prefill.invoice_no) || '',
      items                 : items
    };
  }

  function validateRequiredSelections(){
    const $vendor = $('#rev_vendor_id');
    if(!$vendor.val()){
      showFlash('error', 'Please select a vendor.');
      $vendor.select2('open');
      return false;
    }

    if(!$('#reviewItemsTable tbody tr').length){
      showFlash('error', 'Please add at least one item.');
      return false;
    }

    const $missingItem = $('#reviewItemsTable tbody .rev-item-select').filter(function(){
      return !$(this).val();
    }).first();
    if($missingItem.length){
      showFlash('error', 'Please select an item for every line.');
      $missingItem.select2('open');
      return false;
    }

    return true;
  }

  // -------------------------------------------------------
  // PREVIEW MODAL
  // -------------------------------------------------------
  let reviewedData = null;

  $('#btnGoToPreview').on('click', function(){
    try {
      updateReviewFieldWarnings();
      if(!validateRequiredSelections()) return;
      const $invalidQuantity = $('#reviewItemsTable tbody .rev-item-qty').filter(function(){
        const quantity = Number($(this).val());
        return !Number.isFinite(quantity) || quantity < 1;
      }).first();
      if($invalidQuantity.length){
        showFlash('error', 'Quantity must be greater than or equal to 1 for every line item.');
        $invalidQuantity.trigger('focus');
        return;
      }
      reviewedData = collectReviewData();
      if(!reviewedData || !Array.isArray(reviewedData.items)){
        showFlash('error', 'No items or review data available.');
        return;
      }
      $('#previewSummary').html(
        '<div class="row g-2" style="font-size:0.8rem">'+
        '<div class="col-md-4"><strong>Invoice No:</strong> '+escapeHtml(reviewedData.invoice_no)+'</div>'+
        '<div class="col-md-4"><strong>Date:</strong> '+escapeHtml(reviewedData.invoice_date)+'</div>'+
        '<div class="col-md-4"><strong>Due:</strong> '+escapeHtml(reviewedData.due_date)+'</div>'+
        '<div class="col-md-4"><strong>Order No:</strong> '+escapeHtml(reviewedData.order_no)+'</div>'+
        '<div class="col-md-4"><strong>Supplier Inv No:</strong> '+escapeHtml(reviewedData.supplier_invoice_no)+'</div>'+
        '<div class="col-md-4"><strong>Contract/PO:</strong> '+escapeHtml(reviewedData.contract_po_no)+'</div>'+
        '<div class="col-md-4"><strong>Vendor:</strong> '+escapeHtml(reviewedData.vendor_text)+'</div>'+
        '<div class="col-md-6"><strong>Vendor Address:</strong> '+escapeHtml(reviewedData.vendor_address)+'</div>'+
        '</div>'
      );
      $('#previewItemsTable tbody').empty();
      reviewedData.items.forEach(function(it){
        $('#previewItemsTable tbody').append(
          '<tr><td>'+escapeHtml(it.item_label||it.description||'')+'</td>'+
          '<td>'+escapeHtml(it.unit_label||'')+'</td>'+
          '<td>'+escapeHtml(it.description||'')+'</td>'+
          '<td>'+escapeHtml(String(it.quantity??''))+'</td>'+
          '<td>'+escapeHtml(String(it.unit_price??''))+'</td>'+
          '<td>'+escapeHtml(String(it.discount??0))+'</td>'+
          '<td>'+escapeHtml(String(it.vat_rate??''))+'</td>'+
          '<td>'+escapeHtml(String(it.total??''))+'</td></tr>'
        );
      });
      hideModal('reviewModal');
      showModal('previewModal');
    } catch(e){
      showFlash('error', 'Failed to open preview: '+(e&&e.message?e.message:e));
    }
  });

  $('#btnBackToReview').on('click', function(){ 
    hideModal('previewModal'); 
    showModal('reviewModal'); 
  });

  // -------------------------------------------------------
  // CONFIRM → FILL FORM
  // -------------------------------------------------------
  $('#btnConfirmFillForm').on('click', function(){
    if(!reviewedData) return;
    const d = reviewedData;

    const payload = {
      reference_no: d.reference_no || d.order_no || '',
      reference_no_field: d.reference_no || d.order_no || '',
      vendor_id: d.vendor_id || '',
      customer_id: d.customer_id || '',
      invoice_date: d.invoice_date || '',
      due_date: d.due_date || '',
      order_no: d.order_no || '',
      supplier_invoice_no: d.supplier_invoice_no || '',
      invoice_no: d.invoice_no || '',
      vendor_text: d.vendor_text || '',
      customer_text: d.customer_text || '',
      vendor_vat: d.vendor_vat || '',
      currency: d.currency || 'SAR',
      reference_id: $('#reference_no_prefill').val() || '',
      document_id: (currentOcrRes && currentOcrRes.document_id) ? currentOcrRes.document_id : '',
      items_json: JSON.stringify(d.items || [])
    };

    $('#btnConfirmFillForm').prop('disabled', true).text('Saving...');
    showOcrLoader('Saving the purchase...', 'Validating the details and saving your invoice items. Please keep this page open.');

    $.ajax({
      url: '<?= site_url("pocr/save_purchase_ocr") ?>',
      type: 'POST',
      dataType: 'json',
      data: payload,
     success: function(res){
    $('#btnConfirmFillForm').prop('disabled', false).text('Confirm & Fill Form');
    hideOcrLoader();
    if(res.status === 'success'){
      hideModal('previewModal');
      if(res.redirect_url){
        window.location.href = res.redirect_url;
      } else {
        showFlash('success', 'Purchase saved successfully. Reference: ' + (res.reference || res.purchase_id || 'N/A'));
      }
    } else {
      showFlash('error', 'Save failed: ' + (res.message || 'Unknown error'));
    }
},
      error: function(xhr){
        $('#btnConfirmFillForm').prop('disabled', false).text('Confirm & Fill Form');
        hideOcrLoader();
        showFlash('error', getRequestError(xhr, 'Server error while saving'));
      }
    });
  });

});
</script>