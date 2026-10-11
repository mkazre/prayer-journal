<?php

use Tests\Support\AppTestCase;

/**
 * @internal
 */
final class SmokeTest extends AppTestCase
{
    public function testHomePageRenders(): void
    {
        $result = $this->get('/');
        $result->assertOK();
        $result->assertSee('logo-mark.png');
    }
}
