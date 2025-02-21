<?php
// tests/Pest.php

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