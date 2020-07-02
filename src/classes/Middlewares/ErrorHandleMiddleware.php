<?php


namespace Classes\Middlewares;


use Slim\Http\Request;
use Slim\Http\Response;

class ErrorHandleMiddleware
{
    public function __invoke(Request $request, Response $response, $next)
    {
        $response->getBody()->write('BEFORE');
        $response = $next($request, $response);
        $response->getBody()->write('AFTER');

        $body = $request->getParsedBody();

        return $response;
    }
}