<?php


namespace Classes\Controllers;


use PDO;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\NotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;

class ShoppingController extends Controller
{
    /**
     * 買い物サイトトップ
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function index(Request $request, Response $response): ResponseInterface
    {
        $sql = 'SELECT * FROM m_product ORDER BY product_id';
        $stmt = $this->db->query($sql);
        $products = $stmt->fetchAll();

        $stmt = null;
        $sql = 'SELECT product_name, nickname, CAST(m_product.create_at as date) as Now 
                    FROM m_product ORDER BY create_at DESC LIMIT 5';
        $stmt = $this->db->query($sql);
        $news = $stmt->fetchAll();
        $data = ['products' => $products
            , 'news' => $news
        ];

        return $this->renderer->render($response, '/shopping/index.phtml', $data);
    }

    /**
     * 選択した商品の詳細
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface
     * @throws NotFoundException
     */
    public function show(Request $request, Response $response, array $args): ResponseInterface
    {
        $sql = 'SELECT * FROM m_product WHERE product_id = :id';
        $stmt = $this->db->prepare($sql);
        $id = (int)$args['id'];
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch();

        // 存在しない商品番号にアクセスした場合
        if (!$product) throw new NotFoundException($request, $response);

        $data = ['product' => $product];
        return $this->renderer->render($response, '/shopping/item.phtml', $data);
    }
}