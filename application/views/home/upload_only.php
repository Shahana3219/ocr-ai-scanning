<div class="container mt-6 text-center">
    <h3>Upload Purchase</h3>
    <p class="text-muted">Click below to upload a purchase invoice and open the OCR review flow.</p>
    <button type="button" class="btn btn-primary btn-lg" id="uploadPurchaseButton" onclick="uploadPurchaseWithRef()">
        Upload Purchase
    </button>
    <div id="referenceFlash" class="d-none mt-3 alert alert-danger" role="alert" aria-live="assertive"></div>
</div>

<script>
function showReferenceFlash(message) {
    const flash = document.getElementById('referenceFlash');
    flash.textContent = message;
    flash.classList.remove('d-none');
}

function uploadPurchaseWithRef() {
    const button = document.getElementById('uploadPurchaseButton');
    document.getElementById('referenceFlash').classList.add('d-none');
    button.disabled = true;
    fetch("<?= site_url('pocr/generate_reference/109') ?>")
        .then(res => {
            if (!res.ok) throw new Error('Reference request failed.');
            return res.json();
        })
        .then(data => {
            const ref = Number(data.data && data.data.reference);
            if (data.status !== 'success' || !Number.isInteger(ref) || ref <= 0) {
                throw new Error(data.message || 'A purchase reference was not returned.');
            }
            window.location.href = "<?= site_url('pocr/pocr') ?>?ref=" + ref;
        })
        .catch(err => {
            console.error('Reference generation failed:', err);
            button.disabled = false;
            showReferenceFlash('Could not create a purchase reference. Please try again.');
        });
}
</script>