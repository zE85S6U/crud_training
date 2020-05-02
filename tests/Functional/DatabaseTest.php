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
        $data = [
            'product_name' => 'テスト商品' ,
            'price' => 1000,
            'stock' => 10,
            'image_dir' => 'test.jpg',
            'description' => 'これはテスト用商品です。'
        ];

        $response = $this->runApp('POST', '/product/store', $data);

        $id = $this->container['db']->lastInsertID();
        $stmt = $this->container['db']->query('SELECT * FROM m_product WHERE product_id = ' . $id);
        $product = $stmt->fetch();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/product', (string)$response->getHeaderLine('Location'));
        $this->assertEquals('これはテスト用商品です。', $product['description']);
    }
}