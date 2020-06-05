<?php

if (!function_exists('e')) {
    function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8', false);
    }
}

//日本円表記にする
if (!function_exists('yen')) {
    function yen(string $s): string
    {
        return '￥' . number_format($s);
    }
}

//在庫を確認する
if (!function_exists('stock_confirmation')) {
    function stock_confirmation(int $stock)
    {
        if ($stock < 1) {
            return '<p class="d-inline alert alert-danger" role="alert"><strong>在庫切れ！</strong></p>';
        } else {
            return $stock;
        }
    }
}
