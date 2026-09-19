<?php
// admin/logs.php
require_once(__DIR__ . '/../core/config.php');
require_once(__DIR__ . '/../core/auth.php');
require_once(__DIR__ . '/../core/database.php');

$conn = get_db_connection();

// Verifica se é admin
$stmtUser = $conn->prepare("SELECT is_admin FROM usuarios WHERE id = :uid");
$stmtUser->execute([':uid' => $user_id]);
$user = $stmtUser->fetch();

if (!$user || $user['is_admin'] != 1) {
    die("Acesso negado.");
}

// -------------------------------------------------------------
// LÓGICA DE LIMPEZA DE LOGS (PURGE)
// -------------------------------------------------------------
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_logs') {
    require_valid_csrf();
    $period = $_POST['purge_period'] ?? '';
    
    try {
        if ($period === '30') {
            $stmtPurge = $conn->prepare("DELETE FROM logs_acesso WHERE data_hora < DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmtPurge->execute();
            $message = "Logs com mais de 30 dias foram excluídos com sucesso!";
        } elseif ($period === '60') {
            $stmtPurge = $conn->prepare("DELETE FROM logs_acesso WHERE data_hora < DATE_SUB(NOW(), INTERVAL 60 DAY)");
            $stmtPurge->execute();
            $message = "Logs com mais de 60 dias foram excluídos com sucesso!";
        } elseif ($period === 'all') {
            $stmtPurge = $conn->query("TRUNCATE TABLE logs_acesso");
            $message = "Todos os logs foram limpos com sucesso!";
        }
    } catch (Exception $e) {
        $error = "Erro ao limpar logs: " . $e->getMessage();
    }
}

// -------------------------------------------------------------
// LÓGICA DOS FILTROS DE BUSCA
// -------------------------------------------------------------
$filter_user = $_GET['user_id'] ?? '';
$filter_start = $_GET['start_date'] ?? '';
$filter_end = $_GET['end_date'] ?? '';

// Busca a lista de usuários para alimentar o SELECT do filtro
$usuarios_lista = [];
try {
    $stmtUsers = $conn->query("SELECT id, nome, email FROM usuarios ORDER BY nome ASC");
    $usuarios_lista = $stmtUsers->fetchAll();
} catch (Exception $e) {
    // Silencioso
}

// Construção dinâmica da Query SQL
$sql = "SELECT l.id, l.pagina, l.data_hora, l.ip_address, u.nome, u.email 
        FROM logs_acesso l 
        LEFT JOIN usuarios u ON l.usuario_id = u.id 
        WHERE 1=1";

$params = [];

if (!empty($filter_user)) {
    if ($filter_user === 'guest') {
        $sql .= " AND l.usuario_id IS NULL";
    } else {
        $sql .= " AND l.usuario_id = :user_id";
        $params[':user_id'] = $filter_user;
    }
}

if (!empty($filter_start)) {
    $sql .= " AND l.data_hora >= :start_date";
    $params[':start_date'] = $filter_start . ' 00:00:00';
}

if (!empty($filter_end)) {
    $sql .= " AND l.data_hora <= :end_date";
    $params[':end_date'] = $filter_end . ' 23:59:59';
}

$sql .= " ORDER BY l.data_hora DESC LIMIT 100";

// Busca os logs filtrados
$logs = [];
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Erro ao buscar logs: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Acesso - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        th.sortable.th-sort-asc::after { content: " ↑"; }
        th.sortable.th-sort-desc::after { content: " ↓"; }
    </style>
    <script src="<?= BASE_URL ?>assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/theme.css"></head>
<body class="bg-light">
<div class="container py-4">

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-2"><h2>Logs de Acesso <small class="text-muted">(Filtrados/Últimos 100)</small></h2><a class="btn btn-outline-primary btn-sm" href="<?= BASE_URL ?>admin/mail_logs.php">E-mails em modo log</a></div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-outline-primary">Usuários</a>
            <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-outline-secondary">Voltar ao Painel</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm p-3">
                <h5 class="card-title border-bottom pb-2 mb-3">Filtros Inteligentes</h5>
                <form method="GET" class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Usuário</label>
                        <select name="user_id" class="form-select form-select-sm">
                            <option value="">Todos os Usuários</option>
                            <option value="guest" <?= $filter_user === 'guest' ? 'selected' : '' ?>>Apenas Visitantes (Não Logados)</option>
                            <?php foreach ($usuarios_lista as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= (string)$filter_user === (string)$u['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['nome']) ?> (<?= htmlspecialchars($u['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Data Início</label>
                        <input type="date" name="start_date" class="form-control form-select-sm" value="<?= htmlspecialchars($filter_start) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Data Fim</label>
                        <input type="date" name="end_date" class="form-control form-select-sm" value="<?= htmlspecialchars($filter_end) ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Filtrar</button>
                        <a href="logs.php" class="btn btn-secondary btn-sm" title="Limpar Filtros">✕</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm p-3 border-danger-subtle bg-danger-subtle bg-opacity-10">
                <h5 class="card-title text-danger border-bottom border-danger-subtle pb-2 mb-3">Manutenção de Logs</h5>
                <form method="POST" onsubmit="return confirm('Tem certeza absoluta que deseja executar esta ação de limpeza?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="clear_logs">
                    <div class="row g-2">
                        <div class="col-7">
                            <select name="purge_period" class="form-select form-select-sm" required>
                                <option value="">Selecione o período...</option>
                                <option value="30">Limpar mais antigos que 30 dias</option>
                                <option value="60">Limpar mais antigos que 60 dias</option>
                                <option value="all">Limpar Tudo (Zerar Tabela)</option>
                            </select>
                        </div>
                        <div class="col-5">
                            <button type="submit" class="btn btn-danger btn-sm w-100">Limpar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
<div class="card shadow-sm p-4">
    <div class="table-responsive">
        <!-- 1. Adicionado o ID "logTable" para o JavaScript encontrar a tabela -->
        <table class="table table-striped table-hover align-middle" id="logTable">
            <thead>
                <tr>
                    <!-- 2. Adicionado estilo de cursor de clique e uma classe para o JS identificar as colunas ordenáveis -->
                    <th class="sortable" style="cursor: pointer;">ID ↕</th>
                    <th class="sortable" style="cursor: pointer;">Data/Hora ↕</th>
                    <th class="sortable" style="cursor: pointer;">Usuário ↕</th>
                    <th class="sortable" style="cursor: pointer;">E-mail ↕</th>
                    <th class="sortable" style="cursor: pointer;">Página ↕</th>
                    <th class="sortable" style="cursor: pointer;">IP ↕</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= $log['id'] ?></td>
                    <!-- Importante: adicionado data-time para o JS ordenar a data corretamente como timestamp -->
                    <td data-time="<?= strtotime($log['data_hora']) ?>"><?= date('d/m/Y H:i:s', strtotime($log['data_hora'])) ?></td>
                    <td><?= $log['nome'] ?? '<span class="text-muted"><em>Visitante</em></span>' ?></td>
                    <td><?= $log['email'] ?? '-' ?></td>
                    <td><code><?= htmlspecialchars($log['pagina']) ?></code></td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum log encontrado para os filtros selecionados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script de Ordenação -->
<script>
document.querySelectorAll('#logTable th.sortable').forEach((headerCell, columnIndex) => {
    headerCell.addEventListener('click', () => {
        const table = headerCell.closest('table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        // Se a tabela estiver vazia, não faz nada
        if (rows.length === 1 && rows[0].querySelector('td').getAttribute('colspan')) return;

        // Verifica a direção atual (crescente ou decrescente)
        const isAscending = headerCell.classList.contains('th-sort-asc');
        
        // Remove classes de ordenação de todas as colunas
        table.querySelectorAll('th').forEach(th => th.classList.remove('th-sort-asc', 'th-sort-desc'));
        
        // Alterna a direção
        headerCell.classList.toggle('th-sort-asc', !isAscending);
        headerCell.classList.toggle('th-sort-desc', isAscending);

        // Ordena as linhas
        const sortedRows = rows.sort((a, b) => {
            const aCell = a.children[columnIndex];
            const bCell = b.children[columnIndex];

            // Tratamento especial para a coluna de Data (usa o atributo data-time)
            if (aCell.hasAttribute('data-time') && bCell.hasAttribute('data-time')) {
                const aTime = parseInt(aCell.getAttribute('data-time'), 10);
                const bTime = parseInt(bCell.getAttribute('data-time'), 10);
                return isAscending ? bTime - aTime : aTime - bTime;
            }

            // Tratamento para números (ex: ID)
            const aText = aCell.textContent.trim();
            const bText = bCell.textContent.trim();
            const aNum = parseFloat(aText);
            const bNum = parseFloat(bText);

            if (!isNaN(aNum) && !isNaN(bNum)) {
                return isAscending ? bNum - aNum : aNum - bNum;
            }

            // Ordenação de texto padrão (Usuário, e-mail, etc)
            return isAscending 
                ? bText.localeCompare(aText, undefined, {numeric: true, sensitivity: 'base'})
                : aText.localeCompare(bText, undefined, {numeric: true, sensitivity: 'base'});
        });

        // Reintroduz as linhas ordenadas no HTML
        tbody.append(...sortedRows);
    });
});
</script>
    <script src="<?= BASE_URL ?>assets/js/theme.js"></script></body>
</html>