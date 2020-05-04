<?php


namespace Classes\Controllers;


use PDO;
use Slim\Http\Request;
use Slim\Http\Response;

class ProductController extends Controller
{
    /**
     * 商品一覧
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
        return $this->renderer->render($response, '/product/index.phtml', $data);
    }

    /**
     * 新規商品追加用フォームの表示
     * @param Request $request
     * @param Response $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function create(Request $request, Response $response)
    {
        return $this->renderer->render($response, 'product/create.phtml');
    }

    /**
     * 新規商品追加
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws \Exception
     */
    public function store(Request $request, Response $response)
    {
        // postされたデータを変数に代入
        $product = $request->getParsedBody();

        $sql = 'INSERT INTO m_product (product_name, price, stock, image_dir, description) '
            . 'VALUES (:product_name, :price, :stock, :image_dir, :description)';

        $stmt = $this->db->prepare($sql);

        // プリペアードステートメントを安全に代入
        $stmt->bindParam(':product_name', $product['product_name'], PDO::PARAM_STR);
        $stmt->bindParam(':price', $product['price'], PDO::PARAM_INT);
        $stmt->bindParam(':stock', $product['stock'], PDO::PARAM_INT);
        $stmt->bindParam(':image_dir', $product['image_dir'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $product['description'], PDO::PARAM_STR);

        $result = $stmt->execute();

        if (!$result) {
            throw new \Exception
            ('could not save the product');
        }

        // 保存が正常に出来たら一覧ページへリダイレクトする
        return $response->withRedirect("/product");
    }

    /**
     * 商品の編集画面
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function edit(Request $request, Response $response, array $args)
    {
        $sql = 'SELECT * FROM m_product WHERE product_id = :id';
        $stmt = $this->db->prepare($sql);
        $id = (int)$args['id'];
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch();
        $data = ['product' => $product];
        return $this->renderer->render($response, '/product/edit.phtml', $data);
    }

    /**
     * 商品の更新
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function update(Request $request, Response $response, array $args)
    {
        try {
            $product = $this->fetchProduct($args['id']);
        } catch (\Exception $e) {
            return $response->withStatus(404)->write($e->getMessage());
        }
        $product['product_name'] = $request->getParsedBodyParam('product_name');
        $product['price'] = $request->getParsedBodyParam('price');
        $product['stock'] = $request->getParsedBodyParam('stock');
        $product['image_dir'] = $request->getParsedBodyParam('image_dir');
        $product['description'] = $request->getParsedBodyParam('description');

        $stmt = $this->db->prepare('UPDATE m_product SET product_name = :product_name, 
                     price = :price, stock = :stock, image_dir = :image_dir, description = :description
                     WHERE product_id = :product_id');
        $stmt->execute($product);
        return $response->withRedirect("/product");
    }

    /**
     * 商品の削除
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function delete(Request $request, Response $response, array $args)
    {
        try {
            $product = $this->fetchProduct($args['id']);
        } catch (\Exception $e) {
            return $response->withStatus(404)->write($e->getMessage());
        }
        $stmt = $this->db->prepare('DELETE FROM m_product WHERE product_id = :id');
        $stmt->execute(['id' => $product['id']]);
        return $response->withRedirect("/product");
    }

    /**
     * @param $id
     * @return array
     * @throws \Exception
     */
    private function fetchProduct($id): array
    {
        $sql = 'SELECT * FROM m_product WHERE product_id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $ticket = $stmt->fetch();
        if (!$ticket) {
            throw new \Exception('not found');
        }
        return $ticket;
    }
}