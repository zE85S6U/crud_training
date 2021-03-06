<?php


namespace Classes\Models;


use PDO;

class Users
{
    protected $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * ユーザ情報を取得する
     * @param $loginId
     * @return mixed
     */
    public function getUser($loginId)
    {
        $sql = 'SELECT * FROM m_user WHERE login_id = :login_id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':login_id', $loginId, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * 購入履歴を取得する
     * @param $userId
     * @return array
     */
    public function getPurchaseHistory($userId) {
        $sql = 'SELECT CAST(order_date as date), product_name, price, order_quantity, image_dir
                FROM d_order d
                    INNER JOIN d_order_details dd on d.order_id = dd.order_id
                    INNER JOIN m_product mp on dd.product_id = mp.product_id
                WHERE user_id = :user_id ORDER BY order_date DESC';
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();

        return  $stmt->fetchAll();
    }
    /**
     * ログインチェックする
     * @param $user
     * @param $password
     * @return array|string[]
     */
    public function validate($user, $password)
    {
        $error = [];

        if (!$user) {
            // ユーザ情報が取得できない
            $error = [
                'auth_error' => 'ログインできません　ログイン情報をお確かめ下さい。'
            ];
        } else if (!password_verify($password, $user['password'])) {
            // パスワードの不一致
            $error = [
                'auth_error' => 'ログインできません　ログイン情報をお確かめ下さい。'
            ];
        }

        return $error;
    }
}