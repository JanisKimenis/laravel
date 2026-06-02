<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshMongoDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }
}
