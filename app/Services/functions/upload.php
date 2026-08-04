<?php

function uploadImage($file)
{
    $allowed = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);

    if (!in_array($mime, $allowed)) {
        throw new Exception('Arquivo inválido');
    }

    $name = bin2hex(random_bytes(16)) . '.jpg';

    move_uploaded_file(
        $file['tmp_name'],
        __DIR__ . '/../../../storage/uploads/' . $name
    );

    return $name;
}