<?php


namespace Classes\Controllers;


use Exception;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\NotFoundException;
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
     * @return Response
     */
    public function store(Request $request, Response $response): Response
    {
        $loginid = e(trim($request->getParsedBodyParam('login_id')));

        // パスワードの検証
        $password = trim($request->getParsedBodyParam('password'));
        if ($this->verifyPassword($password)) {
            $password = password_hash($password, PASSWORD_DEFAULT);
        } else {
            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'text/html')
                ->write(' パスワードは半角英数字記号をそれぞれ1種類以上含む8文字以上100文字以下で設定してください。');
        }

        // SQLを組み立て
        $sql = 'INSERT INTO m_user (login_id, password) VALUES (:login_id, :password)';
        $stmt = $this->db->prepare($sql);

        // プリペアードステートメントを安全に代入
        try {
            $stmt->bindParam(':login_id', $loginid, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->execute();
        } catch (Exception $e) {
            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'text/html')
                ->write('問題が発生しました:別の名前を使用してください');
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
     * ログイン
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function login(Request $request, Response $response): Response
    {
        $loginid = e($request->getParsedBodyParam('login_id'));
        $password = e($request->getParsedBodyParam('password'));

        $sql = 'SELECT * FROM m_user WHERE login_id = :login_id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':login_id', $loginid, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            return $response
                ->withStatus(403)
                ->withHeader('Content-Type', 'text/html')
                ->write('ログインできません　ログイン情報をお確かめ下さい。');
        }

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user']['user_id'] = (int)$user['user_id'];
            $_SESSION['user']['login_id'] = $user['login_id'];
            $_SESSION['user']['auth'] = $user['auth'];
        }

        // 正常に認証出来たらTOPページへリダイレクトする
        return $response->withRedirect("/");
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
     * @return Response
     */
    public function update(Request $request, Response $response): Response
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
            if ($password == $user['password']) {
                $sql = 'UPDATE m_user SET login_id = :login_id
                     WHERE user_id = :user_id';

                $stmt = $this->db->prepare($sql);

                try {
                    $stmt->bindParam(':login_id', $loginId, PDO::PARAM_STR);
                    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

                    $stmt->execute();
                } catch (Exception $e) {
                    return $response
                        ->withStatus(500)
                        ->withHeader('Content-Type', 'text/html')
                        ->write('問題が発生しました:別の名前を使用してください');
                }
            } else {
                $password = password_hash($password, PASSWORD_DEFAULT);

                $sql = 'UPDATE m_user SET login_id = :login_id, password = :password
                     WHERE user_id = :user_id';

                $stmt = $this->db->prepare($sql);

                // プリペアードステートメントを安全に代入
                try {
                    $stmt->bindParam(':login_id', $loginid, PDO::PARAM_STR);
                    $stmt->bindParam(':password', $password, PDO::PARAM_STR);
                    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

                    $stmt->execute();
                } catch (Exception $e) {
                    return $response
                        ->withStatus(500)
                        ->withHeader('Content-Type', 'text/html')
                        ->write('問題が発生しました:別の名前を使用してください');
                }
            }
        } else {
            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'text/html')
                ->write(' パスワードは半角英数字記号をそれぞれ1種類以上含む8文字以上100文字以下で設定してください。');
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