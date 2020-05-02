<?php

use Classes\Controllers\ProductController;
use Classes\Controllers\ShoppingController;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
    $container = $app->getContainer();

    $app->get('/', ShoppingController::class . ':index');

    // 商品画面
    $app->get('/product', ProductController::class . ':index');
    // 商品管理画面
    $app->post('/product/store', ProductController::class . ':store');
};