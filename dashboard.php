<?php
require_once(__DIR__ . '/core/config.php');
require_once(__DIR__ . '/core/auth.php');
require_once(__DIR__ . '/core/logger.php');
require_once(__DIR__ . '/core/database.php');

// Função de saneamento de ID para consistência
function safe_sanitize_id($id) {
    return preg_replace('/[^a-zA-Z0-9_\-]/', '', $id);
}

// Conectar ao banco e buscar os quadros (Apenas do usuário logado)
$conn = get_db_connection();

// Buscar detalhes do usuário logado
$stmtUser = $conn->prepare("SELECT nome, is_admin FROM usuarios WHERE id = :uid LIMIT 1");
$stmtUser->execute([':uid' => $user_id]);
$user = $stmtUser->fetch();

$boards_db = [];
$validBoardIds = [];
$DASHBOARD_LIMIT = 20; // Limite de quadros por página (Requisito 3)

try {
    // Otimização e Compatibilidade Máxima (MySQL 5.7+, MariaDB e MySQL 8.0+)
    // Extraímos apenas o tamanho dos arrays e strings que precisamos para calcular as métricas.
    $sql = "SELECT 
                id, 
                title, 
                last_updated,
                (CASE WHEN JSON_VALID(data) THEN 1 ELSE 0 END) as is_valid,
                
                -- Extrai a estrutura de colunas e tarefas de forma leve
                JSON_EXTRACT(data, '$.columns') as colunas_json,
                JSON_EXTRACT(data, '$.tasks') as tarefas_json
            FROM boards 
            WHERE usuario_id = :uid 
            ORDER BY last_updated DESC 
            LIMIT :limit";
            
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $DASHBOARD_LIMIT, PDO::PARAM_INT);
    $stmt->execute();
    
    while ($row = $stmt->fetch()) {
        $id = $row['id'];
        $validBoardIds[] = $id;
        
        $totalTarefas = 0;
        $allCompleted = false;
        $isValid = (bool)$row['is_valid'];
        
        if ($isValid && !empty($row['tarefas_json']) && !empty($row['colunas_json'])) {
            $tasksArray = json_decode($row['tarefas_json'], true);
            $columnsArray = json_decode($row['colunas_json'], true);
            
            if (is_array($tasksArray) && is_array($columnsArray)) {
                // 1. Calcula o total de tarefas somando o tamanho de cada coluna
                foreach ($tasksArray as $colId => $tasks) {
                    if (is_array($tasks)) {
                        $totalTarefas += count($tasks);
                    }
                }
                
                // 2. Verifica se está concluído: todas as tarefas precisam estar na ÚLTIMA coluna
                if ($totalTarefas > 0 && !empty($columnsArray)) {
                    $ultimaColuna = end($columnsArray);
                    $ultimaColunaId = $ultimaColuna['id'] ?? null;
                    
                    if ($ultimaColunaId && isset($tasksArray[$ultimaColunaId])) {
                        $tarefasUltimaColuna = count($tasksArray[$ultimaColunaId]);
                        // Se o total da última coluna for igual ao total do quadro
                        if ($tarefasUltimaColuna === $totalTarefas) {
                            $allCompleted = true;
                        }
                    }
                }
            }
        }
        
        $boards_db[] = [
            'id' => $id,
            'title' => !empty($row['title']) ? $row['title'] : ucfirst(str_replace('_',' ', $id)),
            'last_updated' => $row['last_updated'],
            'valid_json' => $isValid,
            'total' => $totalTarefas,
            'all_completed' => $allCompleted
        ];
    }
} catch (Exception $e) {
    error_log("Erro ao buscar boards do MySQL: " . $e->getMessage());
}

// Listar os templates disponíveis
$templatesPath = __DIR__ . "/templates";
$templateFiles = glob($templatesPath . '/*.json');
$templates = array_map(function($file) {
    return basename($file, '.json');
}, $templateFiles);

$defaultTemplate = 'default';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/img/favicon.ico" type="image/x-icon">
    <title>Painel Kanban</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="<?= BASE_URL ?>assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/theme.css">
    <style>
        .board-card { border-radius: 12px; padding: 20px; transition: 0.2s; border: 1px solid var(--app-border); background: var(--app-surface); color: var(--app-text); }
        .board-card:hover { transform: scale(1.03); box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        #delete-form { border: 1px dashed #dc3545; padding: 20px; border-radius: 8px; margin-top: 20px; }
    </style>
</head>
<body class="light">

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h2>Olá, <?= htmlspecialchars($user['nome'] ?? 'Usuário') ?>! 👋</h2>
        <div class="d-flex">
            <?php if(isset($user['is_admin']) && $user['is_admin'] == 1): ?>
                <a href="<?= BASE_URL ?>admin/logs.php" class="btn btn-outline-info me-2">Admin</a>
            <?php endif; ?>
            <form action="<?= BASE_URL ?>auth/logout.php" method="POST" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-danger">Sair</button>
            </form>
        </div>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Ops!</strong> Você já possui um quadro com este identificador.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="mb-4">
        <input type="text" id="searchBoards" class="form-control" placeholder="Buscar quadros por título ou ID..." onkeyup="filterBoards()">
    </div>

    <div class="row" id="boardsContainer">
        <?php if (empty($boards_db)): ?>
            <div class="col-12 text-center py-5 text-muted">
                <p>Você ainda não possui nenhum quadro Kanban. Crie um abaixo!</p>
            </div>
        <?php else: ?>
            <?php foreach ($boards_db as $info): 
                $id = $info['id'];
                
                // Define as classes de destaque solicitadas
                $highlightClass = '';
                if ($info['valid_json']) {
                    if ($info['all_completed'] && $info['total'] > 0) {
                        // Destaque Verde para concluídos
                        $highlightClass = 'bg-success bg-opacity-10 border-success';
                    } elseif ($info['total'] >= 10) {
                        // Destaque Amarelo para mais de 10 tarefas
                        $highlightClass = 'bg-warning bg-opacity-10 border-warning';
                    }
                }
            ?>
            <div class="col-md-4 mb-4 board-item">
                <div class="board-card shadow-sm h-100 d-flex flex-column <?= $highlightClass ?>">
                    <h5 class="board-title mb-1"><?= htmlspecialchars($info['title']) ?></h5>
                    <p class="text-muted mb-3"><small>ID: <code class="board-id"><?= htmlspecialchars($id) ?></code></small></p>

                    <div class="thumb-stats mb-3">
                        <?php if ($info['valid_json']): ?>
                            <strong>Total:</strong> <?= $info["total"] ?> tarefas
                        <?php else: ?>
                            <span class="badge bg-danger">Arquivo JSON inválido/antigo</span>
                        <?php endif; ?>
                    </div>

                    <div class="mt-auto">
                        <p class="text-muted mb-2" style="font-size: 0.85rem;">
                            📅 Atualizado em: <?= date('d/m/Y H:i', strtotime($info['last_updated'])) ?>
                        </p>
                        <a href="<?= BASE_URL ?>board.php?id=<?= urlencode($id) ?>" class="btn btn-primary w-100">
                            Abrir Quadro
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <hr class="my-4">

    <h4>Criar novo quadro</h4>
    <form action="<?= BASE_URL ?>new_board.php" method="POST" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-6">
            <input type="text" name="board_id" class="form-control" placeholder="ID do quadro (ex: projeto_x)" required>
        </div>
        <div class="col-md-4">
            <select name="template" class="form-select" required>
                <option value="" disabled>Selecionar Template</option>
                <?php foreach ($templates as $t): ?>
                    <option value="<?= $t ?>" <?= ($t === $defaultTemplate) ? 'selected' : '' ?>>
                        <?= ucfirst(str_replace('_',' ', $t)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-success w-100">Criar Quadro</button>
        </div>
    </form>
    
    <hr class="my-5">
    
    <details>
        <summary class="text-danger style="cursor: pointer;">Gerenciar/Exc  luir Quadros (Zona de Risco)</summary>
        <div id="delete-form">
            <h4 class="text-danger">Excluir Quadros em Massa</h4>
            <p class="text-muted">Selecione os quadros que deseja excluir de forma definitiva.</p>
            <form action="<?= BASE_URL ?>delete_boards.php" method="POST" onsubmit="return confirm('ATENÇÃO: Ação irreversível! Confirmar exclusão?');">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <?php foreach ($boards_db as $info): $id = $info['id']; ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="board_ids[]" value="<?= $id ?>" id="check_<?= $id ?>">
                            <label class="form-check-label" for="check_<?= $id ?>">
                                <?= htmlspecialchars($info['title']) ?> (<code><?= htmlspecialchars($id) ?></code>)
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-danger" <?= empty($boards_db) ? 'disabled' : '' ?>>
                    Excluir Selecionados
                </button>
            </form>
        </div>
    </details>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/theme.js"></script>
<script>
function filterBoards() {
    const input = document.getElementById('searchBoards').value.toLowerCase();
    const boards = document.querySelectorAll('.board-item');
    boards.forEach(board => {
        const title = board.querySelector('.board-title').textContent.toLowerCase();
        const id = board.querySelector('.board-id').textContent.toLowerCase();
        board.style.display = (title.includes(input) || id.includes(input)) ? '' : 'none';
    });
}

// Limpeza automática do localStorage de boards órfãos (Injetado via PHP com segurança)
(function() {
    // Array de IDs de quadros válidos vindos do banco de dados
    const validIds = <?= json_encode($validBoardIds) ?>; //
    const prefix = "kanban_";
    const invalidKeys = [];
    
    // ID reservado do quadro de teste público (NUNCA deve ser apagado)
    const TEST_BOARD_ID = 'test_board_public';

    // TRAVA DE SEGURANÇA: Se não houver nenhum quadro vindo do banco (usuário deslogado ou conta nova),
    // abortamos a limpeza para proteger o localStorage local contra limpezas acidentais em massa.
    if (!validIds || validIds.length === 0) {
        return;
    }

    // 1. Coletar chaves órfãs legítimas
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key.startsWith(prefix)) {
            const boardId = key.substring(prefix.length);
            
            // Se for o ID do quadro de testes público, ignora e mantém intacto
            if (boardId === TEST_BOARD_ID) {
                continue;
            }
            
            // Se o ID do board NÃO está na lista de IDs válidos do usuário no banco
            if (!validIds.includes(boardId)) {
                invalidKeys.push(key); //
            }
        }
    }

    // 2. Remover apenas chaves órfãs confirmadas
    if (invalidKeys.length > 0) {
        console.warn(`[Kanban Cleanup] Removendo ${invalidKeys.length} entradas órfãs do LocalStorage:`, invalidKeys);
        invalidKeys.forEach(key => {
            localStorage.removeItem(key); //
        });
    }
})();
</script>
</body>
</html>