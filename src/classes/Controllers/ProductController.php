<?php


namespace Classes\Controllers;


use Exception;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class ProductController extends Controller
{
    // 商品画像の保存ディレクトリ
    const FILE_DIR = __DIR__ . '/../../../public/image/file/';

    /**
     * 商品一覧
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function index(Request $request, Response $response): ResponseInterface
    {
        $sql = 'SELECT * FROM m_product ORDER BY product_id';
        $stmt = $this->db->query($sql);
        $products = $stmt->fetchAll();
        $data = ['products' => $products];
        return $this->renderer->render($response, '/product/index.phtml', $data);
    }

    /**
     * 新規商品追加用フォームの表示
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function show(Request $request, Response $response): ResponseInterface
    {
        return $this->renderer->render($response, 'product/create.phtml');
    }

    /**
     * 新規商品追加
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws Exception
     */
    public function store(Request $request, Response $response): Response
    {
        // postされたデータを変数に代入
        $product = $request->getParsedBody();
        $sql = 'INSERT INTO m_product (product_name, price, stock, image_dir, description) '
            . 'VALUES (:product_name, :price, :stock, :image_dir, :description)';
        $stmt = $this->db->prepare($sql);

        // 画像ファイルをサーバにアップロード
        $image = $this->imgUpload();

        // プリペアードステートメントを安全に代入
        $stmt->bindParam(':product_name', $product['product_name'], PDO::PARAM_STR);
        $stmt->bindParam(':price', $product['price'], PDO::PARAM_INT);
        $stmt->bindParam(':stock', $product['stock'], PDO::PARAM_INT);
        $stmt->bindParam(':image_dir', $image, PDO::PARAM_STR);
        $stmt->bindParam(':description', $product['description'], PDO::PARAM_STR);

        $result = $stmt->execute();

        if (!$result) {
            throw new Exception
            ('商品登録に失敗しました');
        }

        // 保存が正常に出来たら一覧ページへリダイレクトする
        return $response->withRedirect("/product");
    }

    /**
     * 商品詳細をIDで取得
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface
     */
    public function edit(Request $request, Response $response, array $args): ResponseInterface
    {
        $sql = 'SELECT * FROM m_product WHERE product_id = :id';
        $stmt = $this->db->prepare($sql);
        $id = (int)$args['id'];
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch();

        // 存在しない商品番号にアクセスした場合
        if (!$product) {
            return $response->withStatus(404)->write('not found');
        }

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
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $product = $this->fetchProduct($args['id']);
        } catch (Exception $e) {
            return $response->withStatus(404)
                ->withHeader('Content-Type', 'text/html')
                ->write($e->getMessage());
        }

        // 画像ファイルをサーバにアップロード
        if (!empty($_FILES['image_dir']['name'])) {
            $image = $this->imgUpload();
        }

        // 更新前の商品を取得
        $product['product_name'] = $request->getParsedBodyParam('product_name');
        $product['price'] = $request->getParsedBodyParam('price');
        $product['stock'] = $request->getParsedBodyParam('stock');
        $product['image_dir'] = $image ?? $product['image_dir'];
        $product['description'] = $request->getParsedBodyParam('description');

        $stmt = $this->db->prepare('UPDATE m_product SET product_name = :product_name, 
                     price = :price, stock = :stock, image_dir = :image_dir, description = :description
                     WHERE product_id = :product_id');

        // プリペアードステートメントを安全に代入
        $stmt->bindParam(':product_id', $product['product_id'], PDO::PARAM_INT);
        $stmt->bindParam(':product_name', $product['product_name'], PDO::PARAM_STR);
        $stmt->bindParam(':price', $product['price'], PDO::PARAM_INT);
        $stmt->bindParam(':stock', $product['stock'], PDO::PARAM_INT);
        $stmt->bindParam(':image_dir', $product['image_dir'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $product['description'], PDO::PARAM_STR);

        $stmt->execute();

        return $response->withRedirect("/product");
    }

    /**
     * 商品の削除
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $product = $this->fetchProduct($args['id']);
        } catch (Exception $e) {
            return $response->withStatus(404)->write($e->getMessage());
        }
        $stmt = $this->db->prepare('DELETE FROM m_product WHERE product_id = :id');
        $stmt->execute(['id' => $product['product_id']]);
        return $response->withRedirect("/product");
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

    /**
     * 画像をサーバに保存
     * @return string 画像ファイル名
     */
    private function imgUpload(): string
    {
        $image = uniqid(mt_rand());
        $image .= '.' . substr(strrchr($_FILES['image_dir']['name'], '.'), 1);
        $file = self::FILE_DIR . $image;
        if (!empty($_FILES['image_dir']['name'])) {
            move_uploaded_file($_FILES['image_dir']['tmp_name'], $file);
            if (exif_imagetype($file)) return $image;
        }
        return "";
    }
}