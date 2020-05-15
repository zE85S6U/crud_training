<?php


namespace Classes\Controllers;


use Exception;
use PDO;
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
     *
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     * @throws Exception
     */
    public function submit(Request $request, Response $response)
    {
        // 購入商品をデータベースに登録
        $order_id = $this->insertOrder();
        $this->insertOrderDetails($order_id);

        // カートをリセット
        $this->resetSession();

        // TODO 購入完了画面へ遷移する
        return $this->renderer->render($response, '/order/index.phtml');
    }

    /**
     * 注文が完了したらカートをリセットする
     */
    private function resetSession(): void
    {
        unset($_SESSION['cart']);
        unset($_SESSION['carts']);
    }

    /**
     * 注文したユーザと日付を登録
     * @return int
     * @throws Exception
     */
    private function insertOrder(): int
    {
        $sql = 'INSERT INTO d_order(user_id, order_date) '
            . 'VALUES (:user_id, :order_date)';
        $stmt = $this->db->prepare($sql);

        // ユーザIDを取得
        $user_id = $_SESSION['user']['user_id'];

        // 現在時間を取得
        date_default_timezone_set('Asia/Tokyo');
        $date = date("Y/m/d H:i:s");

        // プリペアードステートメントを安全に代入
        // 仮のユーザID
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':order_date', $date, PDO::PARAM_STR);

        $result = $stmt->execute();
        $order_id = (int)$this->db->lastInsertId();

        if (!$result) {
            throw new Exception
            ('could not save the product');
        }
        return $order_id;
    }

    /**
     * 注文ごとの詳細を登録
     * @param $order_id
     * @throws Exception
     */
    private function insertOrderDetails($order_id): void
    {
        $sql = 'INSERT INTO d_order_details(order_id, product_id, order_quantity, miner_total) '
            . 'VALUES (:order_id, :product_id, :order_quantity, :miner_total)';
        $stmt = $this->db->prepare($sql);


        // プリペアードステートメントを安全に代入
        foreach ($_SESSION['cart'] as $item) {
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
            $stmt->bindParam(':order_quantity', $item['order_quantity'], PDO::PARAM_INT);
            $stmt->bindParam(':miner_total', $item['miner_total'], PDO::PARAM_INT);

            $result = $stmt->execute();

            // 在庫を再計算する
            $this->reCalculateStock(['product_id' => $item['product_id'], 'order_quantity' => $item['order_quantity']]);
            if (!$result) {
                throw new Exception
                ('could not save the product');
            }
        }
    }

    /**
     * 在庫の再計算をする
     * @param $products
     */
    private function reCalculateStock($products): void
    {
        // 現在のテーブルを取得
        $sql = 'SELECT stock FROM m_product WHERE product_id = :product_id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $products['product_id'], PDO::PARAM_INT);
        $stmt->execute();
        $stock = $stmt->fetch();
        $stmt = null;

        // 在庫カラムの更新
        $sql = ('UPDATE m_product SET stock = :stock WHERE product_id = :product_id');
        $stmt = $this->db->prepare($sql);
        $resultStock = $stock['stock'] - (int)$products['order_quantity'];
        // プリペアードステートメントを安全に代入
        $stmt->bindParam(':stock', $resultStock, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $products['product_id'], PDO::PARAM_INT);

        $stmt->execute();
    }
}