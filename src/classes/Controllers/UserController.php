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
        $loginid = e($request->getParsedBodyParam('login_id'));
        $password = password_hash($request->getParsedBodyParam('password'), PASSWORD_DEFAULT);

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
                ->write('問題が発生しました:エラーコード[' . $e->getCode() . ']');
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
        $password = $request->getParsedBodyParam('password');

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
}