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
        $sql = 'INSERT INTO m_product (product_name, price, stock, image_dir, description, nickname) '
            . 'VALUES (:product_name, :price, :stock, :image_dir, :description, :nickname)';
        $stmt = $this->db->prepare($sql);

        // 画像ファイルをサーバにアップロード
        $image = $this->imgUpload();


        // 画像が選択されなかった場合の画像URLはデフォルト値
        $image = $image ?? 'default.jpg';

        // プリペアードステートメントを安全に代入
        $product_name = trim($product['product_name']);
        $description = trim($product['description']);
        $nickname = trim($product['nickname']);
        $stmt->bindParam(':product_name', $product_name, PDO::PARAM_STR);
        $stmt->bindParam(':price', $product['price'], PDO::PARAM_INT);
        $stmt->bindParam(':stock', $product['stock'], PDO::PARAM_INT);
        $stmt->bindParam(':image_dir', $image, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':nickname', $nickname, PDO::PARAM_STR);

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
        $image = $this->imgUpload();

        // 更新前の商品を取得
        $product['product_name'] = trim($request->getParsedBodyParam('product_name'));
        $product['price'] = $request->getParsedBodyParam('price');
        $product['stock'] = $request->getParsedBodyParam('stock');
        $product['image_dir'] = $image ?? $product['image_dir'];
        $product['description'] = trim($request->getParsedBodyParam('description'));
        $product['nickname'] = trim($request->getParsedBodyParam('nickname'));

        $sql = 'UPDATE m_product SET product_name = :product_name, price = :price,
                     stock = :stock, image_dir = :image_dir, description = :description, nickname = :nickname
                     WHERE product_id = :product_id';

        $stmt = $this->db->prepare($sql);

        // プリペアードステートメントを安全に代入
        $stmt->bindParam(':product_id', $product['product_id'], PDO::PARAM_INT);
        $stmt->bindParam(':product_name', $product['product_name'], PDO::PARAM_STR);
        $stmt->bindParam(':price', $product['price'], PDO::PARAM_INT);
        $stmt->bindParam(':stock', $product['stock'], PDO::PARAM_INT);
        $stmt->bindParam(':image_dir', $product['image_dir'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $product['description'], PDO::PARAM_STR);
        $stmt->bindParam(':nickname', $product['nickname'], PDO::PARAM_STR);

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
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $id
     * @return array
     * @throws NotFoundException
     */
    private function fetchProduct(ServerRequestInterface $request, ResponseInterface $response, string $id): array
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
     * @return string 画像ファイル名
     */
    private function imgUpload()
    {
        // 画像ファイル名
        $fileName = null;

        // 画像ファイル未入力か画像設定済で変更しない場合
        if (($_FILES['image_dir']['error']) == 4) {
            return null;
        }

        // $_FILES['image_dir']['mime']の値はブラウザ側で偽装可能なので、MIMEタイプをチェックする
        $type = @exif_imagetype($_FILES['image_dir']['tmp_name']);

        // MIMEタイプがgif,jpeg,pngなら保存
        if (in_array($type, [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {

            $tmpName = $_FILES['image_dir']['tmp_name'];
            $maxWidth = 640;    // 最大幅
            $maxHeight = 480;   // 最大高さ

            // リサイズ
            if ($type == IMAGETYPE_GIF) {
                $ext = '.gif';
                $srcImage = imagecreatefromgif($tmpName);
            } elseif ($type == IMAGETYPE_JPEG) {
                $ext = '.jpeg';
                $srcImage = imagecreatefromjpeg($tmpName);
            } elseif ($type == IMAGETYPE_PNG) {
                $ext = '.png';
                $srcImage = imagecreatefrompng($tmpName);
            } else {
                return false;
            }


            list($srcWidth, $srcHeight) = getimagesize($tmpName);

            // 元画像の縦横の大きさを比べてどちらかにあわせる
            if ($srcWidth > $srcHeight) {
                $diff = $srcHeight / $maxHeight;
                $newWidth = $maxWidth * $diff;
                $newHeight = $srcHeight;
                $cutOff = $srcWidth - $newWidth;
                $offSetY = 0;
                $offSetX = $cutOff * 0.5;
            } elseif ($srcWidth < $srcHeight) {
                $diff = $srcWidth / $maxWidth;
                $newWidth = $srcWidth;
                $newHeight = $maxHeight * $diff;
                $cutOff = $srcHeight - $newHeight;
                $offSetY = $cutOff * 0.5;
                $offSetX = 0;
            } elseif ($srcWidth === $srcHeight) {
                $diff = $srcWidth / $maxWidth;
                $newWidth = $srcWidth;
                $newHeight = $maxHeight * $diff;
                $cutOff = $srcHeight - $newHeight;
                $offSetY = $cutOff * 0.5;
                $offSetX = 0;
            }

            //サムネイルになる土台の画像
            $canvas = imagecreatetruecolor($maxWidth, $maxHeight);

            if ($ext == '.gif') {
                $transparent1 = imagecolortransparent($srcImage);
                if ($transparent1 >= 0) {
                    $index = imagecolorsforindex($srcImage, $transparent1);
                    $transparent2 = imagecolorallocate($canvas, $index['red'], $index['green'], $index['blue']);
                    imagefill($canvas, 0, 0, $transparent2);
                    imagecolortransparent($canvas, $transparent2);
                }
            } elseif ($ext == '.png') {
                imagealphablending($canvas, false);
                $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefill($canvas, 0, 0, $transparent);
                imagesavealpha($canvas, true);
            }

            imagecopyresampled($canvas, $srcImage, 0, 0, $offSetX, $offSetY, $maxWidth, $maxHeight, $newWidth, $newHeight);


            $fileName = uniqid(mt_rand());
            $fileName .= '.' . substr(strrchr($_FILES['image_dir']['name'], '.'), 1);
            $filePass = self::FILE_DIR . $fileName;

            if ($ext == '.jpg' || $ext == '.jpeg') {
                $quality = 80;
                imagejpeg($canvas, $filePass, $quality);
            } else if ($ext == '.png') {
                imagepng($canvas, $filePass);
            } else if ($ext == '.gif') {
                imagegif($canvas, $filePass);
            }

            imagedestroy($srcImage);
            imagedestroy($canvas);

        }

        return $fileName;
    }
}