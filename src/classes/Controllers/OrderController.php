<?php


namespace Classes\Controllers;


use Classes\Model\Orders;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class OrderController extends Controller
{
    /**
     * 購入確認画面
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function index(Request $request, Response $response): ResponseInterface
    {
        return $this->renderer->render($response, '/order/index.phtml');
    }

    /**
     * 購入確定
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     * @throws Exception
     */
    public function submit(Request $request, Response $response): ResponseInterface
    {
        $order = new Orders($this->db);

        // 注文したユーザと日付を登録
        $order_id = $order->insertOrder($request, $response);
        // 購入商品をデータベースに登録
        $order->insertOrderDetails($request, $response, $order_id);

        // カートをリセット
        $this->resetSession();

        // お届けページへ
        return $response->withRedirect("/order/delivery");
    }

    /**
     * お届けページ
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function greet(Request $request, Response $response): ResponseInterface
    {
        return $this->renderer->render($response, '/order/delivery.phtml');
    }

    /**
     * 注文が完了したらカートをリセットする
     */
    private function resetSession(): void
    {
        unset($_SESSION['cart']);
        unset($_SESSION['carts']);
    }
}