<?php
// tests/Feature/TemplateTest.php

test('basic page template exists', function () {
    $template = \ProcessWire\wire('templates')->get('basic-page');
    
    expect($template)->not->toBeNull()
        ->and($template->hasField('title'))->toBeTrue()
        ->and($template->name)->toBe('basic-page');
});

test('admin template has the required fields', function () {
    $template = \ProcessWire\wire('templates')->get('admin');
    $requiredFields = ['title', 'process'];
    
    foreach ($requiredFields as $fieldName) {
        expect($template->hasField($fieldName))->toBeTrue();
    }
});