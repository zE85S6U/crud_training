<?php


namespace Classes\Controllers;


use Slim\Http\Request;
use Slim\Http\Response;

class TestController extends Controller
{
    public function index(Request $request, Response $response)
    {
        return $this->renderer->render($response, 'index.phtml');
    }
}