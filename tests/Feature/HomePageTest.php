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


it('displays correct content on homepage', function() {
    $homepage = pages()->get('/');

    // First assert that we got a valid page
    expect($homepage)
        ->toBeInstanceOf(\ProcessWire\Page::class)
        ->and($homepage->id)->toBe(1);

    $content = getPageContent($homepage);
    
    expect($content)
        ->toContainText('Login to continue')
        ->toContainHtml("href='/admin/'>Login</a>");
});
