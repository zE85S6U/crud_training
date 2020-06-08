<?php


namespace Classes\Controllers;


use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class CartController extends Controller
{

    /**
     * カート画面
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function index(Request $request, Response $response): ResponseInterface
    {
        return $this->renderer->render($response, '/cart/index.phtml');
    }

    /**
     * カートに商品を追加する
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function insert(Request $request, Response $response): Response
    {
        $toCart = $this->trimPostCartData($request->getParsedBody());

        // カートに商品が入っていない場合は商品を追加して表示する
        if (empty($_SESSION['cart'])) {
            $_SESSION['cart'][] = $toCart;
            $this->minerTotal();
            $this->total();
            return $response->withRedirect('/cart');
        }

        // 追加済の場合は個数を追加する
        $index = array_search($toCart['product_id'], array_column($_SESSION['cart'], 'product_id'));
        if ($index === false) {
            $_SESSION['cart'][] = $toCart;
        } else {
            // 注文個数を追記
            $preorder = $_SESSION['cart'][$index]['order_quantity'] + $toCart['order_quantity'];
            // 追加注文が在庫を上回る場合は追加しない
            $_SESSION['cart'][$index]['order_quantity'] =
                $preorder > $_SESSION['cart'][$index]['stock'] ? $_SESSION['cart'][$index]['order_quantity'] : $preorder;
        }

        $this->minerTotal();
        $this->total();
        return $response->withRedirect('/cart');
    }

    /**
     * フォームから送信された
     * データを表示用に整える
     * @param $cartData
     * @return array
     */
    private function trimPostCartData($cartData): array
    {
        $cartData['order_quantity'] = (int)$cartData['order_quantity'];
        $cartData['product_id'] = (int)$cartData['product_id'];
        $cartData['price'] = (int)$cartData['price'];
        $cartData['stock'] = (int)$cartData['stock'];

        return $cartData;
    }

    /**
     * 小計を計算
     */
    private function minerTotal(): void
    {
        foreach ($_SESSION['cart'] as $index => $item) {
            $_SESSION['cart'][$index]['miner_total'] =
                $item['order_quantity'] * $item['price'];
        }
    }

    /**
     * 合計を計算
     */
    private function total(): void
    {
        $sumArr = array_column($_SESSION['cart'], 'miner_total');
        $sum = array_sum($sumArr);

        $_SESSION['carts']['total'] = $sum;
    }

    /**
     * 注文個数を更新
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    function update(Request $request, Response $response): Response
    {
        $index = $request->getParsedBodyParam('index');
        $quantity = $request->getParsedBodyParam('order_quantity');
        $_SESSION['cart'][$index]['order_quantity'] = $quantity;

        $this->minerTotal();
        $this->total();
        return $response->withRedirect('/cart');
    }


    /**
     * カードから商品を削除する
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        array_splice($_SESSION['cart'], $args['id'], 1);
        $this->total();

        return $response->withRedirect('/cart');
    }

}