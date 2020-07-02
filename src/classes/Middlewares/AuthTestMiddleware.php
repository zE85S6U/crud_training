<?php


namespace Classes\Middlewares;


use Slim\Http\Request;
use Slim\Http\Response;

class AuthTestMiddleware
{
    public function __invoke(Request $request, Response $response, $next)
    {
        $response->getBody();
         $at = $request->getParsedBody();



        return $response;
    }
}