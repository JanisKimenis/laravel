<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

trait RefreshMongoDatabase
{
    use RefreshDatabase;

    protected function refreshMongoDatabase(): void
    {
        $this->refreshDatabase();
    }
}
