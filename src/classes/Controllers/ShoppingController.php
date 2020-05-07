<?php


namespace Classes\Controllers;


use Slim\Http\Request;
use Slim\Http\Response;

class ShoppingController extends Controller
{
    /**
     * 買い物サイトトップ
     * @param Request $request
     * @param Response $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(Request $request, Response $response)
    {
        return $this->renderer->render($response, '/shopping/index.phtml');
    }
}