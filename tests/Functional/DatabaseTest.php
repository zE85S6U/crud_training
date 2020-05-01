<?php


namespace Tests\Functional;


class DatabaseTest extends BaseTestCase
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

    public function testStore()
    {
        $response = $this->runApp('POST', '/', ['subject' => 'テストチケット']);

        $id = $this->container['db']->lastInsertID();
        $stmt = $this->container['db']->query('SELECT * FROM tickets WHERE id = ' . $id);
        $ticket = $stmt->fetch();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/tickets', (string)$response->getHeaderLine('Location'));
        $this->assertEquals('テストチケット', $ticket['subject']);
    }
}