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
     * @return Response
     */
    public function store(Request $request, Response $response): Response
    {
        $loginid = e(trim($request->getParsedBodyParam('login_id')));

        $password = trim($request->getParsedBodyParam('password'));
        if ($this->verifyPassword($password)) {
            $password = password_hash($password, PASSWORD_DEFAULT);
        } else {
            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'text/html')
                ->write(' パスワードは半角英数字記号をそれぞれ1種類以上含む8文字以上100文字以下で設定してください。');
        }
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
                ->write('ログインできません　ログイン名かパスワードを確認してください。');
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