<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Template Editor</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/spectrum/2.0.10/spectrum.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
        }
        
        .editor-container {
            display: grid;
            grid-template-columns: 320px 1fr 400px;
            height: 100vh;
            gap: 0;
        }
        
        .sidebar {
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            overflow-y: auto;
            box-shadow: 2px 0 8px rgba(0,0,0,0.05);
        }
        
        .preview-area {
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: auto;
        }
        
        .properties-panel {
            background: #ffffff;
            border-left: 1px solid #e2e8f0;
            overflow-y: auto;
            box-shadow: -2px 0 8px rgba(0,0,0,0.05);
        }
        
        .invoice-preview {
            background: white;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-radius: 8px;
            min-height: 800px;
            width: 100%;
            max-width: 600px;
            position: relative;
            transform-origin: top center;
        }
        
        .section-item {
            background: #f8fafc;
            border: 2px solid transparent;
            border-radius: 8px;
            padding: 12px;
            margin: 8px 0;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .section-item:hover {
            background: #e2e8f0;
            border-color: #94a3b8;
        }
        
        .section-item.active {
            background: #dbeafe;
            border-color: #3b82f6;
        }
        
        .section-item.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .field-item {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            margin: 4px 0;
            cursor: move;
            transition: all 0.2s ease;
        }
        
        .field-item:hover {
            border-color: #3b82f6;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
        }
        
        .field-item.selected {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        
        .color-picker-input {
            width: 40px;
            height: 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .property-group {
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px;
            margin: 12px 0;
        }
        
        .property-group h6 {
            color: #374151;
            font-weight: 600;
            margin-bottom: 12px;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .toolbar {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .btn-group-sm .btn {
            padding: 4px 8px;
            font-size: 0.875rem;
        }
        
        .zoom-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .dragging {
            opacity: 0.5;
        }
        
        .drop-zone {
            min-height: 40px;
            border: 2px dashed #cbd5e1;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .drop-zone.drag-over {
            border-color: #3b82f6;
            background: #eff6ff;
            color: #3b82f6;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .visibility-toggle {
            width: 20px;
            height: 20px;
            border: none;
            background: none;
            color: #6b7280;
            cursor: pointer;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .visibility-toggle:hover {
            background: #f3f4f6;
            color: #374151;
        }
        
        .section-controls {
            display: flex;
            gap: 4px;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        
        .section-item:hover .section-controls {
            opacity: 1;
        }
        
        .language-tabs {
            display: flex;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 16px;
        }
        
        .language-tab {
            padding: 8px 16px;
            border: none;
            background: none;
            color: #6b7280;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
        }
        
        .language-tab.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
        }
        
        .rtl-support {
            direction: rtl;
            text-align: right;
        }
        
        @media print {
            .invoice-preview {
                box-shadow: none;
                border-radius: 0;
            }
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            border-radius: 8px;
        }
        
        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid #e2e8f0;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        .toast {
            min-width: 300px;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="editor-container">
        <!-- Left Sidebar - Sections & Elements -->
        <div class="sidebar">
            <div class="p-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="m-0">Template Sections</h5>
                    <button class="btn btn-sm btn-outline-primary" onclick="addSection()">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                
                <!-- Language Tabs -->
                <div class="language-tabs">
                    <button class="language-tab active" data-lang="en">English</button>
                    <button class="language-tab" data-lang="ar">العربية</button>
                </div>
                
                <!-- Template Sections -->
                <div id="sections-list">
                    <div class="section-item active" data-section="header">
                        <div class="section-header">
                            <div>
                                <i class="fas fa-heading text-primary me-2"></i>
                                <strong>Header</strong>
                            </div>
                            <div class="section-controls">
                                <button class="visibility-toggle" data-visible="true">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Company logo and header information</small>
                    </div>
                    
                    <div class="section-item" data-section="company-info">
                        <div class="section-header">
                            <div>
                                <i class="fas fa-building text-success me-2"></i>
                                <strong>Company Information</strong>
                            </div>
                            <div class="section-controls">
                                <button class="visibility-toggle" data-visible="true">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Company name, address, VAT number</small>
                    </div>
                    
                    <div class="section-item" data-section="client-info">
                        <div class="section-header">
                            <div>
                                <i class="fas fa-user text-info me-2"></i>
                                <strong>Client Information</strong>
                            </div>
                            <div class="section-controls">
                                <button class="visibility-toggle" data-visible="true">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Bill to information</small>
                    </div>
                    
                    <div class="section-item" data-section="invoice-details">
                        <div class="section-header">
                            <div>
                                <i class="fas fa-file-invoice text-warning me-2"></i>
                                <strong>Invoice Details</strong>
                            </div>
                            <div class="section-controls">
                                <button class="visibility-toggle" data-visible="true">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Invoice number, date, due date</small>
                    </div>
                    
                    <div class="section-item" data-section="items-table">
                        <div class="section-header">
                            <div>
                                <i class="fas fa-table text-purple me-2"></i>
                                <strong>Items Table</strong>
                            </div>
                            <div class="section-controls">
                                <button class="visibility-toggle" data-visible="true">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Line items with quantities and prices</small>
                    </div>
                    
                    <div class="section-item" data-section="totals">
                        <div class="section-header">
                            <div>
                                <i class="fas fa-calculator text-danger me-2"></i>
                                <strong>Totals</strong>
                            </div>
                            <div class="section-controls">
                                <button class="visibility-toggle" data-visible="true">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Subtotal, VAT, and total amount</small>
                    </div>
                    
                    <div class="section-item" data-section="footer">
                        <div class="section-header">
                            <div>
                                <i class="fas fa-align-left text-secondary me-2"></i>
                                <strong>Footer</strong>
                            </div>
                            <div class="section-controls">
                                <button class="visibility-toggle" data-visible="true">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Terms, conditions, and signatures</small>
                    </div>
                </div>
                
                <hr>
                
                <!-- Available Fields -->
                <div>
                    <h6 class="mb-3">Available Fields</h6>
                    <div id="available-fields">
                        <div class="field-item" draggable="true" data-field-type="text">
                            <i class="fas fa-font me-2"></i>
                            Text Field
                        </div>
                        <div class="field-item" draggable="true" data-field-type="number">
                            <i class="fas fa-hashtag me-2"></i>
                            Number Field
                        </div>
                        <div class="field-item" draggable="true" data-field-type="date">
                            <i class="fas fa-calendar me-2"></i>
                            Date Field
                        </div>
                        <div class="field-item" draggable="true" data-field-type="image">
                            <i class="fas fa-image me-2"></i>
                            Image Field
                        </div>
                        <div class="field-item" draggable="true" data-field-type="qr_code">
                            <i class="fas fa-qrcode me-2"></i>
                            QR Code
                        </div>
                        <div class="field-item" draggable="true" data-field-type="barcode">
                            <i class="fas fa-barcode me-2"></i>
                            Barcode
                        </div>
                        <div class="field-item" draggable="true" data-field-type="signature">
                            <i class="fas fa-signature me-2"></i>
                            Signature
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Center - Preview Area -->
        <div class="preview-area">
            <div class="toolbar">
                <div class="d-flex align-items-center gap-3">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary" onclick="saveTemplate()">
                            <i class="fas fa-save me-1"></i> Save
                        </button>
                        <button class="btn btn-outline-secondary" onclick="exportTemplate()">
                            <i class="fas fa-download me-1"></i> Export
                        </button>
                        <button class="btn btn-outline-secondary" onclick="previewPDF()">
                            <i class="fas fa-file-pdf me-1"></i> PDF Preview
                        </button>
                    </div>
                    
                    <div class="zoom-controls">
                        <button class="btn btn-sm btn-outline-secondary" onclick="zoomOut()">
                            <i class="fas fa-search-minus"></i>
                        </button>
                        <span class="small text-muted px-2" id="zoom-level">100%</span>
                        <button class="btn btn-sm btn-outline-secondary" onclick="zoomIn()">
                            <i class="fas fa-search-plus"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-sm" id="preview-language">
                        <option value="en">English Preview</option>
                        <option value="ar">Arabic Preview</option>
                    </select>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="live-preview" checked>
                        <label class="form-check-label small" for="live-preview">Live Preview</label>
                    </div>
                </div>
            </div>

            <div class="invoice-preview" id="invoice-preview">
                <div id="loading-overlay" class="loading-overlay" style="display: none;">
                    <div class="spinner"></div>
                </div>

                <!-- Dynamic invoice preview content will be generated here -->
                <div id="preview-content">
                    <!-- Header Section -->
                    <div class="preview-section" data-section="header" style="padding: 20px; background: #f8f9fa; border-bottom: 2px solid #e9ecef;">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="company-logo" style="width: 120px; height: 60px; background: #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #666;">
                                    LOGO
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <h2 class="mb-0" style="color: #2563eb; font-weight: bold;">INVOICE</h2>
                            </div>
                        </div>
                    </div>

                    <!-- Company & Client Info Section -->
                    <div class="preview-section" data-section="company-info" style="padding: 20px;">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 style="color: #374151; margin-bottom: 15px;">From:</h5>
                                <div class="company-info">
                                    <strong>ZYILER ORGANIZATION</strong><br>
                                    <span class="text-muted">شركة زايلر</span><br>
                                    Alabama, U.S.A<br>
                                    VAT: 300123456789003<br>
                                    CR: 1010123456
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 style="color: #374151; margin-bottom: 15px;">Bill To:</h5>
                                <div class="client-info">
                                    <strong>Rob & Joe Traders</strong><br>
                                    4141 Hacienda Drive<br>
                                    Pleasanton, 94588 CA<br>
                                    USA
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Details Section -->
                    <div class="preview-section" data-section="invoice-details" style="padding: 0 20px;">
                        <div class="row">
                            <div class="col-md-8">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Invoice#:</strong></td>
                                        <td>INV-17</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Invoice Date:</strong></td>
                                        <td>16 Mar 2023</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Due Date:</strong></td>
                                        <td>16 Mar 2023</td>
                                    </tr>
                                    <tr>
                                        <td><strong>P.O.#:</strong></td>
                                        <td>SO-17</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="qr-code" style="width: 100px; height: 100px; background: #f3f4f6; border: 1px dashed #d1d5db; display: flex; align-items: center; justify-content: center; margin-left: auto;">
                                    <i class="fas fa-qrcode fa-2x text-muted"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table Section -->
                    <div class="preview-section" data-section="items-table" style="padding: 20px;">
                        <table class="table table-bordered">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th>#</th>
                                    <th>Item & Description</th>
                                    <th>Qty</th>
                                    <th>Rate</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>
                                        <strong>Brochure Design</strong><br>
                                        <small class="text-muted">Brochure Design Single Sided Color</small>
                                    </td>
                                    <td>1.00</td>
                                    <td>300.00 SAR</td>
                                    <td>300.00 SAR</td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>
                                        <strong>Web Design Package</strong><br>
                                        <small class="text-muted">Custom Themes for your business. Inclusive of 10 hours of marketing and annual training</small>
                                    </td>
                                    <td>1.00</td>
                                    <td>250.00 SAR</td>
                                    <td>250.00 SAR</td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td>
                                        <strong>Print Ad - Basic - Color</strong><br>
                                        <small class="text-muted">Print Ad 1/8 size Color</small>
                                    </td>
                                    <td>1.00</td>
                                    <td>80.00 SAR</td>
                                    <td>80.00 SAR</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals Section -->
                    <div class="preview-section" data-section="totals" style="padding: 20px;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="text-muted">
                                    <strong>Thanks for your business.</strong>
                                </div>
                                <div class="mt-3">
                                    <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjQwIiB2aWV3Qm94PSIwIDAgMTAwIDQwIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iNDAiIGZpbGw9IiMwMDcwYmEiLz48L3N2Zz4=" alt="PayPal" style="height: 30px;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless text-end">
                                    <tr>
                                        <td><strong>Sub Total:</strong></td>
                                        <td><strong>630.00 SAR</strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>VAT (15%):</strong></td>
                                        <td><strong>94.50 SAR</strong></td>
                                    </tr>
                                    <tr style="border-top: 2px solid #e9ecef;">
                                        <td><strong>Total:</strong></td>
                                        <td><strong style="font-size: 1.2em; color: #dc3545;">724.50 SAR</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Payment Made:</td>
                                        <td style="color: #dc3545;">(-) 100.00 SAR</td>
                                    </tr>
                                    <tr style="border-top: 2px solid #dc3545;">
                                        <td><strong>Balance Due:</strong></td>
                                        <td><strong style="font-size: 1.2em; color: #dc3545;">624.50 SAR</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Section -->
                    <div class="preview-section" data-section="footer" style="padding: 20px; background: #f8f9fa; border-top: 1px solid #e9ecef;">
                        <div class="row">
                            <div class="col-md-8">
                                <h6>Terms & Conditions</h6>
                                <p class="small text-muted">
                                    Your company's Terms and Conditions will be displayed here. You can add it in the invoice Preferences page under Settings.
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="signature-area" style="border-top: 1px solid #333; padding-top: 10px; margin-top: 40px;">
                                    <small>Authorized Signature</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar - Properties Panel -->
        <div class="properties-panel">
            <div class="p-3">
                <h5 class="mb-3">Properties</h5>
                
                <!-- Template Settings -->
                <div class="property-group">
                    <h6>Template Settings</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Template Name</label>
                        <input type="text" class="form-control" id="template-name" value="My Custom Template">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Paper Size</label>
                            <select class="form-select" id="paper-size">
                                <option value="A4" selected>A4</option>
                                <option value="A5">A5</option>
                                <option value="Letter">Letter</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Orientation</label>
                            <select class="form-select" id="orientation">
                                <option value="portrait" selected>Portrait</option>
                                <option value="landscape">Landscape</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Font Family</label>
                        <select class="form-select" id="font-family">
                            <option value="Open Sans" selected>Open Sans</option>
                            <option value="Arial">Arial</option>
                            <option value="Helvetica">Helvetica</option>
                            <option value="Cairo">Cairo (Arabic)</option>
                            <option value="Amiri">Amiri (Arabic)</option>
                        </select>
                    </div>
                </div>

                <!-- Margins -->
                <div class="property-group">
                    <h6>Margins (inches)</h6>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small">Top</label>
                            <input type="number" class="form-control form-control-sm" id="margin-top" value="0.7" step="0.1">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Bottom</label>
                            <input type="number" class="form-control form-control-sm" id="margin-bottom" value="0.7" step="0.1">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Left</label>
                            <input type="number" class="form-control form-control-sm" id="margin-left" value="0.55" step="0.1">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Right</label>
                            <input type="number" class="form-control form-control-sm" id="margin-right" value="0.4" step="0.1">
                        </div>
                    </div>
                </div>

                <!-- Colors -->
                <div class="property-group">
                    <h6>Colors</h6>
                    
                    <div class="mb-2">
                        <label class="form-label small">Primary Color</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" class="color-picker-input" id="primary-color" value="#2563eb">
                            <input type="text" class="form-control form-control-sm" value="#2563eb" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label small">Header Background</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" class="color-picker-input" id="header-bg" value="#f8f9fa">
                            <input type="text" class="form-control form-control-sm" value="#f8f9fa" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label small">Table Header</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" class="color-picker-input" id="table-header" value="#f8f9fa">
                            <input type="text" class="form-control form-control-sm" value="#f8f9fa" readonly>
                        </div>
                    </div>
                </div>

                <!-- Section-specific Properties -->
                <div class="property-group" id="section-properties">
                    <h6>Section Properties</h6>
                    <p class="text-muted small">Select a section to edit its properties</p>
                </div>

                <!-- Field Properties -->
                <div class="property-group" id="field-properties" style="display: none;">
                    <h6>Field Properties</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Field Label</label>
                        <input type="text" class="form-control" id="field-label">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Field Type</label>
                        <select class="form-select" id="field-type">
                            <option value="text">Text</option>
                            <option value="number">Number</option>
                            <option value="date">Date</option>
                            <option value="image">Image</option>
                            <option value="qr_code">QR Code</option>
                            <option value="barcode">Barcode</option>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-4">
                            <label class="form-label small">Size</label>
                            <input type="number" class="form-control form-control-sm" id="field-font-size" value="12">
                        </div>
                        <div class="col-4">
                            <label class="form-label small">Weight</label>
                            <select class="form-select form-select-sm" id="field-font-weight">
                                <option value="normal">Normal</option>
                                <option value="bold">Bold</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label small">Align</label>
                            <select class="form-select form-select-sm" id="field-text-align">
                                <option value="left">Left</option>
                                <option value="center">Center</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="field-required">
                        <label class="form-check-label" for="field-required">Required</label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="field-editable" checked>
                        <label class="form-check-label" for="field-editable">Editable</label>
                    </div>
                </div>

                <!-- Assets -->
                <div class="property-group">
                    <h6>Assets</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Company Logo</label>
                        <input type="file" class="form-control" id="logo-upload" accept="image/*">
                        <small class="text-muted">Recommended: 200x100px, PNG/JPG</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Signature</label>
                        <input type="file" class="form-control" id="signature-upload" accept="image/*">
                        <small class="text-muted">Recommended: 150x75px, PNG with transparency</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Background Image</label>
                        <input type="file" class="form-control" id="background-upload" accept="image/*">
                        <small class="text-muted">Optional watermark or background</small>
                    </div>
                </div>

                <!-- Advanced Settings -->
                <div class="property-group">
                    <h6>Advanced Settings</h6>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="include-payment-stub">
                        <label class="form-check-label" for="include-payment-stub">Include Payment Stub</label>
                    </div>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="show-qr-code" checked>
                        <label class="form-check-label" for="show-qr-code">Show QR Code</label>
                    </div>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="arabic-support" checked>
                        <label class="form-check-label" for="arabic-support">Arabic Support</label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="vat-breakdown" checked>
                        <label class="form-check-label" for="vat-breakdown">Show VAT Breakdown</label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Default Currency</label>
                        <select class="form-select" id="default-currency">
                            <option value="SAR" selected>Saudi Riyal (SAR)</option>
                            <option value="AED">UAE Dirham (AED)</option>
                            <option value="USD">US Dollar (USD)</option>
                            <option value="EUR">Euro (EUR)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container"></div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/spectrum/2.0.10/spectrum.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

    <script>
        // Global variables
        let currentZoom = 1;
        let selectedSection = 'header';
        let selectedField = null;
        let templateData = {};
        let isDirty = false;

        // Initialize the editor
        $(document).ready(function() {
            initializeEditor();
            setupEventListeners();
            loadTemplateData();
        });

        function initializeEditor() {
            // Initialize color pickers
            $('.color-picker-input').spectrum({
                type: "color",
                showPalette: true,
                showButtons: false,
                allowEmpty: false,
                preferredFormat: "hex",
                change: function(color) {
                    $(this).next('input').val(color.toHexString());
                    updatePreview();
                }
            });

            // Initialize sortable sections
            new Sortable(document.getElementById('sections-list'), {
                animation: 150,
                onEnd: function(evt) {
                    updateSectionOrder();
                    updatePreview();
                }
            });

            // Make preview sections clickable
            setupPreviewInteraction();
        }

        function setupEventListeners() {
            // Section selection
            $(document).on('click', '.section-item', function() {
                selectSection($(this).data('section'));
            });

            // Visibility toggle
            $(document).on('click', '.visibility-toggle', function(e) {
                e.stopPropagation();
                toggleSectionVisibility($(this));
            });

            // Language tabs
            $('.language-tab').click(function() {
                switchLanguage($(this).data('lang'));
            });

            // Form changes
            $('#template-name, #paper-size, #orientation, #font-family').change(function() {
                markDirty();
                updatePreview();
            });

            // Margin changes
            $('#margin-top, #margin-bottom, #margin-left, #margin-right').change(function() {
                markDirty();
                updatePreview();
            });

            // Color changes
            $('#primary-color, #header-bg, #table-header').change(function() {
                markDirty();
                updatePreview();
            });

            // Live preview toggle
            $('#live-preview').change(function() {
                if ($(this).is(':checked')) {
                    updatePreview();
                }
            });

            // Preview language change
            $('#preview-language').change(function() {
                updatePreview();
            });

            // File uploads
            $('#logo-upload').change(function() {
                handleFileUpload(this, 'logo');
            });

            $('#signature-upload').change(function() {
                handleFileUpload(this, 'signature');
            });

            $('#background-upload').change(function() {
                handleFileUpload(this, 'background');
            });

            // Keyboard shortcuts
            $(document).keydown(function(e) {
                if (e.ctrlKey || e.metaKey) {
                    switch(e.which) {
                        case 83: // Ctrl+S
                            e.preventDefault();
                            saveTemplate();
                            break;
                        case 90: // Ctrl+Z
                            e.preventDefault();
                            // Implement undo functionality
                            break;
                    }
                }
            });

            // Auto-save every 30 seconds
            setInterval(function() {
                if (isDirty) {
                    autoSave();
                }
            }, 30000);
        }

        function selectSection(sectionName) {
            // Update UI
            $('.section-item').removeClass('active');
            $(`.section-item[data-section="${sectionName}"]`).addClass('active');
            
            // Update preview highlight
            $('.preview-section').removeClass('selected');
            $(`.preview-section[data-section="${sectionName}"]`).addClass('selected');
            
            selectedSection = sectionName;
            loadSectionProperties(sectionName);
        }

        function loadSectionProperties(sectionName) {
            const propertiesPanel = $('#section-properties');
            
            // Clear existing properties
            propertiesPanel.html('<h6>Section Properties</h6>');
            
            // Load section-specific properties based on section type
            switch(sectionName) {
                case 'header':
                    loadHeaderProperties(propertiesPanel);
                    break;
                case 'company-info':
                    loadCompanyInfoProperties(propertiesPanel);
                    break;
                case 'items-table':
                    loadTableProperties(propertiesPanel);
                    break;
                // Add more cases for other sections
                default:
                    propertiesPanel.append('<p class="text-muted small">No specific properties for this section</p>');
            }
        }

        function loadHeaderProperties(panel) {
            panel.append(`
                <div class="mb-3">
                    <label class="form-label">Header Height</label>
                    <input type="number" class="form-control" id="header-height" value="80">
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="show-logo" checked>
                    <label class="form-check-label">Show Logo</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="center-logo">
                    <label class="form-check-label">Center Logo</label>
                </div>
            `);
        }

        function loadCompanyInfoProperties(panel) {
            panel.append(`
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="show-vat-number" checked>
                    <label class="form-check-label">Show VAT Number</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="show-cr-number" checked>
                    <label class="form-check-label">Show CR Number</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="show-arabic-name" checked>
                    <label class="form-check-label">Show Arabic Name</label>
                </div>
            `);
        }

        function loadTableProperties(panel) {
            panel.append(`
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="show-item-codes" checked>
                    <label class="form-check-label">Show Item Codes</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="show-vat-column" checked>
                    <label class="form-check-label">Show VAT Column</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="alternate-row-colors">
                    <label class="form-check-label">Alternate Row Colors</label>
                </div>
            `);
        }

        function toggleSectionVisibility(button) {
            const isVisible = button.data('visible');
            const newVisibility = !isVisible;
            
            button.data('visible', newVisibility);
            button.find('i').removeClass('fa-eye fa-eye-slash').addClass(newVisibility ? 'fa-eye' : 'fa-eye-slash');
            
            const section = button.closest('.section-item').data('section');
            $(`.preview-section[data-section="${section}"]`).toggle(newVisibility);
            
            markDirty();
            updatePreview();
        }

        function switchLanguage(lang) {
            $('.language-tab').removeClass('active');
            $(`.language-tab[data-lang="${lang}"]`).addClass('active');
            
            // Update preview content based on language
            if (lang === 'ar') {
                $('#invoice-preview').addClass('rtl-support');
            } else {
                $('#invoice-preview').removeClass('rtl-support');
            }
            
            updatePreview();
        }

        function setupPreviewInteraction() {
            // Make preview sections selectable
            $(document).on('click', '.preview-section', function() {
                const sectionName = $(this).data('section');
                selectSection(sectionName);
            });

            // Add hover effects
            $(document).on('mouseenter', '.preview-section', function() {
                $(this).css('outline', '2px dashed #3b82f6');
            });

            $(document).on('mouseleave', '.preview-section', function() {
                if (!$(this).hasClass('selected')) {
                    $(this).css('outline', 'none');
                }
            });
        }

        function updatePreview() {
            if (!$('#live-preview').is(':checked')) return;

            showLoadingOverlay();
            
            // Collect current template data
            const currentData = collectTemplateData();
            
            // Simulate API call delay
            setTimeout(function() {
                generatePreviewHTML(currentData);
                hideLoadingOverlay();
            }, 300);
        }

        function collectTemplateData() {
            return {
                template_name: $('#template-name').val(),
                paper_size: $('#paper-size').val(),
                orientation: $('#orientation').val(),
                font_family: $('#font-family').val(),
                margins: {
                    top: $('#margin-top').val(),
                    bottom: $('#margin-bottom').val(),
                    left: $('#margin-left').val(),
                    right: $('#margin-right').val()
                },
                colors: {
                    primary: $('#primary-color').val(),
                    header_bg: $('#header-bg').val(),
                    table_header: $('#table-header').val()
                },
                settings: {
                    include_payment_stub: $('#include-payment-stub').is(':checked'),
                    show_qr_code: $('#show-qr-code').is(':checked'),
                    arabic_support: $('#arabic-support').is(':checked'),
                    vat_breakdown: $('#vat-breakdown').is(':checked'),
                    default_currency: $('#default-currency').val()
                },
                sections: collectSectionData(),
                language: $('#preview-language').val()
            };
        }

        function collectSectionData() {
            const sections = [];
            $('.section-item').each(function() {
                const sectionData = {
                    name: $(this).data('section'),
                    visible: $(this).find('.visibility-toggle').data('visible'),
                    order: $(this).index()
                };
                sections.push(sectionData);
            });
            return sections;
        }

        function generatePreviewHTML(data) {
            // Apply styling based on current settings
            const previewContent = $('#preview-content');
            
            // Update font family
            previewContent.css('font-family', data.font_family);
            
            // Update colors
            $(`.preview-section[data-section="header"]`).css('background-color', data.colors.header_bg);
            $('.table thead').css('background-color', data.colors.table_header);
            $('h2, .text-primary').css('color', data.colors.primary);
            
            // Apply margins (simulation)
            $('#invoice-preview').css({
                'padding-top': `${data.margins.top * 16}px`,
                'padding-bottom': `${data.margins.bottom * 16}px`,
                'padding-left': `${data.margins.left * 16}px`,
                'padding-right': `${data.margins.right * 16}px`
            });
            
            // Show/hide sections based on visibility
            data.sections.forEach(section => {
                $(`.preview-section[data-section="${section.name}"]`).toggle(section.visible);
            });
            
            // Update currency display
            if (data.settings.default_currency) {
                $('.preview-section').find('td, span').each(function() {
                    const text = $(this).text();
                    if (text.includes('SAR')) {
                        $(this).text(text.replace('SAR', data.settings.default_currency));
                    }
                });
            }
            
            // Show/hide QR code
            $('.qr-code').toggle(data.settings.show_qr_code);
        }

        function zoomIn() {
            currentZoom = Math.min(currentZoom + 0.1, 2.0);
            updateZoom();
        }

        function zoomOut() {
            currentZoom = Math.max(currentZoom - 0.1, 0.3);
            updateZoom();
        }

        function updateZoom() {
            $('#invoice-preview').css('transform', `scale(${currentZoom})`);
            $('#zoom-level').text(`${Math.round(currentZoom * 100)}%`);
        }

        function saveTemplate() {
            showLoadingOverlay();
            
            const templateData = collectTemplateData();
            
            // Simulate API call
            $.ajax({
                url: '/invoice_templates/save',
                method: 'POST',
                data: templateData,
                success: function(response) {
                    hideLoadingOverlay();
                    if (response.success) {
                        showToast('Success', 'Template saved successfully!', 'success');
                        isDirty = false;
                    } else {
                        showToast('Error', response.message || 'Failed to save template', 'error');
                    }
                },
                error: function() {
                    hideLoadingOverlay();
                    showToast('Error', 'Network error occurred', 'error');
                }
            });
        }

        function autoSave() {
            const templateData = collectTemplateData();
            
            // Save to localStorage as backup
            localStorage.setItem('invoice_template_autosave', JSON.stringify(templateData));
            
            // Optionally save to server
            $.ajax({
                url: '/invoice_templates/autosave',
                method: 'POST',
                data: templateData,
                success: function() {
                    console.log('Template auto-saved');
                }
            });
        }

        function exportTemplate() {
            const templateData = collectTemplateData();
            
            // Create downloadable JSON file
            const dataStr = JSON.stringify(templateData, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            
            const link = document.createElement('a');
            link.href = URL.createObjectURL(dataBlob);
            link.download = `${templateData.template_name || 'invoice-template'}.json`;
            link.click();
            
            showToast('Success', 'Template exported successfully!', 'success');
        }

        function previewPDF() {
            showLoadingOverlay();
            
            const templateData = collectTemplateData();
            
            $.ajax({
                url: '/invoice_templates/generate_pdf',
                method: 'POST',
                data: templateData,
                success: function(response) {
                    hideLoadingOverlay();
                    if (response.success) {
                        // Open PDF in new window
                        window.open(response.pdf_url, '_blank');
                    } else {
                        showToast('Error', 'Failed to generate PDF preview', 'error');
                    }
                },
                error: function() {
                    hideLoadingOverlay();
                    showToast('Error', 'Failed to generate PDF preview', 'error');
                }
            });
        }

        function handleFileUpload(input, type) {
            const file = input.files[0];
            if (!file) return;
            
            // Validate file
            if (!file.type.startsWith('image/')) {
                showToast('Error', 'Please select an image file', 'error');
                return;
            }
            
            if (file.size > 2 * 1024 * 1024) { // 2MB limit
                showToast('Error', 'File size must be less than 2MB', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('asset_file', file);
            formData.append('asset_type', type);
            formData.append('template_id', templateData.id || 0);
            
            $.ajax({
                url: '/invoice_templates/upload_asset',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showToast('Success', `${type} uploaded successfully!`, 'success');
                        updatePreview();
                        markDirty();
                    } else {
                        showToast('Error', response.message, 'error');
                    }
                },
                error: function() {
                    showToast('Error', 'Upload failed', 'error');
                }
            });
        }

        function addSection() {
            // Show modal or dropdown to select section type
            const sectionTypes = [
                'header', 'company-info', 'client-info', 'invoice-details',
                'items-table', 'totals', 'footer', 'terms', 'custom'
            ];
            
            // For now, just add a custom section
            const sectionHtml = `
                <div class="section-item" data-section="custom-${Date.now()}">
                    <div class="section-header">
                        <div>
                            <i class="fas fa-plus text-success me-2"></i>
                            <strong>Custom Section</strong>
                        </div>
                        <div class="section-controls">
                            <button class="visibility-toggle" data-visible="true">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-cog"></i>
                            </button>
                        </div>
                    </div>
                    <small class="text-muted">Custom content section</small>
                </div>
            `;
            
            $('#sections-list').append(sectionHtml);
            markDirty();
            updatePreview();
        }

        function loadTemplateData() {
            // Check for autosave data
            const autosave = localStorage.getItem('invoice_template_autosave');
            if (autosave) {
                try {
                    const data = JSON.parse(autosave);
                    populateFormFromData(data);
                    showToast('Info', 'Restored from autosave', 'info');
                } catch (e) {
                    console.error('Failed to restore autosave data:', e);
                }
            }
            
            // Load template from server if editing existing
            const templateId = new URLSearchParams(window.location.search).get('id');
            if (templateId) {
                $.ajax({
                    url: `/invoice_templates/get/${templateId}`,
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            templateData = response.template;
                            populateFormFromData(templateData);
                        }
                    }
                });
            }
        }

        function populateFormFromData(data) {
            $('#template-name').val(data.template_name || '');
            $('#paper-size').val(data.paper_size || 'A4');
            $('#orientation').val(data.orientation || 'portrait');
            $('#font-family').val(data.font_family || 'Open Sans');
            
            if (data.margins) {
                $('#margin-top').val(data.margins.top || 0.7);
                $('#margin-bottom').val(data.margins.bottom || 0.7);
                $('#margin-left').val(data.margins.left || 0.55);
                $('#margin-right').val(data.margins.right || 0.4);
            }
            
            if (data.colors) {
                $('#primary-color').val(data.colors.primary || '#2563eb').trigger('change');
                $('#header-bg').val(data.colors.header_bg || '#f8f9fa').trigger('change');
                $('#table-header').val(data.colors.table_header || '#f8f9fa').trigger('change');
            }
            
            if (data.settings) {
                $('#include-payment-stub').prop('checked', data.settings.include_payment_stub || false);
                $('#show-qr-code').prop('checked', data.settings.show_qr_code !== false);
                $('#arabic-support').prop('checked', data.settings.arabic_support !== false);
                $('#vat-breakdown').prop('checked', data.settings.vat_breakdown !== false);
                $('#default-currency').val(data.settings.default_currency || 'SAR');
            }
            
            updatePreview();
        }

        function markDirty() {
            isDirty = true;
            if (!document.title.includes('*')) {
                document.title = document.title + ' *';
            }
        }

        function showLoadingOverlay() {
            $('#loading-overlay').show();
        }

        function hideLoadingOverlay() {
            $('#loading-overlay').hide();
        }

        function showToast(title, message, type = 'info') {
            const toastId = 'toast-' + Date.now();
            const bgClass = {
                success: 'bg-success',
                error: 'bg-danger',
                warning: 'bg-warning',
                info: 'bg-info'
            }[type] || 'bg-info';
            
            const toastHtml = `
                <div id="${toastId}" class="toast ${bgClass} text-white" role="alert">
                    <div class="toast-header ${bgClass} text-white border-0">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                        <strong class="me-auto">${title}</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            
            $('.toast-container').append(toastHtml);
            
            const toast = new bootstrap.Toast(document.getElementById(toastId), {
                autohide: true,
                delay: type === 'error' ? 5000 : 3000
            });
            
            toast.show();
            
            // Remove toast element after it's hidden
            document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
                this.remove();
            });
        }

        function updateSectionOrder() {
            // Update section order based on current DOM order
            $('.section-item').each(function(index) {
                $(this).data('order', index);
            });
            markDirty();
        }

        // Prevent page reload if there are unsaved changes
        window.addEventListener('beforeunload', function(e) {
            if (isDirty) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });

        // Initialize drag and drop for fields
        document.addEventListener('DOMContentLoaded', function() {
            const availableFields = document.getElementById('available-fields');
            const previewContent = document.getElementById('preview-content');
            
            // Make fields draggable
            new Sortable(availableFields, {
                group: {
                    name: 'fields',
                    pull: 'clone',
                    put: false
                },
                animation: 150,
                sort: false
            });
            
            // Make preview sections droppable
            new Sortable(previewContent, {
                group: 'fields',
                animation: 150,
                onAdd: function(evt) {
                    const fieldType = evt.item.dataset.fieldType;
                    addFieldToSection(fieldType, evt.to);
                    evt.item.remove(); // Remove the cloned element
                }
            });
        });

        function addFieldToSection(fieldType, targetSection) {
            const fieldId = 'field-' + Date.now();
            const fieldHtml = createFieldHTML(fieldType, fieldId);
            
            // Add field to the target section
            $(targetSection).append(fieldHtml);
            
            // Make the new field selectable
            setupFieldInteraction(fieldId);
            
            markDirty();
            updatePreview();
            
            showToast('Success', `${fieldType} field added successfully!`, 'success');
        }

        function createFieldHTML(fieldType, fieldId) {
            const fieldLabels = {
                text: 'Text Field',
                number: 'Number Field',
                date: 'Date Field',
                image: 'Image Field',
                qr_code: 'QR Code',
                barcode: 'Barcode',
                signature: 'Signature'
            };
            
            return `
                <div class="field-element" data-field-id="${fieldId}" data-field-type="${fieldType}" 
                     style="border: 1px dashed #ccc; padding: 8px; margin: 4px; cursor: pointer; display: inline-block;">
                    <i class="fas fa-${getFieldIcon(fieldType)} me-1"></i>
                    ${fieldLabels[fieldType] || fieldType}
                </div>
            `;
        }

        function getFieldIcon(fieldType) {
            const icons = {
                text: 'font',
                number: 'hashtag',
                date: 'calendar',
                image: 'image',
                qr_code: 'qrcode',
                barcode: 'barcode',
                signature: 'signature'
            };
            return icons[fieldType] || 'square';
        }

        function setupFieldInteraction(fieldId) {
            $(`[data-field-id="${fieldId}"]`).click(function() {
                selectField(fieldId);
            });
        }

        function selectField(fieldId) {
            $('.field-element').removeClass('selected');
            $(`[data-field-id="${fieldId}"]`).addClass('selected');
            
            selectedField = fieldId;
            loadFieldProperties(fieldId);
            $('#field-properties').show();
        }

        function loadFieldProperties(fieldId) {
            const fieldElement = $(`[data-field-id="${fieldId}"]`);
            const fieldType = fieldElement.data('field-type');
            
            $('#field-type').val(fieldType);
            $('#field-label').val(fieldElement.text().trim());
            
            // Load other field properties from data attributes or defaults
            $('#field-font-size').val(fieldElement.data('font-size') || 12);
            $('#field-font-weight').val(fieldElement.data('font-weight') || 'normal');
            $('#field-text-align').val(fieldElement.data('text-align') || 'left');
            $('#field-required').prop('checked', fieldElement.data('required') || false);
            $('#field-editable').prop('checked', fieldElement.data('editable') !== false);
        }

        // Add CSS for better visual feedback
        $('<style>').appendTo('head').html(`
            .field-element.selected {
                border-color: #3b82f6 !important;
                background-color: #eff6ff !important;
            }
            
            .preview-section.selected {
                outline: 2px solid #3b82f6 !important;
                outline-offset: -2px;
            }
            
            .section-item.disabled .section-controls {
                opacity: 0.3;
            }
            
            .fade-in {
                animation: fadeIn 0.3s ease-in;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        `);

		
    </script>

	
</body>
</html>
