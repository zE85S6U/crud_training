<?php


namespace Classes\Controllers;


use Classes\Models\Products;
use Exception;
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
        $product = new Products($this->db);
        $products = $product->getProducts();
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
        $product = new Products($this->db);

        // フォームに入力された商品情報
        $item = $request->getParsedBody();

        // 画像ファイルをサーバにアップロード
        $image = $this->imgUpload();

        // 画像が選択されなかった場合の画像URLはデフォルト値
        $image = $image ?? 'default.jpg';
        $item['image'] = $image;

        // 商品をデータベースに追加
        $product->insertProducts($item);

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
        $product = new Products($this->db);

        // IDから商品情報を取得
        $item = $product->getProductsOfId($args);

        $data = ['product' => $item];

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
        $product = new Products($this->db);

        // IDから商品情報を取得
        $item = $product->getProductsOfId($args);

        // 更新前の商品を取得
        $item['product_name'] = trim($request->getParsedBodyParam('product_name'));
        $item['price'] = $request->getParsedBodyParam('price');
        $item['stock'] = $request->getParsedBodyParam('stock');
        $item['image_dir'] = $image ?? $item['image_dir'];
        $item['description'] = trim($request->getParsedBodyParam('description'));
        $item['nickname'] = trim($request->getParsedBodyParam('nickname'));

        // 商品情報を更新
        $product->updateProducts($item);

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
        $product = new Products($this->db);

        // IDから商品を削除
        $product->deleteProduct($args);

        return $response->withRedirect("/product");
    }

    /**
     * 画像をサーバに保存
     * @return bool|string|null 画像ファイル名
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

            // アップロードされtがファイルの名前
            $tmpName = $_FILES['image_dir']['tmp_name'];

            // 最大幅
            $maxWidth = 640;
            // 最大高
            $maxHeight = 480;

            // 元画像リソースを作成
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

            // 元画像の高さと幅を取得
            list($srcWidth, $srcHeight) = getimagesize($tmpName);

            // 4:3の比率でとれる最大サイズを取得
            if ($srcWidth > $srcHeight) {
                // 幅のほうが大きい場合
                $diff = $srcHeight / $maxHeight;    // 幅と高さの比率差
                $newWidth = $maxWidth * $diff;      // 変換元となる画像の幅
                $newHeight = $srcHeight;            // 変換元なる画像の高さ
                $cutOff = $srcWidth - $newWidth;    // 切り取る長さ
                $offSetY = 0;                       // キャプチャするY起点
                $offSetX = $cutOff * 0.5;           // キャプチャするX起点
            } elseif ($srcWidth < $srcHeight) {
                // 高さのほうが大きい場合
                $diff = $srcWidth / $maxWidth;
                $newWidth = $srcWidth;
                $newHeight = $maxHeight * $diff;
                $cutOff = $srcHeight - $newHeight;
                $offSetY = $cutOff * 0.5;
                $offSetX = 0;
            } elseif ($srcWidth === $srcHeight) {
                // 横縦が同じ場合は高さが大きい場合のルールを使用
                $diff = $srcWidth / $maxWidth;
                $newWidth = $srcWidth;
                $newHeight = $maxHeight * $diff;
                $cutOff = $srcHeight - $newHeight;
                $offSetY = $cutOff * 0.5;
                $offSetX = 0;
            }

            //サムネイルになる土台の画像
            $canvas = imagecreatetruecolor($maxWidth, $maxHeight);

            // 背景色の設定
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

            // リサイズの実行
            imagecopyresampled($canvas, $srcImage, 0, 0, $offSetX, $offSetY, $maxWidth, $maxHeight, $newWidth, $newHeight);

            // ファイルネームは一意な値にする
            $fileName = uniqid(mt_rand());
            $fileName .= '.' . substr(strrchr($_FILES['image_dir']['name'], '.'), 1);
            $filePass = self::FILE_DIR . $fileName;

            // ファイルに出力する
            if ($ext == '.jpg' || $ext == '.jpeg') {
                $quality = 80;
                imagejpeg($canvas, $filePass, $quality);
            } else if ($ext == '.png') {
                imagepng($canvas, $filePass);
            } else if ($ext == '.gif') {
                imagegif($canvas, $filePass);
            }

            // 画像を破棄する
            imagedestroy($srcImage);
            imagedestroy($canvas);
        }

        return $fileName;
    }
}