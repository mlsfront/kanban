<?php 
require_once(__DIR__ . '/core/config.php');
require_once(__DIR__ . '/core/database.php');
require_once(__DIR__ . '/core/auth.php');
$board_id = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($_GET['id'] ?? ''));
if ($board_id === '') { http_response_code(400); exit('ID de quadro inválido.'); }
$conn = get_db_connection();
$stmt = $conn->prepare('SELECT title FROM boards WHERE id = :id AND usuario_id = :uid LIMIT 1');
$stmt->execute([':id' => $board_id, ':uid' => $user_id]);
$board_row = $stmt->fetch();
if (!$board_row) { http_response_code(404); exit('Quadro não encontrado.'); }
$board_title = $board_row['title']; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="<?= BASE_URL ?>assets/img/favicon.ico" type="image/x-icon">
    <title>Kanban - <?= htmlspecialchars($board_title, ENT_QUOTES, "UTF-8") ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/kanban.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="<?= BASE_URL ?>assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/theme.css"></head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between">
        <h2 class="mb-3" id="board-title">Kanban - <?= htmlspecialchars($board_title, ENT_QUOTES, "UTF-8") ?></h2> <a class="text-white text-decoration-none" href="<?= BASE_URL ?>dashboard.php"><button class="bi bi-house btn bg-secondary text-white"> Home</button></a>   
    </div>  
    
    <span id="save-indicator" class="ms-3 badge bg-secondary" style="display: none; transition: opacity 0.3s;"></span>

    <details class="my-4">
        <summary class="mb-3">Gerenciar operações</summary>
        <div class="mb-3 d-flex flex-wrap gap-2">
            <button class="btn btn-secondary" onclick="Board.exportJSON()">⬇ Exportar JSON</button>
            <button class="btn btn-warning bi bi-gear" onclick="ColumnManager.openModal()"> Gerenciar Colunas</button>
            <label for="importJSON" class="btn btn-success">
                <i class="bi bi-upload"></i> Importar JSON
                <input type="file" id="importJSON" onchange="Board.importJSON(event)" style="display: none;">
            </label>
            <button class="btn btn-danger" onclick="Board.clear()">Limpar Quadro (Local)</button>
            <button class="btn btn-info" onclick="Board.reload()">Recarregar JSON Externo</button>
            <button class="btn btn-info bi bi-graph-up-arrow" onclick="ReportManager.openReportModal()"> Relatórios</button>
        </div>
    </details>

    <div class="card bg-light p-3 mb-4">
        <h6 class="mb-2">Opções de Visualização</h6>
        <div class="d-flex gap-3 flex-wrap">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-funnel"></i> Filtrar Prioridade
                </button>
                <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                    <li><a class="dropdown-item" href="#" onclick="CardFilter.setFilter(null)">Todas as Prioridades</a></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="CardFilter.setFilter('alta')"><i class="bi bi-flag-fill"></i> Alta</a></li>
                    <li><a class="dropdown-item text-warning" href="#" onclick="CardFilter.setFilter('media')"><i class="bi bi-flag-fill"></i> Média</a></li>
                    <li><a class="dropdown-item text-info" href="#" onclick="CardFilter.setFilter('baixa')"><i class="bi bi-flag-fill"></i> Baixa</a></li>
                </ul>
            </div>
            
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-sort-down"></i> Ordenar Colunas
                </button>
                <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                    <li><a class="dropdown-item" href="#" onclick="CardSorter.setSort('priority_desc')">Prioridade (Alta primeiro)</a></li>
                    <li><a class="dropdown-item" href="#" onclick="CardSorter.setSort('due_date_asc')">Vencimento (Mais próximos)</a></li>
                    <li><a class="dropdown-item" href="#" onclick="CardSorter.setSort('default')">Padrão (Manual)</a></li>
                </ul>
            </div>

        </div>
    </div>

    <div class="modal fade" id="columnManagerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gerenciar Colunas do Quadro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Arraste e solte (em breve) para reordenar, ou use os botões para editar.</p>
                    
                    <div id="columnListContainer" class="list-group mb-4">
                        </div>

                    <hr>

                    <h6 id="columnFormTitle">Adicionar Nova Coluna</h6>
                    <form id="columnForm" class="row g-3">
                        <input type="hidden" id="columnIdInput" value="">
                        
                        <div class="col-md-4">
                            <label for="columnTitleInput" class="form-label">Título</label>
                            <input type="text" class="form-control" id="columnTitleInput" required>
                        </div>
                        <div class="col-md-4">
                            <label for="columnColorInput" class="form-label">Cor</label>
                            <input type="color" class="form-control form-control-color" id="columnColorInput" value="#0d6efd" title="Seletor de Cor">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100" id="saveColumnButton">Salvar Coluna</button>
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-danger float-end" id="deleteColumnButton" style="display:none;">Excluir Coluna</button>
                        </div>
                    </form>
                    
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Estatísticas do Quadro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="reportBody">
                    <div class="mb-4">
                        <h6>📊 Distribuição por Prioridade</h6>
                        <div id="priorityStats" class="d-flex justify-content-around">
                            </div>
                    </div>

                    <div class="mb-4">
                        <h6>⏳ Status de Vencimento</h6>
                        <div id="dueDateStats" class="d-flex justify-content-around">
                            </div>
                    </div>
                    
                    <hr>
                    <h6>Sumário Geral</h6>
                    <p>Total de Tarefas: <strong id="totalTasksCount">0</strong></p>
                    <p>Total de Colunas: <strong id="totalColumnsCount">0</strong></p>
                </div>
            </div>
        </div>
    </div>

    <div id="kanban" class="kanban-board"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script type="module">
    import { Kanban } from './core/kanban.js';
    import { Board } from './core/boardManager.js';
    import { ColumnManager } from './core/columnManager.js';
    import { ReportManager } from './core/reportManager.js';
    import { CardFilter } from './core/cardFilter.js';
    import { CardSorter } from './core/cardSorter.js';
    
    const BOARD_ID = <?= json_encode($board_id) ?>; 

    const API_GET_BOARD = <?= json_encode(BASE_URL) ?> + 'core/get_board_db.php?id=' + encodeURIComponent(BOARD_ID);
    const API_SAVE_BOARD = <?= json_encode(BASE_URL) ?> + 'core/save_board.php';

    Board.init({
        id: BOARD_ID,
        source: API_GET_BOARD,
        saveUrl: API_SAVE_BOARD,
        storageKey: "kanban_" + BOARD_ID
    });

    Kanban.init({
        container: "#kanban",
        id: BOARD_ID,
        source: API_GET_BOARD,
        saveUrl: API_SAVE_BOARD
    });
    
    window.ColumnManager = ColumnManager;
    window.ReportManager = ReportManager;
    window.CardFilter = CardFilter;
    window.CardSorter = CardSorter;
    window.Board = Board;
</script>


    <script src="<?= BASE_URL ?>assets/js/theme.js"></script></body>
</html> 