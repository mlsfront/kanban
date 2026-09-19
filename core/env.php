<?php
// core/env.php

/**
 * Carrega um arquivo dotenv simples sem depender de Composer.
 * Variáveis já definidas no processo têm precedência sobre o arquivo.
 */
function load_env_file(string $path): void
{
    static $loaded = [];
    $path = realpath($path) ?: $path;
    if (isset($loaded[$path])) {
        return;
    }
    $loaded[$path] = true;

    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $lineNumber => $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_starts_with($line, 'export ')) {
            $line = substr($line, 7);
        }
        if (!str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $name)) {
            error_log("Linha dotenv ignorada ({$lineNumber}): nome inválido.");
            continue;
        }

        if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') ||
            ($value[0] === "'" && substr($value, -1) === "'"))) {
            $quote = $value[0];
            $value = substr($value, 1, -1);
            if ($quote === '"') {
                $value = stripcslashes($value);
            }
        } else {
            $value = preg_split('/\s+#/', $value, 2)[0];
            $value = trim($value);
        }

        if (getenv($name) === false) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }
}

load_env_file(dirname(__DIR__) . '/.env');
