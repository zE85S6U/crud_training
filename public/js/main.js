"use strict";
// テーブルレコード1行全体を選択する
jQuery(document).ready(function ($) {
    $('[data-href]').click(function () {
        window.location = $(this).data("href");
    });
});

// アップロードされる画像の属性を検証する
const input = document.querySelector('#productImage');
const preview = document.querySelector('.preview');
const btn = document.getElementsByClassName('imageSubmit');

const maxSize = 2097152;    // ファイルサイズ上限
input.addEventListener('change', updateImageDisplay);

//
function updateImageDisplay() {
    while (preview.firstChild) {
        preview.removeChild(preview.firstChild);
    }

    const curFiles = input.files;
    if (curFiles.length === 0) {
        const para = document.createElement('p');
        para.textContent = 'アップロードするファイルが選択されていません';
        preview.appendChild(para);
    } else {
        for (const file of curFiles) {
            const para = document.createElement('p');
            if (validFileType(file) && file.size < maxSize) {
                para.textContent = `ファイル名: ${file.name}, ファイルの長さ: ${returnFileSize(file.size)}.`;
                const image = document.createElement('img');
                image.src = URL.createObjectURL(file);
                image.classList.add("m-3");
                image.classList.add("d-block");
                image.classList.add("mx-auto");
                image.classList.add("img-fluid");


                preview.appendChild(image);
                preview.appendChild(para);

                // ボタンを活性化
                btn[0].removeAttribute('disabled');
            } else {
                para.textContent = `ファイル名: ${file.name}: ファイル形式が有効ではありません。選択しなおしてください。`;
                preview.appendChild(para);

                // ボタンを非活性化
                btn[0].disabled = true;
            }
        }
    }
}

// https://developer.mozilla.org/en-US/docs/Web/Media/Formats/Image_types
// 許可するファイルタイプ
const fileTypes = [
    "image/apng",
    "image/bmp",
    "image/gif",
    "image/jpeg",
    "image/pjpeg",
    "image/png",
];

// 許可されたファイルタイプか検証
function validFileType(file) {
    return fileTypes.includes(file.type);
}

// ファイルサイズを変換
function returnFileSize(number) {
    if (number < 1024) {
        return number + 'bytes';
    } else if (number >= 1024 && number < 1048576) {
        return (number / 1024).toFixed(1) + 'KB';
    } else if (number >= 1048576) {
        return (number / 1048576).toFixed(1) + 'MB';
    }
}