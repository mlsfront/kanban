<?php
// core/boardValidator.php

function validate_board_document($document): array
{
    if (!is_object($document) && !is_array($document)) {
        return [false, 'O documento do quadro deve ser um objeto JSON.'];
    }

    $data = is_object($document) ? get_object_vars($document) : $document;
    if (!isset($data['title'], $data['columns'], $data['tasks']) ||
        !is_string($data['title']) ||
        (!is_array($data['columns']) && !is_object($data['columns'])) ||
        (!is_array($data['tasks']) && !is_object($data['tasks']))) {
        return [false, 'O quadro deve conter title, columns e tasks válidos.'];
    }

    $columns = is_object($data['columns']) ? array_values(get_object_vars($data['columns'])) : $data['columns'];
    $tasksByColumn = is_object($data['tasks']) ? get_object_vars($data['tasks']) : $data['tasks'];
    if (strlen($data['title']) > 255 || count($columns) > 100) {
        return [false, 'O quadro excede os limites permitidos.'];
    }

    $columnIds = [];
    foreach ($columns as $column) {
        if (!is_array($column) && !is_object($column)) {
            return [false, 'Cada coluna deve ser um objeto.'];
        }
        $column = is_object($column) ? get_object_vars($column) : $column;
        $id = $column['id'] ?? '';
        $title = $column['title'] ?? '';
        if (!is_string($id) || !preg_match('/^[a-zA-Z0-9_-]{1,80}$/', $id) ||
            !is_string($title) || strlen($title) > 255 || isset($columnIds[$id])) {
            return [false, 'Coluna inválida ou duplicada.'];
        }
        $columnIds[$id] = true;
    }

    $taskCount = 0;
    foreach ($tasksByColumn as $columnId => $tasks) {
        if (!isset($columnIds[$columnId]) || !is_array($tasks)) {
            return [false, 'As tarefas devem referenciar colunas existentes.'];
        }
        foreach ($tasks as $task) {
            if (!is_array($task) && !is_object($task)) {
                return [false, 'Cada tarefa deve ser um objeto.'];
            }
            $task = is_object($task) ? get_object_vars($task) : $task;
            if (!isset($task['id'], $task['text']) || !is_string($task['id']) ||
                !is_string($task['text']) || strlen($task['id']) > 120 || strlen($task['text']) > 1000) {
                return [false, 'Tarefa inválida.'];
            }
            $priority = $task['priority'] ?? 'baixa';
            if (!in_array($priority, ['alta', 'media', 'baixa'], true)) {
                return [false, 'Prioridade de tarefa inválida.'];
            }
            $taskCount++;
            if ($taskCount > 10000) {
                return [false, 'O quadro excede o limite de tarefas.'];
            }
        }
    }

    return [true, null];
}
