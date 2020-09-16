<?php

namespace Tests\Integration\Import;

use App\Models\Organisation;
use Tests\TestCase;

class SpreadsheetHandlerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_import_a_spreadsheet()
    {
        $organisations = factory(Organisation::class, 20)->create();

    }
}
