<?php


namespace Classes\Controllers;


use Classes\Models\Products;
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
        $product = new Products($this->db);
        // 商品一覧を取得
        $items = $product->getProducts();
        // News一覧を取得
        $news = $product->getNews();

        $data = [
            'products' => $items
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
    public function show(Request $request, Response $response, array $args)
    {
        $product = new Products($this->db);
        $items = $product->getProductsOfId($args);

        // 商品が存在しない場合はエラー
        if (!$items) {
            throw new NotFoundException($request, $response);
        }

        $data = ['product' => $items];
        return $this->renderer->render($response, '/shopping/item.phtml', $data);
    }
}