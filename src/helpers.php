<?php

if(!function_exists('e')) {
    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8', false);
    }
}

if(!function_exists('yen')) {
    function yen(string $s): string {
        return '￥' . number_format($s);
    }
}