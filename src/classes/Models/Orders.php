<?php


namespace Classes\Models;


use PDO;
use Slim\Exception\SlimException;
use Slim\Http\Request;
use Slim\Http\Response;

class Orders
{
    protected $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * 注文したユーザと日付を登録
     * @param Request $request
     * @param Response $response
     * @return int
     * @throws SlimException
     */
    public function insertOrder(Request $request, Response $response): int
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
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':order_date', $date, PDO::PARAM_STR);
        $stmt->execute();

        // 戻り値は注文番号
        return (int)$this->db->lastInsertId();;
    }

    /**
     * 注文ごとの詳細を登録
     * @param Request $request
     * @param Response $response
     * @param $order_id
     * @throws SlimException
     */
    public function insertOrderDetails(Request $request, Response $response, $order_id): void
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
            if (!$result) {
                throw new SlimException($request,$response);
            }

            // 在庫を再計算する
            $orderItem = [                               // 購入された商品IDと個数の配列
                'product_id' => $item['product_id'],
                'order_quantity' => $item['order_quantity']
            ];

            $this->reCalculateStock($orderItem);
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