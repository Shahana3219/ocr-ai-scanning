<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice</title>

<style>
body{font-family:Arial,sans-serif;background:#f3f4f6}
.container{max-width:1200px;margin:20px auto;background:#fff;padding:20px;border-radius:6px}
.header{display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e5e7eb;padding-bottom:12px;margin-bottom:20px}
.row{display:flex;gap:15px;margin-bottom:12px}
.col{flex:1}
label{font-size:13px;color:#374151;display:block;margin-bottom:4px}
input,select,textarea{width:100%;padding:8px;border:1px solid #d1d5db;border-radius:4px}
textarea{resize:vertical}
.btn{padding:8px 14px;border:none;border-radius:4px;cursor:pointer}
.btn-primary{background:#10b981;color:#fff}
.btn-secondary{background:#3b82f6;color:#fff;margin-right:6px}
.info-box{background:#f9fafb;padding:12px;border-radius:4px;border:1px solid #e5e7eb;font-size:13px}

/* MODAL */
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);justify-content:center;align-items:center;z-index:999}
.modal-content{background:#fff;width:500px;border-radius:6px;padding:20px}
.modal-header{display:flex;justify-content:space-between;align-items:center}
.modal-header h3{margin:0}
.close{cursor:pointer;font-size:20px}
small{font-size:12px;color:#6b7280;line-height:1.6}

/* FLASH */
.flash{padding:10px;border-radius:6px;margin-bottom:12px;font-size:13px}
.flash-success{background:#dcfce7;border:1px solid #bbf7d0;color:#166534}
.flash-error{background:#fee2e2;border:1px solid #fecaca;color:#991b1b}
</style>
</head>

<body>
<div class="container">

<?php $prefill = $prefill ?? []; ?>

<?php if($this->session->flashdata('error')): ?>
  <div class="flash flash-error"><?= $this->session->flashdata('error'); ?></div>
<?php endif; ?>

<?php if($this->session->flashdata('success')): ?>
  <div class="flash flash-success"><?= $this->session->flashdata('success'); ?></div>
<?php endif; ?>

<!-- HEADER -->
<div class="header">
  <h2>Invoice</h2>
  <button type="button" class="btn btn-secondary" id="btnOpenModal">Upload Invoice</button>
</div>

<!-- =========================
     OCR MODAL (SEPARATE FORM)
     ========================= -->
<div class="modal" id="uploadModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Upload Invoice (OCR)</h3>
      <span class="close" id="btnCloseModal">&times;</span>
    </div>

    <br>

    <?= form_open_multipart('upload-invoice-ocr', ['id'=>'ocrForm']); ?>

      <input type="file" name="invoice_file" id="invoice_file"
             accept=".png,.jpg,.jpeg,.pdf,.doc,.docx" required>

      <br><br>

      <small>
        <strong>How this works:</strong><br>
        • Upload invoice → system extracts data automatically<br>
        • You can review & edit extracted fields<br><br>

        <strong>Rules:</strong><br>
        • Max file size: <strong>5 MB</strong><br>
        • Formats: PNG, JPG, JPEG, PDF, DOC, DOCX<br>
        • No handwritten invoices (affects OCR clarity)
      </small>

      <div style="text-align:right;margin-top:15px">
        <button type="button" class="btn btn-primary" id="btnContinueOCR">Continue</button>
      </div>

    <?= form_close(); ?>
  </div>
</div>


<!-- ==================================
     MAIN INVOICE SAVE FORM (EDITABLE)
     ================================== -->
<?= form_open('save-invoice', ['id'=>'invoiceSaveForm']); ?>
<input type="hidden" name="document_id" id="document_id" value="">

<!-- DATE / TIME -->
<div class="row">
  <div class="col">
    <label>Date</label>
    <input type="date" name="invoice_date"
      value="<?= set_value('invoice_date', $prefill['invoice_date'] ?? date('Y-m-d')) ?>">
  </div>
  <div class="col">
    <label>Time</label>
    <input type="time" name="invoice_time"
      value="<?= set_value('invoice_time', $prefill['invoice_time'] ?? date('H:i')) ?>">
  </div>
  <div class="col">
    <label>Due Date</label>
    <input type="date" name="due_date"
      value="<?= set_value('due_date', $prefill['due_date'] ?? date('Y-m-d')) ?>">
  </div>
</div>

<!-- ORDER -->
<div class="row">
  <div class="col">
    <label>Order No</label>
    <input type="text" name="order_no"
      value="<?= set_value('order_no', $prefill['order_no'] ?? '') ?>">
  </div>
  <div class="col">
    <label>Reference No</label>
    <input type="text" name="reference_no"
      value="<?= set_value('reference_no', $prefill['reference_no'] ?? '') ?>">
  </div>
  <div class="col">
    <label>Employee</label>
    <select name="employee">
      <option >select employee</option>
    </select>
  </div>
</div>

<!-- SALE -->
<div class="row">
  <div class="col">
    <label>Sale Account</label>
    <select name="sale_account">
      <option>Sale</option>
    </select>
  </div>
  <div class="col">
    <label>Invoice Type</label>
    <select name="invoice_type">
      <option>-- Complete --</option>
    </select>
  </div>
  <div class="col">
    <label>VAT Type</label>
    <select name="vat_type">
      <option>Not Applicable By Default</option>
    </select>
  </div>
</div>

<!-- SUBJECT -->
<div class="row">
  <div class="col">
    <label>Subject</label>
    <textarea name="subject"><?= set_value('subject', $prefill['subject'] ?? '') ?></textarea>
  </div>
</div>

<!-- CUSTOMER / PROJECT -->
<div class="row">
  <div class="col">
    <label>Select Customer</label>
    <select name="customer_id">
      <option>-- Select Customer --</option>
    </select>
  </div>
  <div class="col">
    <label>Select Project</label>
    <select name="project_id">
      <option>-- Select Project --</option>
    </select>
  </div>
</div>

<!-- QUICK ADD -->
<div class="row">
  <div class="col">
    <button type="button" class="btn btn-secondary">+ Customer</button>
    <button type="button" class="btn btn-secondary">+ Product</button>
    <button type="button" class="btn btn-secondary">+ Service</button>
  </div>
</div>

<!-- INVOICE INFO -->
<div class="info-box">
  <p><strong>Invoice No :</strong> <?= htmlspecialchars($prefill['invoice_no'] ?? ''); ?></p>
  <p><strong>Customer Code :</strong> <?= htmlspecialchars($prefill['customer_code'] ?? ''); ?></p>
  <p><strong>Customer :</strong> <?= htmlspecialchars($prefill['customer_name'] ?? ''); ?></p>
  <p><strong>VAT :</strong> <?= htmlspecialchars($prefill['vat'] ?? ''); ?></p>
  <p><strong>Address :</strong> <?= htmlspecialchars($prefill['address'] ?? ''); ?></p>
  <?php if (!empty($prefill['invoice_id'])): ?>
    <p><strong>OCR Invoice ID :</strong> <?= htmlspecialchars($prefill['invoice_id']); ?></p>
    <p><strong>Normalized Pages :</strong> <?= htmlspecialchars($prefill['normalized_pages'] ?? ''); ?></p>
  <?php endif; ?>
</div>

<!-- CURRENCY -->
<div class="row">
  <div class="col">
    <label>Currency</label>
    <select name="currency">
      <option><?= htmlspecialchars($prefill['currency'] ?? 'Saudi Arabia - Saudi Riyal'); ?></option>
    </select>
  </div>
  <div class="col">
    <label>Conversion Rate</label>
    <input type="number" name="conversion_rate"
      value="<?= set_value('conversion_rate', $prefill['conversion_rate'] ?? '1') ?>">
  </div>
</div>

<!-- SUBMIT -->
<div style="margin-top:20px">
  <button type="submit" class="btn btn-primary">Save Invoice</button>
</div>

<?= form_close(); ?>

</div>

<!-- jQuery (use your local copy if already included in template) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
$(function(){

  // OPEN MODAL
  $('#btnOpenModal').on('click', function () {
    $('#uploadModal').css('display', 'flex');
  });

  // CLOSE MODAL (X button)
  $('#btnCloseModal').on('click', function () {
    $('#uploadModal').hide();
  });

  // CLOSE MODAL when clicking outside modal-content
  $('#uploadModal').on('click', function(e){
    if (e.target.id === 'uploadModal') {
      $('#uploadModal').hide();
    }
  });

  // Prevent default form submit (important: avoid routing)
  $('#ocrForm').on('submit', function(e){
    e.preventDefault();
  });

  // Continue -> AJAX upload
  $('#btnContinueOCR').on('click', function(){

    const fileInput = $('#invoice_file')[0];

    if(!fileInput.files.length){
      alert('Please select a file');
      return;
    }

    const f = fileInput.files[0];
    const allowed = [
      'image/png',
      'image/jpeg',
      'application/pdf',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    if($.inArray(f.type, allowed) === -1){
      alert('Invalid file type');
      return;
    }

    if(f.size > 5 * 1024 * 1024){
      alert('File must be below 5MB');
      return;
    }

    const formData = new FormData($('#ocrForm')[0]);

    $.ajax({
      url: '<?= site_url("home/upload_invoice_ocr") ?>',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json', // ✅ IMPORTANT
      success: function(res){

        if(res.status === 'success'){
          if(res.prefill){
            $('input[name="invoice_date"]').val(res.prefill.invoice_date || '');
            $('input[name="invoice_time"]').val(res.prefill.invoice_time || '');
            $('input[name="due_date"]').val(res.prefill.due_date || '');
            $('input[name="order_no"]').val(res.prefill.order_no || '');
            $('input[name="reference_no"]').val(res.prefill.reference_no || '');
            $('input[name="employee"]').val(res.prefill.employee || '');
            $('textarea[name="subject"]').val(res.prefill.subject || '');
          }

          alert(res.message || 'OCR completed');
          $('#uploadModal').hide();
        } else {
          alert(res.message || 'OCR failed');
        }
      },
      error: function(xhr){
        alert('Server error during OCR. HTTP: ' + xhr.status);
      }
    });

  });

});
</script>


</body>
</html>
