<?php
namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use ProcessWire\ProcessWire;

abstract class TestCase extends BaseTestCase
{
    protected $wire;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->wire = ProcessWire::getCurrentInstance();
        
        // Add any other setup code needed for your tests
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        // Add any cleanup code needed after tests
    }
}
