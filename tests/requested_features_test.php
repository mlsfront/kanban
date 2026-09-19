<?php
require_once __DIR__ . '/../core/boardIdentity.php';

$cases = [
    'Controle Produção' => 'ControleProducao',
    'Amor e Salvação' => 'AmoreSalvacao',
];
foreach ($cases as $input => $expected) {
    if (sanitize_board_id($input) !== $expected) {
        throw new RuntimeException("Sanitização incorreta para {$input}");
    }
}

putenv('MAIL_ENABLED=1');
putenv('MAIL_DRIVER=log');
require_once __DIR__ . '/../src/Config/Mail.php';
if (!\App\Config\Mail::enabled() || \App\Config\Mail::driver() !== 'log') {
    throw new RuntimeException('Configuração MAIL_DRIVER=log não foi aplicada.');
}
putenv('MAIL_DRIVER=off');
if (\App\Config\Mail::driver() !== 'off') {
    throw new RuntimeException('Configuração MAIL_DRIVER=off não foi aplicada.');
}
echo "requested_features_test: ok\n";
