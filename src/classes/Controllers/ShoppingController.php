<?php


namespace Classes\Controllers;


use Exception;
use PDO;
use Slim\Http\Request;
use Slim\Http\Response;

class ShoppingController extends Controller
{
    /**
     * 買い物サイトトップ
     * @param Request $request
     * @param Response $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(Request $request, Response $response)
    {
        $sql = 'SELECT * FROM m_product';
        $stmt = $this->db->query($sql);
        $products = $stmt->fetchAll();
        $data = ['products' => $products];

        return $this->renderer->render($response, '/shopping/index.phtml', $data);
    }

    /**
     * 選択した商品の詳細
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function show(Request $request, Response $response, array $args)
    {
        $sql = 'SELECT * FROM m_product WHERE product_id = :id';
        $stmt = $this->db->prepare($sql);
        $id = (int)$args['id'];
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch();
        $data = ['product' => $product];
        return $this->renderer->render($response, '/shopping/item.phtml', $data);
    }

    /**
     * @param $id
     * @return array
     * @throws Exception
     */
    private function fetchProduct($id): array
    {
        $sql = 'SELECT * FROM m_product WHERE product_id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();
        if (!$product) {
            throw new Exception('not found');
        }
        return $product;
    }

}