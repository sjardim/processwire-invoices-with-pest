<?php

test('about us page exists and is accessible', function () {
    $aboutpage = pages()->get('/about-us');
    
    expect($aboutpage)->toBeProcessWirePage()
        ->and($aboutpage->parent->id)->toBe(1)
        ->and($aboutpage->template->name)->toBe('basic-page');
});

test('about us page has the correct title and content', function () {
    $aboutpage = pages()->get('/about-us');
    
    expect($aboutpage)
        ->title->toBe('About Us')
        ->body->toContain('This is the About Us page.');
});