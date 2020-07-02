<?php


namespace Classes\Controllers;


use Exception;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class UserController extends Controller
{
    /**
     * 新規登録画面
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function index(Request $request, Response $response): ResponseInterface
    {
        return $this->renderer->render($response, '/user/signup.phtml');
    }

    /**
     * ユーザを登録
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function store(Request $request, Response $response): ResponseInterface
    {
        $loginId = e(trim($request->getParsedBodyParam('login_id')));

        // パスワードの検証
        $password = trim($request->getParsedBodyParam('password'));
        if ($this->verifyPassword($password)) {
            $password = password_hash($password, PASSWORD_DEFAULT);
        } else {
            $data = [
                'password_error' => 'パスワードは半角英数字記号をそれぞれ1種類以上含む8文字以上100文字以下で設定してください。'
            ];
            return $this->renderer->render($response, '/user/signup.phtml', $data);
        }

        // SQLを組み立て
        $sql = 'INSERT INTO m_user (login_id, password) VALUES (:login_id, :password)';
        $stmt = $this->db->prepare($sql);

        // プリペアードステートメントを安全に代入
        try {
            $stmt->bindParam(':login_id', $loginId, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->execute();
        } catch (Exception $e) {
            $data = [
                'login_id_error' => '問題が発生しました 別の名前を使用してください。'
            ];
            return $this->renderer->render($response, '/user/signup.phtml', $data);
        }

        // 保存が正常に出来たらTOPページへリダイレクトする
        return $response->withRedirect("/");
    }

    /**
     * ログインページへ
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function show(Request $request, Response $response): ResponseInterface
    {
        return $this->renderer->render($response, '/user/login.phtml');
    }

    /**
     * 管理者ログインページへ
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function admin(Request $request, Response $response): ResponseInterface
    {
        return $this->renderer->render($response, '/user/admin.phtml');
    }

    /**
     * ログイン
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function login(Request $request, Response $response): ResponseInterface
    {
        list($user, $password) = $this->getUser($request, $response);

        if (!$user) {
            $data = [
                'auth_error' => 'ログインできません　ログイン情報をお確かめ下さい。'
            ];
        } else if (! $user && password_verify($password, $user['password'])) {
            $data = [
                'auth_error' => 'ログインできません　ログイン情報をお確かめ下さい。'
            ];
        } else {
            $_SESSION['user']['user_id'] = (int)$user['user_id'];
            $_SESSION['user']['login_id'] = $user['login_id'];
            $_SESSION['user']['auth'] = $user['auth'];
        }

        if(isset($data)) {
            return $this->renderer->render($response, '/user/login.phtml', $data);
        }

        // 正常に認証出来たらTOPページへリダイレクトする
        return $response->withRedirect("/");
    }

    /**
     * フォームに入力されたユーザ名とパスワードから
     * ユーザ情報を選択する
     * @param Request $request
     * @param Response $response
     * @return array
     */
    private function getUser(Request $request, Response $response) :array
    {

        $loginId = e($request->getParsedBodyParam('login_id'));
        $password = e($request->getParsedBodyParam('password'));

        $sql = 'SELECT * FROM m_user WHERE login_id = :login_id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':login_id', $loginId, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();

        return [
            $user,
            $password
        ];
    }

    /**
     * ログアウト
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function logout(Request $request, Response $response): Response
    {
        unset($_SESSION['user']);
        return $response->withRedirect("/");
    }

    /**
     * ユーザ情報
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function profile(Request $request, Response $response): Response
    {
        $userId = $_SESSION['user']['user_id'];

        // ユーザ情報
        $sql = 'SELECT * FROM m_user WHERE user_id = :user_id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch();

        // クリア
        $sql = null;
        $stmt = null;

        // 購入履歴
        $sql = 'SELECT CAST(order_date as date), product_name, price, order_quantity, image_dir
                FROM d_order d
                    INNER JOIN d_order_details dd on d.order_id = dd.order_id
                    INNER JOIN m_product mp on dd.product_id = mp.product_id
                WHERE user_id = :user_id ORDER BY order_date DESC';
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        $history = $stmt->fetchAll();


        // ユーザ情報をまとめた配列
        $data = [
            'user' => $user,
            'history' => $history
        ];
        return $this->renderer->render($response, "/user/profile.phtml", $data);
    }

    /**
     * ユーザ情報更新
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function update(Request $request, Response $response): ResponseInterface
    {
        $userId = (int)e($request->getParsedBodyParam('user_id'));
        $password = e(trim($request->getParsedBodyParam('password')));
        $loginId = e(trim($request->getParsedBodyParam('login_id')));


        $sql = 'SELECT * FROM m_user WHERE user_id = :user_id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();

        // パスワードの検証
        if ($this->verifyPassword($password)) {
            $p = password_hash($password, PASSWORD_DEFAULT);
            // パスワードだけ更新
            if ($p == $user['password']) {
                $sql = 'UPDATE m_user SET login_id = :login_id
                     WHERE user_id = :user_id';

                $stmt = $this->db->prepare($sql);

                try {
                    $stmt->bindParam(':login_id', $loginId, PDO::PARAM_STR);
                    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

                    $stmt->execute();
                } catch (Exception $e) {
                    $data = [
                        'auth_error' => '問題が発生しました　別の名前を使用してください。'
                    ];
                    return $this->renderer->render($response, '/user/profile.phtml', $data);
                }
            } else {
                // ユーザ名とパスワードを更新
                $password = password_hash($password, PASSWORD_DEFAULT);

                $sql = 'UPDATE m_user SET login_id = :login_id, password = :password
                     WHERE user_id = :user_id';

                $stmt = $this->db->prepare($sql);

                // プリペアードステートメントを安全に代入
                try {
                    $stmt->bindParam(':login_id', $loginId, PDO::PARAM_STR);
                    $stmt->bindParam(':password', $password, PDO::PARAM_STR);
                    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

                    $stmt->execute();
                } catch (Exception $e) {
                    $data = [
                        'auth_error' => '問題が発生しました　別の名前を使用してください。'
                    ];
                    return $this->renderer->render($response, '/user/profile.phtml', $data);
                }
            }
        } else {
            $data = [
                'password_error' => 'パスワードは半角英数字記号をそれぞれ1種類以上含む8文字以上100文字以下で設定してください。'
            ];
            return $this->renderer->render($response, '/user/profile.phtml', $data);
        }

        // 保存が正常に出来たらTOPページへリダイレクトする
        $_SESSION['user']['login_id'] = $loginId;
        return $response->withRedirect("/");
    }

    /**
     * ユーザ情報を削除
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $userId = (int)e($request->getParsedBodyParam('user_id'));

        $sql = 'DELETE FROM m_user WHERE user_id = :user_id';
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        $stmt->execute();
        return $response->withRedirect("/logout");
    }

    /**
     * 登録されるパスワードの安全性を検証する
     * @param $password
     * @return bool
     * https://qiita.com/mpyw/items/886218e7b418dfed254b
     */
    private function verifyPassword($password): bool
    {
        $result = null;
        // 半角英数字記号をそれぞれ1種類以上含む8文字以上100文字以下
        if (preg_match('/\A(?=.*?[a-z])(?=.*?\d)(?=.*?[!-\/:-@[-`{-~])[!-~]{8,100}+\z/i', $password)) {
            $result = true;
        } else {
            $result = false;
        }
        return $result;
    }
}