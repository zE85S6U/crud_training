<?php

// 文字列を安全にする
if (!function_exists('e')) {
    function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8', false);
    }
}

// 日本円表記にする
if (!function_exists('yen')) {
    function yen(string $s): string
    {
        return '￥' . number_format($s);
    }
}

// 在庫を確認する
if (!function_exists('stock_confirmation')) {
    function stock_confirmation(int $stock)
    {
        if ($stock < 1) {
            return '<p class="d-inline alert alert-danger" role="alert"><strong>SOLD OUT</strong></p>';
        } else {
            return $stock;
        }
    }
}

// 管理者のみアクセスできるページの制限
if (!function_exists('isAdmin')) {
    function isAdmin()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /');
            //以下の処理を行わない
            exit();
        } else if (!$_SESSION['user']['auth']) {
            header('Location: /');
            exit();
        }
    }
}
