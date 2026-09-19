<?php
// index.php (Landing Page)
require_once(__DIR__ . '/core/config.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$logged_in = isset($_SESSION['usuario_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/img/favicon.ico" type="image/x-icon">
    <title>Kanban - Organize seus projetos</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/kanban.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body.light { background: #f4f5f7; color: #222; }
        body.dark  { background: #1e1e1e; color: #ddd; }
        
        .hero {
            padding: 80px 0;
            text-align: center;
        }
        
        /* Botão tema */
        #toggleTheme {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 22px;
            cursor: pointer;
            background: #fff;
            border: 2px solid #999;
            transition: 0.2s;
            z-index: 1000;
        }
        body.dark #toggleTheme {
            background: #333;
            border-color: #bbb;
        }
    </style>
    <script src="<?= BASE_URL ?>assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/theme.css"></head>
<body class="light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h2><i class="bi bi-kanban text-primary"></i> KanbanApp</h2>
        <div>
            <?php if($logged_in): ?>
                <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-primary">Meu Painel</a>
            <?php else: ?>
                <div class="d-flex">
                    <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-outline-primary me-2">Login</a>
                    <a href="<?= BASE_URL ?>auth/register.php" class="btn btn-primary">Cadastrar</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="hero">
        <h1 class="display-4 fw-bold">Organize seus projetos com facilidade</h1>
        <p class="lead opacity-75 mb-4">A ferramenta ideal para gestão de tarefas visual e intuitiva.</p>
        <?php if(!$logged_in): ?>
            <a href="<?= BASE_URL ?>auth/register.php" class="btn btn-lg btn-success">Comece Grátis</a>
        <?php endif; ?>
    </div>

    <!-- Accordion sobre Kanban -->
    <div class="accordion mb-5 shadow-sm" id="accordionKanban">
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                    O que é um Kanban?
                </button>
            </h2>
            <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionKanban">
                <div class="accordion-body">
                    <strong>Kanban é um sistema visual para gestão de tarefas.</strong> Ele permite visualizar o fluxo de trabalho, limitar o trabalho em andamento e maximizar a eficiência. O quadro normalmente é dividido em colunas que representam os status (ex: Fazer, Fazendo, Feito), onde os cartões (tarefas) movem-se conforme progridem.
                </div>
            </div>
        </div>
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                    Quais são as vantagens de criar uma conta em relação ao Quadro Local?
                </button>
            </h2>
            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionKanban">
                <div class="accordion-body">
                    <p class="mb-3">
                        Embora o <strong>Quadro Local (Teste Aqui Mesmo)</strong> permita que você experimente a ferramenta imediatamente, criar uma conta oferece vantagens muito importantes:
                    </p>
                    
                    <ul class="ps-3 mb-3">
                        <li class="mb-2">
                            <strong>Salvamento Seguro em Nuvem:</strong> Seus dados são salvos em nosso banco de dados. No Quadro Local, eles ficam salvos apenas no seu navegador atual (via <code>localStorage</code>), o que significa que limpar o histórico ou trocar de dispositivo fará você perder seu progresso.
                        </li>
                        <li class="mb-2">
                            <strong>Acesso Multidispositivo:</strong> Acesse seu painel de tarefas de qualquer lugar (computador, tablet ou celular), bastando fazer login.
                        </li>
                        <li class="mb-2">
                            <strong>Múltiplos Quadros:</strong> Crie, renomeie, organize e navegue por projetos distintos. O Quadro Local é limitado a apenas um painel.
                        </li>
                        <li>
                            <strong>Backup e Relatórios:</strong> Exporte e importe o conteúdo de cada quadro no formato JSON e tenha acesso às <strong>Estatísticas do Quadro</strong>, incluindo:
                            <ul class="mt-1 list-unstyled ps-3 text-muted">
                                <li>• Distribuição por Prioridade</li>
                                <li>• Status de Vencimento</li>
                                <li>• Sumário Geral</li>
                            </ul>
                        </li>
                    </ul>
                    
                    <p class="mb-0 fw-semibold text-success">
                        E o melhor de tudo: é 100% gratuito. Espero que seja tão útil para você quanto é para mim!
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Board -->
    <h3 class="text-center mb-4">Teste Aqui Mesmo (Quadro Local)</h3>
    <p class="text-center opacity-75 mb-4">Sinta a experiência! Os dados deste quadro são salvos apenas no seu navegador.</p>

    <div id="kanban" class="kanban-board border rounded p-3" style="min-height: 500px;"></div>
    <span id="save-indicator" class="ms-3 badge bg-secondary" style="display: none; transition: opacity 0.3s; position: fixed; bottom: 20px; left: 20px;"></span>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script type="module">
    import { Kanban } from './core/kanban.js';
    import { Board } from './core/boardManager.js';
    import { ColumnManager } from './core/columnManager.js';
    import { ReportManager } from './core/reportManager.js';
    import { CardFilter } from './core/cardFilter.js';
    import { CardSorter } from './core/cardSorter.js';
    import { Storage } from './core/storage.js';
    
    // Override temporário no storage para não puxar do BD (apenas initialData local)
    Storage.load = async function(cfg) {
        const local = localStorage.getItem(cfg.storageKey);
        if (local) {
            try { return JSON.parse(local); } catch(e) {}
        }
        return this._getInitialData('Teste Público');
    };

    const BOARD_ID = 'test_board_public'; 

    Board.init({
        id: BOARD_ID,
        storageKey: "kanban_" + BOARD_ID,
        isTest: true
    });

    Kanban.init({
        container: "#kanban",
        id: BOARD_ID,
        storageKey: "kanban_" + BOARD_ID,
        isTest: true
    });
    
    window.ColumnManager = ColumnManager;
    window.ReportManager = ReportManager;
    window.CardFilter = CardFilter;
    window.CardSorter = CardSorter;
    window.Board = Board;
</script>

<script>
// --- Tema claro/escuro ---
const body = document.body;
const toggle = document.getElementById("toggleTheme");

if (localStorage.theme) {
    body.className = localStorage.theme;
    toggle.textContent = body.classList.contains("dark") ? "☀️" : "🌙";
}

toggle.onclick = () => {
    body.classList.toggle("dark");
    body.classList.toggle("light");
    const theme = body.classList.contains("dark") ? "dark" : "light";
    toggle.textContent = theme === "dark" ? "☀️" : "🌙";
    localStorage.theme = theme;
};
</script>
    <script src="<?= BASE_URL ?>assets/js/theme.js"></script></body>
</html>
