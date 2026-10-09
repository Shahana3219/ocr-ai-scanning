<?php
public function save_purchase_ocr()
{
    header('Content-Type: application/json; charset=utf-8');
    $transactionStarted = false;

    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $referenceId = (int)$this->input->post('reference_id');
        $vendor_id = (int)$this->input->post('vendor_id');
        $customerId = (int)$this->input->post('customer_id');
        $items_json = $this->input->post('items_json');
        $items = json_decode($items_json, true);

        if ($referenceId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Purchase reference is missing. Please start from Upload Purchase.']);
            return;
        }

        if ($vendor_id <= 0 || !$this->db->where('id', $vendor_id)->where('type', 2)->where('status', 0)->get('tbl_profile')->row_array()) {
            echo json_encode(['status' => 'error', 'message' => 'Please select a valid active vendor.']);
            return;
        }

        $referenceLog = $this->db
            ->where('id', $referenceId)
            ->where('type', 109)
            ->get('tbl_logs')
            ->row_array();
        if (!$referenceLog) {
            echo json_encode(['status' => 'error', 'message' => 'The purchase reference was not found. Please start from Upload Purchase again.']);
            return;
        }

        if (empty($items) || !is_array($items)) {
            echo json_encode(['status' => 'error', 'message' => 'Please add at least one item.']);
            return;
        }

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid item data for line ' . ((int)$index + 1) . '.'
                ]);
                return;
            }
            $itemId = isset($item['item_id']) ? (int)$item['item_id'] : 0;
            if ($itemId <= 0 || !$this->db->where('id', $itemId)->get('tbl_items')->row_array()) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Please select a valid item for line ' . ((int)$index + 1) . '.'
                ]);
                return;
            }
        }

        if ($this->db->where('reference', $referenceId)->get('tbl_purchase_pocr')->row_array()) {
            echo json_encode(['status' => 'error', 'message' => 'This purchase reference has already been saved.']);
            return;
        }

        if (!$this->db->trans_begin()) {
            throw new RuntimeException('Could not start purchase transaction.');
        }
        $transactionStarted = true;

        // Calculate totals
        $total = 0;
        $grandTotal = 0;
        $vatTotal = 0;
        $discountTotal = 0;

        // Process items first to calculate totals
        foreach ($items as &$item) {
            $qty = (float)($item['quantity'] ?? 1);
            $unitPrice = (float)($item['unit_price'] ?? 0);
            $vatRate = (float)($item['vat_rate'] ?? 0);
            $discount = (float)($item['discount'] ?? 0);
            $lineTotal = ($qty * $unitPrice) - $discount;
            $vatAmount = $lineTotal * ($vatRate / 100);
            $lineGrandTotal = $lineTotal + $vatAmount;

            $total += $lineTotal;
            $vatTotal += $vatAmount;
            $grandTotal += $lineGrandTotal;
            $discountTotal += $discount;
            
            // Store calculated values back to item
            $item['line_total'] = $lineTotal;
            $item['vat_amount'] = $vatAmount;
            $item['grand_total'] = $lineGrandTotal;
        }
        unset($item);

        // 2. Prepare purchase data for tbl_purchase_pocr
        $purchaseData = [
            'reference' => $referenceId,
            'entry_date' => date('Y-m-d'),
            'inv_no' => (int)($this->input->post('supplier_invoice_no') ?: 0),
            'inv_date' => $this->input->post('invoice_date') ?: null,
            'profile_id' => $vendor_id,
            'status' => 0
        ];
        if (!$this->db->insert('tbl_purchase_pocr', $purchaseData)) {
            throw new RuntimeException('Failed to create purchase record.');
        }
        $purchaseId = $this->db->insert_id();

        if (!$purchaseId || $this->db->trans_status() === false) {
            throw new RuntimeException('Failed to create purchase record.');
        }

        // 3. Save items to tbl_purchase_items
        foreach ($items as $index => $item) {
            $itemData = [
                'reference' => $referenceId,
                'invoice_id' => $purchaseId,
                'item_id' => $item['item_id'] ?? null,
                'item_code' => $item['item_code'] ?? '',
                'item_name' => $item['description'] ?? '',
                'description' => $item['description'] ?? '',
                'quantity' => (float)($item['quantity'] ?? 1),
                'unit_id' => $item['unit_id'] ?? null,
                'price' => (float)($item['unit_price'] ?? 0),
                'discount' => (float)($item['discount'] ?? 0),
                'vat_perc' => (float)($item['vat_rate'] ?? 0),
                'vat_amt' => (float)($item['vat_amount'] ?? 0),
                'total_price' => (float)($item['grand_total'] ?? 0),
                'status' => 2
            ];

            if (!$this->db->insert('tbl_purchase_items', $itemData)) {
                throw new RuntimeException('Failed to save purchase item ' . ((int)$index + 1) . '.');
            }
        }

        // Update the log created by Upload Purchase instead of creating a second reference.
        $this->db->where('id', $referenceId)->update('tbl_logs', [
            'profile_id' => $vendor_id,
            'status' => 1
        ]);
        if ($this->db->trans_status() === false || !$this->db->trans_commit()) {
            throw new RuntimeException('Failed to finish saving the purchase.');
        }
        $transactionStarted = false;

        // 5. Return success with redirect URL
        $redirectUrl = site_url('home/newpurchase?' . http_build_query([
            'ref' => $referenceId,
            'cust' => $vendor_id,
        ]));
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Purchase saved successfully',
            'log_id' => $referenceId,
            'purchase_id' => $purchaseId,
            'reference' => $referenceId,
            'grand_total' => $grandTotal,
            'total' => $total,
            'redirect_url' => $redirectUrl
        ]);

    } catch (Exception $e) {
        if ($transactionStarted) {
            $this->db->trans_rollback();
        }
        log_message('error', "save_purchase_ocr error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}
?>