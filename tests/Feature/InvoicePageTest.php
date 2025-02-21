<?php

// Helper functions for creating test data
function createTestInvoice() {
    $pages = pages();
    $parent = $pages->get('/invoices/'); // Adjust path as needed
    
    if (!$parent->id) {
        $parent = $pages->get(1);
    }
    
    $invoice = $pages->add('invoice', $parent, [
        'title' => 'Test Invoice ' . time(),
        'date' => time(),
    ]);
    
    $invoice->save();
    return $invoice;
}

// Helper functions for creating test data
function createInvoiceDayPage($qty) {
    $pages = pages();
    $parent = $pages->get('/settings/invoice-days/');
    
    if (!$parent->id) {
        // If path doesn't exist, use settings or root
        $settings = $pages->get('/settings/');
        $parent = $settings->id ? $settings : $pages->get(1);
    }
    
    $days = $pages->add('invoice-day', $parent, [
        'title' => "$qty Days",
        'qty' => $qty,
    ]);
    
    $days->save();
    return $days;
}

function cleanup($page) {
    if ($page && $page->id) {
        pages()->delete($page, true);
    }
}

beforeEach(function() {
    $this->invoice = null;
});

afterEach(function() {
    if (isset($this->invoice) && $this->invoice) {
        cleanup($this->invoice);
    }
});

test('getSubtotal calculates correct invoice subtotal', function() {
    $invoice = createTestInvoice();
    
    $invoice->addLineItem('hours', 'Development', 10, 100);
    $invoice->save();
    
    $invoice->addLineItem('service', 'Consulting', 5, 150);
    $invoice->save();
    
    $invoice->addLineItem('product', 'License', 1, 500);
    $invoice->save();
    
    expect($invoice->getSubtotal())->toBe(2250.0);
    expect($invoice->getSubtotal())->toBeNumeric(2250.0);
    
    $invoice->addLineItem('hours', 'Support', 2, 75);
    $invoice->save();
    
    expect($invoice->getSubtotal())->toBe(2400.0);
    expect($invoice->getSubtotal())->toBeNumeric(2400.0);
    
    $invoice->addLineItem('hours', 'Planning', 2.5, 90.50);
    $invoice->save();
    
    expect($invoice->getSubtotal())->toBe(2626.25);
    expect($invoice->getSubtotal())->toBeNumeric();
    
    cleanup($invoice);
});

test('getPaymentsTotal calculates sum of all payments', function() {
    $invoice = createTestInvoice();
    
    expect($invoice->getPaymentsTotal())->toBeNumeric(0.0);
    
    $invoice->addPayment(time(), 500, 'Initial payment');
    $invoice->save();
    
    expect($invoice->getPaymentsTotal())->toBeNumeric(500.0);
    
    $invoice->addPayment(time() - 86400, 750, 'Second payment');
    $invoice->save();
    
    expect($invoice->getPaymentsTotal())->toBeNumeric(1250.0);
    
    $invoice->addPayment(time() + 86400, 100.75, 'Final payment');
    $invoice->save();
    
    expect($invoice->getPaymentsTotal())->toBeNumeric(1350.75);
    
    cleanup($invoice);
});

test('getTotalDue calculates correct amount due', function() {
    $invoice = createTestInvoice();
    
    $invoice->addLineItem('hours', 'Development', 10, 100);
    $invoice->save();
    
    $invoice->addLineItem('service', 'Consulting', 5, 150);
    $invoice->save();
    
    expect($invoice->getTotalDue())->toBe(1750.0);
    expect($invoice->getTotalDue())->toBeNumeric(1750.0);
    
    $invoice->addPayment(time(), 1000, 'Partial payment');
    $invoice->save();
    
    expect($invoice->getTotalDue())->toBe(750.0);
    expect($invoice->getTotalDue())->toBeNumeric(750.0);
    
    $invoice->addPayment(time(), 800, 'Overpayment');
    $invoice->save();
    
    expect($invoice->getTotalDue())->toBeNumeric(-50.0);
    
    cleanup($invoice);
});

test('isPaid correctly determines if invoice is fully paid', function() {
    $invoice = createTestInvoice();
    
    $invoice->addLineItem('hours', 'Development', 10, 150);
    $invoice->save();
    
    $invoice->addLineItem('product', 'License', 1, 500);
    $invoice->save();
    
    expect($invoice->isPaid())->toBeFalse();
    
    $invoice->addPayment(time(), 1000, 'Partial payment');
    $invoice->save();
    
    expect($invoice->isPaid())->toBeFalse();
    
    $invoice->addPayment(time(), 1000, 'Final payment');
    $invoice->save();
    
    expect($invoice->isPaid())->toBeTrue();
    
    cleanup($invoice);
});

test('overpaid invoice is considered paid', function() {
    $invoice = createTestInvoice();
    
    $invoice->addLineItem('service', 'Consulting', 5, 100);
    $invoice->save();
    
    $invoice->addPayment(time(), 600, 'Overpayment');
    $invoice->save();
    
    expect($invoice->isPaid())->toBeTrue();
    
    cleanup($invoice);
});

test('getDueDate calculates the correct due date', function() {
    $invoice = createTestInvoice();
    
    $invoice_date = time();
    $invoice->date = $invoice_date;
    $invoice->save();
    
    $days = createInvoiceDayPage(30);
    $invoice->invoice_days = $days;
    $invoice->save();
    
    $expected_due_date = $invoice_date + (30 * 86400);
    expect($invoice->getDueDate())->toBe($expected_due_date);
    
    // Test zero days
    $zeroDays = createInvoiceDayPage(0);
    $invoice->invoice_days = $zeroDays;
    $invoice->save();
    
    expect($invoice->getDueDate(true))->toBe(_('Upon receipt'));
    
    cleanup($invoice);
});

test('isPastDue identifies past due unpaid invoices', function() {
    $invoice = createTestInvoice();
    
    $invoice->date = time() - (40 * 86400);
    $invoice->save();
    
    $days = createInvoiceDayPage(30);
    $invoice->invoice_days = $days;
    $invoice->save();
    
    $invoice->addLineItem('service', 'Consulting', 1, 1000);
    $invoice->save();
    
    expect($invoice->isPastDue())->toBeTrue();
    
    $invoice->addPayment(time(), 1000, 'Full payment');
    $invoice->save();
    
    expect($invoice->isPastDue())->toBeFalse();
    
    cleanup($invoice);
});

test('getDaysRemaining calculates correct days remaining', function() {
    $invoice = createTestInvoice();
    
    $invoice->date = time();
    $invoice->save();
    
    $days = createInvoiceDayPage(30);
    $invoice->invoice_days = $days;
    $invoice->save();
    
    $invoice->addLineItem('service', 'Work', 1, 1000);
    $invoice->save();
    
    expect($invoice->getDaysRemaining())->toBeGreaterThan(29);
    expect($invoice->getDaysRemaining())->toBeLessThanOrEqual(30);
    
    // Test past due
    $invoice->date = time() - (40 * 86400);
    $invoice->save();
    
    expect($invoice->getDaysRemaining())->toBeLessThan(-9);
    expect($invoice->getDaysRemaining())->toBeGreaterThan(-11);
    
    // Test paid invoice
    $invoice->addPayment(time(), 1000, 'Full payment');
    $invoice->save();
    
    expect($invoice->getDaysRemaining())->toBe(0);
    
    cleanup($invoice);
});

test('getPaidInDays returns false for unpaid invoices', function() {
    $invoice = createTestInvoice();
    
    $invoice->addLineItem('service', 'Work', 1, 1000);
    $invoice->save();
    
    expect($invoice->getPaidInDays())->toBeFalse();
    
    cleanup($invoice);
});

test('getPaidInDays calculates days between invoice date and payment', function() {
    $invoice = createTestInvoice();
    
    $invoice_date = time() - (20 * 86400);
    $invoice->date = $invoice_date;
    $invoice->save();
    
    $invoice->addLineItem('service', 'Work', 1, 1000);
    $invoice->save();
    
    $payment_date = $invoice_date + (10 * 86400);
    $invoice->addPayment($payment_date, 1000, 'Full payment');
    $invoice->save();
    
    expect($invoice->getPaidInDays())->toBeNumeric();
    expect($invoice->getPaidInDays())->toBe(10.0);
    
    cleanup($invoice);
});

test('getPaidInDays uses last payment date with multiple payments', function() {
    $invoice = createTestInvoice();
    
    $baseDate = time() - (30 * 86400);
    $invoice->date = $baseDate;
    $invoice->save();
    
    $invoice->addLineItem('service', 'Work', 1, 1000);
    $invoice->save();
    
    $invoice->addPayment($baseDate + (10 * 86400), 400, 'First payment');
    $invoice->save();
    
    $invoice->addPayment($baseDate + (20 * 86400), 600, 'Second payment');
    $invoice->save();
    
    expect($invoice->getPaidInDays())->toBe(20.0);
    
    cleanup($invoice);
});

test('addLineItem correctly adds invoice line items', function() {
    $invoice = createTestInvoice();
    
    $item1 = $invoice->addLineItem('hours', 'Development', 10, 100);
    $invoice->save();
    
    expect($invoice->invoice_items->count())->toBe(1);
    expect($item1->item_type)->toBe('hours');
    expect($item1->title)->toBe('Development');
    expect($item1->qty)->toBeNumeric(10.0);
    expect($item1->rate)->toBeNumeric(100.0);
    
    $item2 = $invoice->addLineItem('product', 'License', 1, 500);
    $invoice->save();
    
    expect($invoice->invoice_items->count())->toBe(2);
    
    $lastItem = $invoice->invoice_items->last();
    expect($lastItem->item_type)->toBe('product');
    expect($lastItem->title)->toBe('License');
    expect((float)$lastItem->qty)->toBeNumeric(1.0);
    expect((float)$lastItem->qty)->toBe(1.0);
    expect($lastItem->rate)->toBeNumeric(500.0);
    expect((float)$lastItem->rate)->toBe(500.0);
    
    cleanup($invoice);
});

test('addPayment correctly adds payment records', function() {
    $invoice = createTestInvoice();
    
    $invoice->addLineItem('service', 'Work', 1, 500);
    $invoice->save();
    
    $payment_date = time() - (5 * 86400);
    $payment = $invoice->addPayment($payment_date, 500, 'Deposit');
    $invoice->save();
    
    expect($invoice->invoice_payments->count())->toBe(1);
    expect($payment->date)->toBe($payment_date);
    expect($payment->total)->toBeNumeric(500.0);
    expect($payment->title)->toBe('Deposit');
    
    cleanup($invoice);
});

test('multiple payments are stored correctly', function() {
    $invoice = createTestInvoice();
    
    $invoice->addLineItem('service', 'Work', 1, 500);
    $invoice->save();
    
    $payment_date1 = time() - (5 * 86400);
    $invoice->addPayment($payment_date1, 500, 'Deposit');
    $invoice->save();
    
    $invoice->addLineItem('service', 'Items', 2, 1500);
    $invoice->save();
    
    $payment_date2 = time();
    $invoice->addPayment($payment_date2, 750.50, 'Final payment');
    $invoice->save();
    
    expect($invoice->invoice_payments->count())->toBe(2);
    
    $firstPayment = $invoice->invoice_payments->first();
    expect($firstPayment->date)->toBe($payment_date1);
    expect($firstPayment->total)->toBeNumeric(500.0);
    expect($firstPayment->title)->toBe('Deposit');
    
    $lastPayment = $invoice->invoice_payments->last();
    expect($lastPayment->date)->toBe($payment_date2);
    expect($lastPayment->total)->toBeNumeric(750.50);
    expect($lastPayment->title)->toBe('Final payment');
    
    cleanup($invoice);
});

test('addLog adds timestamped log entries', function() {
    $invoice = createTestInvoice();
    
    $invoice->addLog('Invoice created');
    $invoice->save();
    
    $log = $invoice->getUnformatted('invoice_log');
    expect($log)->toContain('Invoice created');
    
    $pattern = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} /';
    expect($log)->toMatch($pattern);
    
    cleanup($invoice);
});

test('addLog preserves existing log entries', function() {
    $invoice = createTestInvoice();
    
    $invoice->addLog('Invoice created');
    $invoice->save();
    
    $invoice->addLog('Payment received');
    $invoice->save();
    
    $log = $invoice->getUnformatted('invoice_log');
    expect($log)->toContain('Invoice created');
    expect($log)->toContain('Payment received');
    
    cleanup($invoice);
});

test('addLog saveNow parameter saves immediately', function() {
    $invoice = createTestInvoice();
    
    $invoice->addLog('Immediate save test', true);
    
    $updatedInvoice = pages()->get($invoice->id);
    $updatedLog = $updatedInvoice->getUnformatted('invoice_log');
    expect($updatedLog)->toContain('Immediate save test');
    
    cleanup($invoice);
});