<?php

use ProcessWire\Page;

// Helper functions for creating test data
function createTestClient($data = []) {
    $pages = pages();
    $parent = $pages->get('/clients/');
    
    if (!$parent->id) {
        $parent = $pages->get(1);
    }
    
    $defaultData = [
        'title' => 'Test Client ' . time(),
        'email' => 'test' . time() . '@example.com',
        'address' => '123 Test Street',
        'website' => 'https://example.com'
    ];
    
    $data = array_merge($defaultData, $data);
    
    $client = $pages->add('client', $parent, $data);
    $client->save();
    
    return $client;
}

function createClientTestInvoice($client, $data = []) {
    $pages = pages();
    $parent = $pages->get('/invoices/');
    
    if (!$parent->id) {
        $parent = $pages->get(1);
    }
    
    $defaultData = [
        'title' => 'Test Invoice ' . time(),
        'date' => time(),
        'client' => $client
    ];
    
    $data = array_merge($defaultData, $data);
    
    $invoice = $pages->add('invoice', $parent, $data);
    $invoice->save();
    
    return $invoice;
}

function cleanupPage($page) {
    if ($page && $page->id) {
        pages()->delete($page, true);
    }
}

// // Setup and teardown
beforeEach(function() {
    $this->client = null;
    $this->invoices = [];
});

afterEach(function() {
    // Clean up invoices first (due to reference constraints)
    if (!empty($this->invoices)) {
        foreach ($this->invoices as $invoice) {
            cleanupPage($invoice);
        }
    }
    
    if (isset($this->client) && $this->client) {
        cleanupPage($this->client);
    }
});

test('client page can be created with basic properties', function() {
    $client = createTestClient([
        'title' => 'Test Client',
        'email' => 'test@example.com',
        'address' => '123 Test St',
        'website' => 'https://test.com'
    ]);
    
    expect($client)->toBeInstanceOf(\ProcessWire\ClientPage::class);
    expect($client->title)->toBe('Test Client');
    expect($client->email)->toBe('test@example.com');
    expect($client->address)->toBe('123 Test St');
    expect($client->website)->toBe('https://test.com');
    
    cleanupPage($client);
});

test('getInvoices returns correct invoices for client', function() {
    $client = createTestClient();
    
    // Create test invoices
    $invoice1 = createClientTestInvoice($client, ['date' => time() - 86400]);
    $invoice2 = createClientTestInvoice($client, ['date' => time()]);
    $invoice3 = createClientTestInvoice($client, ['date' => time() - 172800]);
    
    $this->invoices = [$invoice1, $invoice2, $invoice3];
    
    $invoices = $client->getInvoices();
    
    // Test number of invoices
    expect($invoices->count())->toBe(3);
    
    // Test sort order (-date means newest first)
    expect($invoices->first()->id)->toBe($invoice2->id);
    expect($invoices->last()->id)->toBe($invoice3->id);
    
    // Test that all invoices belong to this client
    foreach ($invoices as $invoice) {
        expect($invoice->client->id)->toBe($client->id);
    }
    
    cleanupPage($client);
});

test('getNumInvoices returns correct count of published invoices', function() {
    $client = createTestClient();
    
    // Initially should have no invoices
    expect($client->getNumInvoices())->toBe(0);
    
    // Create some invoices
    $invoice1 = createClientTestInvoice($client);
    $invoice2 = createClientTestInvoice($client);
    $this->invoices = [$invoice1, $invoice2];
    
    expect($client->getNumInvoices())->toBe(2);
    
    // Create an unpublished invoice
    $invoice3 = createClientTestInvoice($client);
    $invoice3->status(Page::statusUnpublished);
    $invoice3->save();
    $this->invoices[] = $invoice3;
    
    // Should still be 2 as unpublished invoice shouldn't count
    expect($client->getNumInvoices())->toBe(2);
    
    cleanupPage($client);
});

test('get method handles custom properties correctly', function() {
    $client = createTestClient();
    
    // Test standard property
    expect($client->get('title'))->toBe($client->title);
    expect($client->get('email'))->toBe($client->email);
    
    // Test custom properties
    expect($client->get('num_invoices'))->toBe(0);
    expect($client->get('invoices')->count())->toBe(0);
    
    // Add some invoices
    $invoice1 = createClientTestInvoice($client);
    $invoice2 = createClientTestInvoice($client);
    $this->invoices = [$invoice1, $invoice2];
    
    expect($client->get('num_invoices'))->toBe(2);
    expect($client->get('invoices')->count())->toBe(2);
    
    cleanupPage($client);
});

test('getPageListLabel returns correctly formatted label', function() {
    $client = createTestClient(['title' => 'Test Client']);
    
    // Test with no invoices
    $label = $client->getPageListLabel();
    expect($label)->toContain('Test Client');
    expect($label)->toContain('0 Invoices');
    
    // Test with one invoice
    $invoice1 = createClientTestInvoice($client);
    $this->invoices = [$invoice1];
    
    $label = $client->getPageListLabel();
    expect($label)->toContain('Test Client');
    expect($label)->toContain('1 Invoice');
    
    // Test with multiple invoices
    $invoice2 = createClientTestInvoice($client);
    $this->invoices[] = $invoice2;
    
    $label = $client->getPageListLabel();
    expect($label)->toContain('Test Client');
    expect($label)->toContain('2 Invoices');
    
    cleanupPage($client);
});

test('invoices property accessor returns same result as getInvoices method', function() {
    $client = createTestClient();
    
    // Add some invoices
    $invoice1 = createClientTestInvoice($client, ['date' => time() - 86400]);
    $invoice2 = createClientTestInvoice($client, ['date' => time()]);
    $this->invoices = [$invoice1, $invoice2];
    
    $methodResult = $client->getInvoices();
    $propertyResult = $client->invoices;
    
    expect($propertyResult->count())->toBe($methodResult->count());
    expect($propertyResult->first()->id)->toBe($methodResult->first()->id);
    expect($propertyResult->last()->id)->toBe($methodResult->last()->id);
    
    cleanupPage($client);
});

test('num_invoices property accessor returns same result as getNumInvoices method', function() {
    $client = createTestClient();
    
    expect($client->num_invoices)->toBe($client->getNumInvoices());
    
    // Add some invoices
    $invoice1 = createClientTestInvoice($client);
    $invoice2 = createClientTestInvoice($client);
    $this->invoices = [$invoice1, $invoice2];
    
    expect($client->num_invoices)->toBe($client->getNumInvoices());
    expect($client->num_invoices)->toBe(2);
    
    cleanupPage($client);
});

test('getInvoices returns empty PageArray when client has no invoices', function() {
    $client = createTestClient();
    
    $invoices = $client->getInvoices();
    expect($invoices)->toBeInstanceOf(\ProcessWire\PageArray::class);
    expect($invoices->count())->toBe(0);
    
    cleanupPage($client);
});

test('getNumInvoices handles unpublished and published invoices correctly', function() {
    $client = createTestClient();
    
    // Create mix of published and unpublished invoices
    $invoice1 = createClientTestInvoice($client);
    
    $invoice2 = createClientTestInvoice($client);
    $invoice2->status(Page::statusUnpublished);
    $invoice2->save();
    
    $invoice3 = createClientTestInvoice($client);
    
    $invoice4 = createClientTestInvoice($client);
    $invoice4->status(Page::statusUnpublished);
    $invoice4->save();
    
    $this->invoices = [$invoice1, $invoice2, $invoice3, $invoice4];
    
    // Should only count published invoices
    expect($client->getNumInvoices())->toBe(2);
    
    cleanupPage($client);
});