<?php


namespace Classes\Controllers;


use PDO;
use Slim\Http\Request;
use Slim\Http\Response;

class ProductController extends Controller
{
    /**
     * 商品一覧ページ
     * @param Request $request
     * @param Response $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(Request $request, Response $response)
    {
        return $this->renderer->render($response, '/product/index.phtml');
    }

    /**
     * 商品の追加
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws \Exception
     */
    public function store(Request $request, Response $response)
    {
        // postされたデータを代入
        $product_name = $request->getParsedBodyParam('product_name');
        $price = $request->getParsedBodyParam('price');
        $stock = $request->getParsedBodyParam('stock');
        $image_dir = $request->getParsedBodyParam('image_dir');
        $description = $request->getParsedBodyParam('description');

        $sql = 'INSERT INTO m_product (product_name, price, stock, image_dir, description) VALUES (:product_name, :price, :stock, :image_dir, :description)';

        $stmt = $this->db->prepare($sql);

        // プリペアードステートメントを安全に代入
        $stmt->bindParam(':product_name', $product_name, PDO::PARAM_STR);
        $stmt->bindParam(':price', $price, PDO::PARAM_INT);
        $stmt->bindParam(':stock', $stock, PDO::PARAM_INT);
        $stmt->bindParam(':image_dir', $image_dir, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);

        $result = $stmt->execute();

        if (!$result) {
            throw new \Exception('could not save the product');
        }

        // 保存が正常に出来たら一覧ページへリダイレクトする
        return $response->withRedirect("/product");
    }

}