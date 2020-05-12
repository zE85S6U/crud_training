<?php


namespace Classes\Controllers;


use Exception;
use Slim\Http\Request;
use Slim\Http\Response;
use function PHPUnit\Framework\isEmpty;

class CartController extends Controller
{
    /**
     * カート内一覧を表示する
     * @param Request $request
     * @param Response $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(Request $request, Response $response)
    {
        return $this->renderer->render($response, '/cart/index.phtml');
    }

    /**
     * カートに商品を追加する
     * @param Request $request
     * @param Response $response
     * @return \Psr\Http\Message\ResponseInterface|Response
     */
    public function insert(Request $request, Response $response)
    {
        $toCart = $this->trimPostCartData($request->getParsedBody());

        // カートに商品が入っていない場合は商品を追加して表示する
        if (empty($_SESSION['cart'])) {
            $_SESSION['cart'][] = $toCart;
            $this->minerTotal();
            $this->total();
            return $response->withRedirect('/cart');
        }

        // 追加済の場合は個数を追記する
        $index = array_search($toCart['product_id'], array_column($_SESSION['cart'], 'product_id'));
        if ($index === false) {
            $_SESSION['cart'][] = $toCart;
        } else {
            $_SESSION['cart'][$index]['order_quantity'] += $toCart['order_quantity'];
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
    private function minerTotal()
    {
        foreach ($_SESSION['cart'] as $index => $item) {
            $_SESSION['cart'][$index]['miner_total'] =
                $item['order_quantity'] * $item['price'];
        }
    }

    private function total()
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
    function update(Request $request, Response $response)
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
    public function delete(Request $request, Response $response, array $args)
    {
        array_splice($_SESSION['cart'], $args['id'], 1);
        $this->total();

        return $response->withRedirect('/cart');
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