<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('preprocess_image_universal')) {
    
    /**
     * Universal image preprocessing for all invoice types
     */
    function preprocess_image_universal($imagePath) {
        if (!extension_loaded('imagick')) {
            return false;
        }

        try {
            $imagick = new Imagick($imagePath);
            
            // 1. Get image info
            $width = $imagick->getImageWidth();
            $height = $imagick->getImageHeight();
            $format = $imagick->getImageFormat();
            
            // 2. Remove transparency and flatten
            $imagick->setImageBackgroundColor('white');
            $imagick = $imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            
            // 3. Convert to grayscale (better for OCR)
            $imagick->setImageColorspace(Imagick::COLORSPACE_GRAY);
            
            // 4. Auto-orient based on EXIF
            $imagick->autoOrient();
            
            // 5. Auto-deskew (straighten text)
            auto_deskew_image($imagick);
            
            // 6. Enhance contrast
            $imagick->normalizeImage();
            $imagick->contrastImage(1);
            $imagick->brightnessContrastImage(5, 20);
            
            // 7. Remove noise
            $imagick->medianFilterImage(1);
            
            // 8. Sharpen
            $imagick->sharpenImage(1, 0.5);
            
            // 9. Set high DPI for OCR
            $imagick->setImageResolution(300, 300);
            $imagick->resampleImage(300, 300, Imagick::FILTER_LANCZOS, 1);
            
            // 10. Save as PNG for best quality
            $imagick->setImageFormat('png');
            $imagick->writeImage($imagePath);
            
            $imagick->clear();
            $imagick->destroy();
            
            return true;
            
        } catch (Exception $e) {
            log_message('error', 'Universal preprocessing failed: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('auto_deskew_image')) {
    
    /**
     * Auto-deskew image (straighten rotated text)
     */
    function auto_deskew_image(&$imagick) {
        try {
            // Create a clone for deskew analysis
            $clone = clone $imagick;
            
            // Convert to black and white for deskew
            $clone->thresholdImage(0.5 * Imagick::getQuantum());
            
            // Deskew
            $clone->deskewImage(0.5 * Imagick::getQuantum());
            
            // Get the deskew angle from properties
            $angle = $clone->getImageProperty('deskew:angle');
            
            if ($angle && abs($angle) > 0.5) {
                // Rotate original image
                $imagick->rotateImage('white', -$angle);
            }
            
            $clone->clear();
            $clone->destroy();
            
        } catch (Exception $e) {
            // Skip if deskew fails
        }
    }
}

if (!function_exists('run_ocr_universal')) {
    
    /**
     * Universal OCR function for all invoice types
     */
    function run_ocr_universal($imagePath, $page_info = []) {
        $tesseract = '"C:\Program Files\Tesseract-OCR\tesseract.exe"';
        $tmpDir = FCPATH . 'assets/uploads/tmp/';
        
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }
        
        $tmpBase = $tmpDir . 'ocr_universal_' . md5($imagePath) . '_' . time();
        
        // UNIVERSAL CONFIGURATION FOR ALL INVOICES:
        // 1. Multiple languages (add more as needed)
        // 2. LSTM engine (most accurate)
        // 3. Adaptive page segmentation
        // 4. High DPI
        
        $languages = 'eng'; // Start with English
        
        // Auto-detect language presence
        if (is_arabic_text_present($imagePath)) {
            $languages .= '+ara';
        }
        if (is_french_text_present($imagePath)) {
            $languages .= '+fra';
        }
        if (is_spanish_text_present($imagePath)) {
            $languages .= '+spa';
        }
        
        // Build command with universal settings
        $cmd = sprintf(
            '%s "%s" "%s" -l %s --oem 1 --psm 3 --dpi 300 -c preserve_interword_spaces=1 -c tessedit_pageseg_mode=3 2>&1',
            $tesseract,
            $imagePath,
            $tmpBase,
            $languages
        );
        
        // Execute OCR
        $output = [];
        $return_code = 0;
        exec($cmd, $output, $return_code);
        
        // Read results
        $txtFile = $tmpBase . '.txt';
        if (!file_exists($txtFile)) {
            return [
                'success' => false,
                'error' => 'OCR output file not created',
                'output' => implode("\n", $output)
            ];
        }
        
        $ocrText = trim(file_get_contents($txtFile));
        
        // Calculate confidence
        $confidence = calculate_ocr_confidence($ocrText, $output);
        
        // Clean up
        @unlink($txtFile);
        
        return [
            'success' => true,
            'text' => $ocrText,
            'confidence' => $confidence,
            'languages' => $languages,
            'raw_output' => $output
        ];
    }
}

if (!function_exists('is_arabic_text_present')) {
    
    /**
     * Detect if Arabic text is present in image
     */
    function is_arabic_text_present($imagePath) {
        // Quick OCR with Arabic only to check
        $tesseract = '"C:\Program Files\Tesseract-OCR\tesseract.exe"';
        $tmpDir = FCPATH . 'assets/uploads/tmp/';
        $tmpBase = $tmpDir . 'check_arabic_' . time();
        
        $cmd = $tesseract . ' ' . escapeshellarg($imagePath) . ' ' . escapeshellarg($tmpBase) . 
               ' -l ara --oem 1 --psm 6 --dpi 150 2>&1';
        
        exec($cmd, $output, $return_code);
        
        $txtFile = $tmpBase . '.txt';
        if (file_exists($txtFile)) {
            $text = file_get_contents($txtFile);
            @unlink($txtFile);
            
            // Check for Arabic characters
            return preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $text);
        }
        
        return false;
    }
}

if (!function_exists('calculate_ocr_confidence')) {
    
    /**
     * Calculate OCR confidence score
     */
    function calculate_ocr_confidence($text, $tesseract_output) {
        if (empty($text)) return 0;
        
        $confidence = 50; // Base confidence
        
        // Length-based confidence
        $length = strlen($text);
        if ($length > 100) $confidence += 10;
        if ($length > 500) $confidence += 10;
        if ($length < 10) return 10;
        
        // Word count confidence
        $word_count = str_word_count($text);
        if ($word_count > 20) $confidence += 10;
        if ($word_count < 5) return 20;
        
        // Invoice-specific term bonus
        $invoice_terms = [
            'invoice', 'bill', 'receipt', 'payment', 'total', 'amount', 'date',
            'customer', 'client', 'vendor', 'supplier', 'tax', 'vat', 'gst',
            'quantity', 'price', 'subtotal', 'discount', 'due', 'balance'
        ];
        
        $term_count = 0;
        $lower_text = strtolower($text);
        foreach ($invoice_terms as $term) {
            if (strpos($lower_text, $term) !== false) {
                $term_count++;
            }
        }
        
        $confidence += ($term_count * 3);
        
        // Check Tesseract output for confidence scores
        foreach ($tesseract_output as $line) {
            if (strpos($line, 'confidence') !== false) {
                if (preg_match('/confidence\s*(\d+)/i', $line, $matches)) {
                    $tess_conf = intval($matches[1]);
                    $confidence = max($confidence, $tess_conf);
                }
            }
        }
        
        return min(95, $confidence);
    }
}

if (!function_exists('extract_invoice_data_universal')) {
    
    /**
     * Universal invoice data extraction
     */
    function extract_invoice_data_universal($ocr_text) {
        $data = [];
        
        // Clean text
        $text = clean_ocr_text($ocr_text);
        
        // ========== INVOICE NUMBER ==========
        $patterns_invoice_no = [
            '/(?:invoice\s*(?:no|number|#)|facture\s*n°|rechnung\s*nr\.?)[:\s]*([A-Za-z0-9\-\.\/]+)/i',
            '/(?:رقم\s*الفاتورة|رقم\s*الفاکتور|发票号码)[:\s]*([A-Za-z0-9\-\.\/]+)/i',
            '/(?:№|No\.|#)\s*([A-Za-z0-9\-\.\/]+)/',
            '/INV[-_\.]?([A-Za-z0-9]+)/i',
            '/INVOICE\s+([A-Za-z0-9\-]+)/i'
        ];
        
        foreach ($patterns_invoice_no as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $data['invoice_no'] = trim($matches[1]);
                break;
            }
        }
        
        // ========== DATES ==========
        // International date patterns
        $date_patterns = [
            // 2024-12-31
            '/(\d{4}[-\.\/]\d{1,2}[-\.\/]\d{1,2})/',
            // 31/12/2024
            '/(\d{1,2}[-\.\/]\d{1,2}[-\.\/]\d{4})/',
            // 31-Dec-2024
            '/(\d{1,2}[-\.\/]\w{3,}[-\.\/]\d{4})/',
            // December 31, 2024
            '/(\w+\s+\d{1,2},?\s+\d{4})/',
        ];
        
        $dates_found = [];
        foreach ($date_patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $date_str) {
                    $dates_found[] = $date_str;
                }
            }
        }
        
        $dates_found = array_unique($dates_found);
        
        // Identify invoice date
        foreach ($dates_found as $date_str) {
            $context = get_text_context($text, $date_str, 50);
            $lower_context = strtolower($context);
            
            if (strpos($lower_context, 'invoice') !== false || 
                strpos($lower_context, 'date') !== false ||
                strpos($lower_context, 'فاتورة') !== false ||
                strpos($lower_context, 'تاریخ') !== false) {
                $data['invoice_date'] = parse_date_universal($date_str);
                break;
            }
        }
        
        // Identify due date
        foreach ($dates_found as $date_str) {
            $context = get_text_context($text, $date_str, 50);
            $lower_context = strtolower($context);
            
            if (strpos($lower_context, 'due') !== false || 
                strpos($lower_context, 'pay by') !== false ||
                strpos($lower_context, 'استحقاق') !== false ||
                strpos($lower_context, 'دفع') !== false) {
                $data['due_date'] = parse_date_universal($date_str);
                break;
            }
        }
        
        // ========== AMOUNTS ==========
        // Currency symbols from around the world
        $currency_patterns = [
            '/USD\s*([\d,]+\.?\d*)/i',
            '/EUR\s*([\d,]+\.?\d*)/i',
            '/GBP\s*([\d,]+\.?\d*)/i',
            '/SAR\s*([\d,]+\.?\d*)/i',
            '/AED\s*([\d,]+\.?\d*)/i',
            '/₹\s*([\d,]+\.?\d*)/',
            '/¥\s*([\d,]+\.?\d*)/',
            '/€\s*([\d,]+\.?\d*)/',
            '/£\s*([\d,]+\.?\d*)/',
            '/\$\s*([\d,]+\.?\d*)/',
            '/(?:ريال|ر\.س\.)\s*([\d,]+\.?\d*)/',
            '/د\.إ\.?\s*([\d,]+\.?\d*)/',
        ];
        
        $amounts_found = [];
        foreach ($currency_patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $amount_str) {
                    $amount = parse_amount_universal($amount_str);
                    if ($amount > 0) {
                        $amounts_found[] = $amount;
                    }
                }
            }
        }
        
        // Also find amounts without currency symbols
        if (preg_match_all('/(\d{1,3}(?:,\d{3})*(?:\.\d{2}))/', $text, $matches)) {
            foreach ($matches[1] as $amount_str) {
                $amount = parse_amount_universal($amount_str);
                if ($amount > 10) { // Filter out small numbers
                    $amounts_found[] = $amount;
                }
            }
        }
        
        // Sort amounts descending
        rsort($amounts_found);
        
        // Identify total amount
        $total_patterns = [
            '/(?:total\s*amount|grand\s*total|amount\s*due|total\s*payable)[:\s]*([$\€\£\¥\w\s]*[\d,]+\.?\d*)/i',
            '/(?:المجموع\s*النهائي|المبلغ\s*الإجمالي|المبلغ\s*المستحق)[:\s]*([$\€\£\¥\w\s]*[\d,]+\.?\d*)/i',
            '/(?:montant\s*total|total\s*à\s*payer)[:\s]*([$\€\£\¥\w\s]*[\d,]+\.?\d*)/i',
        ];
        
        foreach ($total_patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $data['grand_total'] = parse_amount_universal($matches[1]);
                break;
            }
        }
        
        // If no pattern match, use largest amount
        if (!isset($data['grand_total']) && !empty($amounts_found)) {
            $data['grand_total'] = $amounts_found[0];
        }
        
        // ========== CUSTOMER/VENDOR INFO ==========
        // Customer name patterns
        $customer_patterns = [
            '/(?:customer\s*name|client\s*name|bill\s*to)[:\s]*(.+?)(?=\n|invoice|total|$)/is',
            '/(?:اسم\s*العميل|اسم\s*الزبون|المستفيد)[:\s]*(.+?)(?=\n|فاتورة|المجموع|$)/is',
            '/(?:nom\s*du\s*client|destinataire)[:\s]*(.+?)(?=\n|facture|total|$)/is',
        ];
        
        foreach ($customer_patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $data['customer_name'] = trim($matches[1]);
                break;
            }
        }
        
        // Vendor/Supplier name
        $vendor_patterns = [
            '/(?:supplier|vendor|from|seller)[:\s]*(.+?)(?=\n|invoice|date|$)/is',
            '/(?:المورد|البائع|من)[:\s]*(.+?)(?=\n|فاتورة|تاريخ|$)/is',
            '/(?:fournisseur|vendeur)[:\s]*(.+?)(?=\n|facture|date|$)/is',
        ];
        
        foreach ($vendor_patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $data['vendor_name'] = trim($matches[1]);
                break;
            }
        }
        
        // ========== TAX/VAT INFO ==========
        if (preg_match('/(?:vat\s*(?:no|number|id)|gstin|tax\s*id)[:\s]*([A-Za-z0-9\-]+)/i', $text, $matches)) {
            $data['tax_id'] = trim($matches[1]);
        }
        
        if (preg_match('/(?:vat|gst|tax)\s*(?:amount|total)[:\s]*([$\€\£\¥\w\s]*[\d,]+\.?\d*)/i', $text, $matches)) {
            $data['tax_amount'] = parse_amount_universal($matches[1]);
        }
        
        // ========== ADDRESS ==========
        if (preg_match('/(?:address|location|адрес|عنوان)[:\s]*(.+?)(?=\n|phone|email|$)/is', $text, $matches)) {
            $data['address'] = trim($matches[1]);
        }
        
        // ========== LINE ITEMS (Basic extraction) ==========
        $data['line_items'] = extract_line_items($text);
        
        // Calculate confidence for extraction
        $data['extraction_confidence'] = calculate_extraction_confidence($data);
        
        return $data;
    }
}

if (!function_exists('clean_ocr_text')) {
    
    /**
     * Clean OCR text
     */
    function clean_ocr_text($text) {
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Fix common OCR errors
        $replacements = [
            '/0(\d)/' => 'O$1',
            '/[|](\d)/' => '1$1',
            '/[l](\d)/' => '1$1',
            '/\bT0\b/' => 'TO',
            '/\b1n\b/' => 'in',
            '/\bteh\b/' => 'the',
            '/\bw1th\b/' => 'with',
            '/1nvoice/' => 'Invoice',
            '/Cust0mer/' => 'Customer',
        ];
        
        foreach ($replacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }
        
        return trim($text);
    }
}

if (!function_exists('get_text_context')) {
    
    /**
     * Get context around a string
     */
    function get_text_context($text, $search, $chars = 50) {
        $pos = stripos($text, $search);
        if ($pos === false) return '';
        
        $start = max(0, $pos - $chars);
        $end = min(strlen($text), $pos + strlen($search) + $chars);
        
        return substr($text, $start, $end - $start);
    }
}

if (!function_exists('parse_date_universal')) {
    
    /**
     * Parse date in any format
     */
    function parse_date_universal($date_str) {
        try {
            $date = new DateTime($date_str);
            return $date->format('Y-m-d');
        } catch (Exception $e) {
            // Try common formats
            $formats = [
                'd/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y', 'm-d-Y',
                'd M Y', 'M d, Y', 'd F Y', 'F d, Y'
            ];
            
            foreach ($formats as $format) {
                $date = DateTime::createFromFormat($format, $date_str);
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            }
            
            return null;
        }
    }
}

if (!function_exists('parse_amount_universal')) {
    
    /**
     * Parse amount in any format
     */
    function parse_amount_universal($amount_str) {
        // Remove currency symbols and spaces
        $clean = preg_replace('/[^\d\.\-]/', '', $amount_str);
        return floatval($clean);
    }
}

if (!function_exists('extract_line_items')) {
    
    /**
     * Extract line items from invoice text
     */
    function extract_line_items($text) {
        $items = [];
        $lines = explode("\n", $text);
        
        foreach ($lines as $line) {
            // Look for item patterns: quantity, description, price
            if (preg_match('/(\d+)\s+(.+?)\s+([\d,]+\.?\d{0,2})\s+([\d,]+\.?\d{0,2})/', $line, $matches)) {
                $items[] = [
                    'quantity' => intval($matches[1]),
                    'description' => trim($matches[2]),
                    'unit_price' => parse_amount_universal($matches[3]),
                    'total_price' => parse_amount_universal($matches[4])
                ];
            }
        }
        
        return $items;
    }
}

if (!function_exists('calculate_extraction_confidence')) {
    
    /**
     * Calculate confidence for extracted data
     */
    function calculate_extraction_confidence($data) {
        $confidence = 0;
        $total_fields = 0;
        $filled_fields = 0;
        
        $important_fields = ['invoice_no', 'invoice_date', 'grand_total', 'customer_name'];
        
        foreach ($important_fields as $field) {
            $total_fields++;
            if (!empty($data[$field])) {
                $filled_fields++;
                $confidence += 20;
            }
        }
        
        // Bonus for having multiple fields
        if ($filled_fields >= 3) {
            $confidence += 20;
        }
        
        return min(95, $confidence);
    }
}