"use strict";
// テーブルレコード1行全体を選択する
jQuery(document).ready(function ($) {
    $('[data-href]').click(function () {
        window.location = $(this).data("href");
    });
});