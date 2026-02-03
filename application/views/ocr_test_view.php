<!DOCTYPE html>
<html>
<head>
    <title>OCR Accuracy Test</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <h1>Test OCR Accuracy</h1>
    
    <form id="testForm" enctype="multipart/form-data">
        <input type="file" name="test_file" accept=".png,.jpg,.jpeg,.pdf" required>
        <button type="button" id="testBtn">Test OCR</button>
    </form>
    
    <div id="result" style="margin-top: 20px;"></div>
    
    <script>
    $('#testBtn').on('click', function(){
        const formData = new FormData($('#testForm')[0]);
        
        $.ajax({
            url: '<?= site_url("home/upload_invoice_ocr") ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res){
                if(res.status === 'success'){
                    $('#result').html('<p>✓ File uploaded. Document ID: ' + res.document_id + '</p>');
                    
                    // Test enhanced OCR
                    $.ajax({
                        url: '<?= site_url("home/run_ocr_max_accuracy") ?>',
                        type: 'POST',
                        data: { document_id: res.document_id },
                        dataType: 'json',
                        success: function(ocrRes){
                            $('#result').append(
                                '<h3>OCR Results:</h3>' +
                                '<p>Status: ' + ocrRes.status + '</p>' +
                                '<p>Accuracy: ' + ocrRes.average_confidence + '%</p>' +
                                '<p>Processed: ' + ocrRes.processed_pages + ' pages</p>' +
                                '<h4>Extracted Data:</h4>' +
                                '<pre>' + JSON.stringify(ocrRes.extracted_data, null, 2) + '</pre>'
                            );
                        }
                    });
                    
                } else {
                    $('#result').html('<p style="color:red">✗ ' + res.message + '</p>');
                }
            }
        });
    });
    </script>
</body>
</html>