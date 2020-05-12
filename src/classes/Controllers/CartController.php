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
        return $this->renderer->render($response, '/order/cart.phtml');
    }

    /**
     * カートに商品を追加する
     * @param Request $request
     * @param Response $response
     * @return \Psr\Http\Message\ResponseInterface|Response
     */
    public function insert(Request $request, Response $response)
    {
        $toCart = $request->getParsedBody();

        if (empty($_SESSION['cart'])) {
            $_SESSION['cart'][] = $toCart;
            return $this->renderer->render($response, '/order/cart.phtml');
        }

        $index = array_search($toCart['product_id'], array_column($_SESSION['cart'], 'product_id'));
        if ($index === false) {
            $_SESSION['cart'][] = $toCart;
        } else {
            $_SESSION['cart'][$index]['order_quantity'] += $toCart['order_quantity'];
        }

        $_SESSION['token'] = md5("token");

        return $response->withRedirect("/order/cart");
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
        return $response->withRedirect("/order/cart");
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