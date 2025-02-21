<?php
// tests/Pest.php

use PHPUnit\Framework\Assert;
use ProcessWire\ProcessWire;


/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

// pest()->extend(Tests\TestCase::class)->in('Feature');


/*
|--------------------------------------------------------------------------
| Bootstrap ProcessWire
|--------------------------------------------------------------------------
*/

// Define required ProcessWire constants if not already defined
if(!defined("PROCESSWIRE")) {
    define("PROCESSWIRE", true);
}
if(!defined("PROCESSWIRE_INSTALL")) {
    define("PROCESSWIRE_INSTALL", false);
}

// Set the root path - adjust this to point to your ProcessWire installation
$rootPath = dirname(__DIR__);

// Include ProcessWire's index.php to bootstrap the application
require_once $rootPath . '/index.php';


/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeProcessWirePage', function () {
    return $this->toBeInstanceOf(\ProcessWire\Page::class);
});

expect()->extend('toContainText', function (string $text, bool $caseSensitive = true) {
    $pageContent = trim(strip_tags($this->value));
    
    if (!$caseSensitive) {
        $pageContent = strtolower($pageContent);
        $text = strtolower($text);
    }
    
    Assert::assertStringContainsString(
        $text,
        $pageContent,
        "Failed asserting that page content contains '$text'"
    );
    
    return $this;
});

expect()->extend('toContainHtml', function (string $html) {
    Assert::assertStringContainsString(
        $html,
        $this->value,
        "Failed asserting that page content contains HTML '$html'"
    );
    
    return $this;
});

expect()->extend('toContainTextInOrder', function (array $texts, bool $caseSensitive = true) {
    $pageContent = trim(strip_tags($this->value));
    
    if (!$caseSensitive) {
        $pageContent = strtolower($pageContent);
        $texts = array_map('strtolower', $texts);
    }
    
    $position = 0;
    foreach ($texts as $text) {
        $currentPosition = strpos($pageContent, $text, $position);
        
        Assert::assertNotFalse(
            $currentPosition,
            "Failed asserting that page content contains '$text'"
        );
        
        Assert::assertGreaterThanOrEqual(
            $position,
            $currentPosition,
            sprintf(
                "Failed asserting that '%s' comes after '%s'",
                $text,
                $texts[array_search($text, $texts) - 1] ?? 'start'
            )
        );
        
        $position = $currentPosition + strlen($text);
    }
    
    return $this;
});

// Helper function to get rendered page content
function getPageContent(\ProcessWire\Page $page): string {
    // Store current page
    $currentPage = wire('page');
    
    // Set page we want to render
    wire('page', $page);
    
    // Render the page
    $content = $page->render();
    
    // Restore original page
    wire('page', $currentPage);
    
    return $content;
}


/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function wire($var = null) {
    if(is_null($var)) return ProcessWire::getCurrentInstance();
    return ProcessWire::getCurrentInstance()->$var;
}

function pages() {
    return wire('pages');
}

function fields() {
    return wire('fields');
}

function modules() {
    return wire('modules');
}