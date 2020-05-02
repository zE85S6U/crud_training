<?php


namespace Tests\Functional;


class ViewTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->container['db']->beginTransaction();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->container['db']->rollback();
    }

    public function testIndex()
    {
        $response = $this->runApp('GET', '/');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('ショッピング', (string)$response->getBody());
    }
}