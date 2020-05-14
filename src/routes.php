<?php

use Classes\Controllers\CartController;
use Classes\Controllers\OrderController;
use Classes\Controllers\ProductController;
use Classes\Controllers\ShoppingController;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
    $container = $app->getContainer();

    // 買い物サイトトップ
    $app->get('/', ShoppingController::class . ':index');

    // 商品詳細
    $app->get('/item/{id}', ShoppingController::class . ':show');

    // カートを表示する
    $app->get('/cart', CartController::class . ':index');

    // カートに商品を追加する
    $app->post('/cart', CartController::class . ':insert');

    // カートから注文個数を更新する
    $app->post('/cart/update', CartController::class . ':update');

    // カートから商品を削除する
    $app->get('/cart/{id}', CartController::class . ':delete');

    // 購入画面
    $app->get('/order', OrderController::class . ':index');

    // 購入確定
    $app->get('/order/submit', OrderController::class . ':submit');

    // 商品一覧画面
    $app->get('/product', ProductController::class . ':index');

    // 新規作成用フォームの表示
    $app->get('/product/create', ProductController::class . ':show');

    // 新規商品追加
    $app->post('/product', ProductController::class . ':store');

    // 商品更新画面
    $app->get('/product/{id}', ProductController::class . ':edit');

    // 商品更新
    $app->post('/product/{id}', ProductController::class . ':update');

    // 商品削除
    $app->delete('/product/{id}', ProductController::class . ':delete');
};