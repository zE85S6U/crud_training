<?php


namespace Classes\Controllers;


use Exception;
use PDO;
use Slim\Http\Request;
use Slim\Http\Response;

class UserController extends Controller
{
    // 新規登録画面
    public function index(Request $request, Response $response) {
        return $this->renderer->render($response, '/user/signup.phtml');
    }

    // ユーザを登録
    public function store(Request $request, Response $response) {
        $loginid = e($request->getParsedBodyParam('login_id'));
        $password = password_hash($request->getParsedBodyParam('password'), PASSWORD_DEFAULT );

        $sql = 'INSERT INTO m_user (login_id, password) VALUES (:login_id, :password)';
        $stmt = $this->db->prepare($sql);

        // プリペアードステートメントを安全に代入
        $stmt->bindParam(':login_id', $loginid, PDO::PARAM_STR);
        $stmt->bindParam(':password',$password , PDO::PARAM_STR);
        $result = $stmt->execute();

        if (!$result) {
            throw new Exception
            ('could not save the product');
        }

        // 保存が正常に出来たらTOPページへリダイレクトする
        return $response->withRedirect("/");
    }

    // ログインページへ
    public function show(Request $request, Response $response) {
        return $this->renderer->render($response, '/user/login.phtml');
    }

    // ログイン
    public function login(Request $request, Response $response) {
        $loginid = e($request->getParsedBodyParam('login_id'));
        $password = $request->getParsedBodyParam('password');

        $sql = 'SELECT * FROM m_user WHERE login_id = :login_id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':login_id', $loginid, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user']['user_id'] = (int)$user['user_id'];
            $_SESSION['user']['login_id']= $user['login_id'];
            // 正常に認証出来たらTOPページへリダイレクトする
            return $response->withRedirect("/");
        } else {
        echo 'ログインできません　ログイン名かパスワードを確認してください。';
        }
    }

    // ログアウト
    public function logout(Request $request, Response $response) {
        unset($_SESSION['user']);
        return $response->withRedirect("/");
    }
}