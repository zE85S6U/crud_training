<?php

use Classes\Controllers\ProductController;
use Classes\Controllers\ShoppingController;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
    $container = $app->getContainer();

    $app->get('/', ShoppingController::class . ':index');

    // 商品一覧画面
    $app->get('/product', ProductController::class . ':index');

    // 新規作成用フォームの表示
    $app->get('/product/create', ProductController::class . ':create');

    // 新規商品追加
    $app->post('/product', ProductController::class . ':store');

    // 商品更新画面
    $app->get('/product/{id}', ProductController::class . ':edit');

    //　商品更新
    $app->post('/product/{id}', ProductController::class . ':update');

    // 削除
    $app->delete('/product/{id}', ProductController::class . ':delete');
};