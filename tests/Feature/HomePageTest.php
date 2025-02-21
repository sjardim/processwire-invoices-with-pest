<?php

test('home page exists and is accessible', function () {
    $homepage = pages()->get('/');
    
    expect($homepage)->toBeProcessWirePage()
        ->and($homepage->id)->toBe(1)
        ->and($homepage->template->name)->toBe('home');
});

test('home page has the correct title', function () {
    $homepage = pages()->get('/');
    
    expect($homepage->title)->not->toBeEmpty();
});