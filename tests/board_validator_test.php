<?php
require_once __DIR__ . '/../core/boardValidator.php';

$valid = (object)[
    'title' => 'Teste',
    'columns' => [(object)['id' => 'backlog', 'title' => 'Backlog', 'color' => '#0d6efd']],
    'tasks' => (object)['backlog' => [(object)['id' => 'task-1', 'text' => 'Tarefa', 'priority' => 'alta']]],
];
[$ok, $error] = validate_board_document($valid);
if (!$ok) throw new RuntimeException('Documento válido foi rejeitado: ' . $error);

$invalid = (object)[
    'title' => 'Teste',
    'columns' => [(object)['id' => 'backlog', 'title' => 'Backlog']],
    'tasks' => (object)['outra-coluna' => []],
];
[$ok] = validate_board_document($invalid);
if ($ok) throw new RuntimeException('Documento com coluna inexistente foi aceito.');

echo "board_validator_test: ok\n";
