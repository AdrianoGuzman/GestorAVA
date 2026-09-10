<?php

namespace Tests\Unit;

use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    public function test_that_true_is_true()
    {
        $this->assertTrue(true);
    }
}
