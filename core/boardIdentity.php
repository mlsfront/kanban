<?php
// core/boardIdentity.php

function sanitize_board_id(string $value): string
{
    $value = trim($value);
    $transliterated = function_exists('iconv')
        ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value)
        : $value;

    if ($transliterated === false) {
        $transliterated = $value;
    }

    // IDs de arquivo permanecem ASCII, mas preservam letras e números da grafia.
    $id = preg_replace('/[^A-Za-z0-9]+/', '', $transliterated);
    return substr($id ?: 'quadro', 0, 50);
}
