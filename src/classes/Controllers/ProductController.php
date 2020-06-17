<?php


namespace Classes\Controllers;


use Exception;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\NotFoundException;
use Slim\Exception\SlimException;
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
        if ($_FILES['image_dir']['error'] != 4) {
            $image = $this->imgUpload($request, $response);
        } else {
            // 登録画像違反
            throw new SlimException($request, $response);
        }

        // 画像が選択されなかった場合の画像URLはデフォルト値
        $image = $image ?? 'default.jpg';

        // プリペアードステートメントを安全に代入
        $stmt->bindParam(':product_name', $product['product_name'], PDO::PARAM_STR);
        $stmt->bindParam(':price', $product['price'], PDO::PARAM_INT);
        $stmt->bindParam(':stock', $product['stock'], PDO::PARAM_INT);
        $stmt->bindParam(':image_dir', $image, PDO::PARAM_STR);
        $stmt->bindParam(':description', $product['description'], PDO::PARAM_STR);

        $result = $stmt->execute();
        if (!$result) throw new SlimException($request, $response);

        // 保存が正常に出来たら一覧ページへリダイレクトする
        return $response->withRedirect("/product");
    }

    /**
     * 商品詳細をIDで取得
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface
     * @throws NotFoundException
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
        if (!$product) throw new NotFoundException($request, $response);

        $data = ['product' => $product];
        return $this->renderer->render($response, '/product/edit.phtml', $data);
    }

    /**
     * 商品の更新
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $product = $this->fetchProduct($request, $response, $args['id']);
        // 存在しない商品番号にアクセスした場合
        if (!$product) throw new NotFoundException($request, $response);

        // 画像ファイルをサーバにアップロード
        if ($_FILES['image_dir']['error'] != 4) {
            $image = $this->imgUpload($request, $response);
        } else {
            // 登録画像違反
            throw new SlimException($request, $response);
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
     * @throws Exception
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $product = $this->fetchProduct($request, $response, $args['id']);
        // 存在しない商品番号にアクセスした場合
        if (!$product) throw new NotFoundException($request, $response);

        $stmt = $this->db->prepare('DELETE FROM m_product WHERE product_id = :id');
        $stmt->execute(['id' => $product['product_id']]);
        return $response->withRedirect("/product");
    }

    /**
     * 商品をIDで検索
     * @param Request $request
     * @param Response $response
     * @param array $id
     * @return array
     * @throws NotFoundException
     */
    private function fetchProduct(Request $request, Response $response, array $id): array
    {
        $sql = 'SELECT * FROM m_product WHERE product_id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();

        // 存在しない商品番号にアクセスした場合
        if (!$product) throw new NotFoundException($request, $response);

        return $product;
    }

    /**
     * 画像をサーバに保存
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return string 画像ファイル名
     * @throws SlimException
     */
    private function imgUpload(ServerRequestInterface $request, ResponseInterface $response)
    {
        // ファイル名は一意性のある名前にする
        $fileName = uniqid(mt_rand());
        $fileName .= '.' . substr(strrchr($_FILES['image_dir']['name'], '.'), 1);
        $filePass = self::FILE_DIR . $fileName;

        // $_FILES['image_dir']['mime']の値はブラウザ側で偽装可能なので、MIMEタイプを自前でチェックする
        $type = @exif_imagetype($_FILES['image_dir']['tmp_name']);
        if (!in_array($type, [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            throw new SlimException($request, $response);
        }

        // 画像をtempからサーバに保存
        if (!empty($_FILES['image_dir']['name'])) {
            move_uploaded_file($_FILES['image_dir']['tmp_name'], $filePass);
        }

        return $fileName;
    }
}