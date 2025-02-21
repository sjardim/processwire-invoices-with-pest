<?php

use ProcessWire\Page;

test('about us page exists and is accessible', function () {
    $aboutpage = pages()->get('/about-us');
    
    expect($aboutpage)->toBeProcessWirePage()
        ->and($aboutpage->parent->id)->toBe(1)
        // status(true) returns an array of status names assigned to page
        // we expect it to be empty, meaning the page is published and not hidden
        ->and($aboutpage->status(true))->toBeEmpty() 
        ->and($aboutpage->template->name)->toBe('basic-page');
});

test('about us page has the correct title and content', function () {
    $aboutpage = pages()->get('/about-us');
    
    expect($aboutpage)
        ->title->toBe('About Us')
        ->body->toContain('This is the About Us page.');
});