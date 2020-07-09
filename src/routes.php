<?php

use Classes\Controllers\CartController;
use Classes\Controllers\OrderController;
use Classes\Controllers\ProductController;
use Classes\Controllers\ShoppingController;
use Classes\Controllers\UserController;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
    $container = $app->getContainer();

    // 買い物サイトトップ
    $app->get('/', ShoppingController::class . ':index');

    // 商品詳細
    $app->get('/item/{id}', ShoppingController::class . ':show');

    // 新規登録画面へ
    $app->get('/signup', UserController::class . ':index');

    // 新規登録
    $app->post('/signup', UserController::class . ':store');

    // ログインページへ
    $app->get('/login', UserController::class . ':show');

    // 管理者ログインページへ
    $app->get('/login/admin', UserController::class . ':show_admin');

    // ログイン
    $app->post('/login', UserController::class . ':login');

    // 管理者ログイン
    $app->post('/login/admin', UserController::class . ':login_admin');

    // ログアウト
    $app->get('/logout', UserController::class . ':logout');

    // 登録者情報
    $app->get('/user/profile', UserController::class . ':profile');

    // 登録者情報更新
    $app->post('/user', UserController::class . ':update');

    // 登録者情報削除
    $app->delete('/user', UserController::class . ':delete');

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

    // お届けページへ
    $app->get('/order/delivery', OrderController::class . ':greet');

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