// core/ui.js
import { Kanban } from './kanban.js';
import { DragDrop } from './dragdrop.js';

// Função auxiliar para gerar IDs únicos (para novas tarefas)
function generateUniqueId() {
    return 'task_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[character]));
}

function safeColor(value) {
    return /^#[0-9a-fA-F]{6}$/.test(String(value)) ? String(value) : '#6c757d';
}

function isDueDateTodayOrPast(dueDateStr) {
    if (!dueDateStr) return null;
    try {
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const dueDate = new Date(dueDateStr);
        dueDate.setHours(0, 0, 0, 0); 

        if (dueDate.getTime() <= today.getTime()) {
             return dueDate.getTime() === today.getTime() ? 'today' : 'expired';
        }
        return 'future';

    } catch (e) {
        console.error("Formato de data inválido:", dueDateStr);
        return null;
    }
}

// ---------------- NOVO: CÓDIGO DO MODAL ----------------
// Adiciona o HTML do modal ao body na primeira vez que for chamado
let modalInitialized = false;
function initializeModal() {
    if (modalInitialized) return;
    const modalHTML = `
        <div class="modal fade" id="cardDetailModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cardModalTitle">Editar Tarefa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="cardDetailForm">
                            <input type="hidden" id="modalTaskId">
                            <div class="mb-3">
                                <label for="modalTaskText" class="form-label">Título</label>
                                <input type="text" class="form-control" id="modalTaskText" required>
                            </div>
                            <div class="mb-3">
                                <label for="modalTaskDesc" class="form-label">Descrição</label>
                                <textarea class="form-control" id="modalTaskDesc" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="modalTaskPriority" class="form-label">Prioridade</label>
                                <select class="form-select" id="modalTaskPriority">
                                    <option value="baixa">Baixa</option>
                                    <option value="media">Média</option>
                                    <option value="alta">Alta</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="modalTaskDueDate" class="form-label">Data de Vencimento</label>
                                <input type="date" class="form-control" id="modalTaskDueDate">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                <button type="button" class="btn btn-primary" id="modalSaveTask">Salvar Alterações</button>
                            </div>
                            <button type="button" class="btn btn-danger float-end me-3" id="modalDeleteTask">Excluir Tarefa</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    modalInitialized = true;
    
    // Adiciona o Listener de Salvar
    document.getElementById('modalSaveTask').onclick = saveTaskDetails;
    // Adiciona o Listener de Excluir
    document.getElementById('modalDeleteTask').onclick = deleteTask;

    // Configura o Bootstrap Modal (necessita do script BS)
    window.taskModal = new bootstrap.Modal(document.getElementById('cardDetailModal'));

    // Adiciona o Dropzone da Lixeira
    const trashHTML = `
        <div id="trashZone" style="display:none; position:fixed; bottom:20px; right:20px; width:80px; height:80px; background-color:rgba(220,53,69,0.8); color:white; border-radius:50%; align-items:center; justify-content:center; font-size:30px; z-index:9999; box-shadow:0 0 15px rgba(0,0,0,0.3); transition: transform 0.2s;">
            <i class="bi bi-trash"></i>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', trashHTML);

    const trashZone = document.getElementById('trashZone');
    trashZone.ondragover = (e) => {
        e.preventDefault(); // Necessário para permitir o drop
        trashZone.style.transform = 'scale(1.2)';
    };
    trashZone.ondragleave = (e) => {
        trashZone.style.transform = 'scale(1)';
    };
    trashZone.ondrop = (e) => {
        e.preventDefault();
        trashZone.style.transform = 'scale(1)';
        DragDrop.handleCardDrop(e); // Passa para a lógica de DragDrop processar o drop na lixeira
    };
}

// ---------------- FUNÇÕES DE MODAL ----------------

// Preenche e abre o modal
function openTaskDetails(task, columnId) {
    initializeModal();

    document.getElementById('modalTaskId').value = task.id;
    document.getElementById('modalTaskText').value = task.text;
    document.getElementById('modalTaskDesc').value = task.description || '';
    document.getElementById('modalTaskPriority').value = task.priority || 'baixa';
    document.getElementById('modalTaskDueDate').value = task.due_date || '';

    // Armazena o ID da coluna para exclusão
    document.getElementById('modalDeleteTask').setAttribute('data-column-id', columnId);

    window.taskModal.show();
}

// Salva os dados do modal
function saveTaskDetails() {
    const taskId = document.getElementById('modalTaskId').value;
    const newText = document.getElementById('modalTaskText').value.trim();
    
    // Encontra a tarefa no modelo de dados
    let updated = false;
    for (const columnId in Kanban.data.tasks) {
        const index = Kanban.data.tasks[columnId].findIndex(t => t.id === taskId);
        if (index !== -1) {
            const task = Kanban.data.tasks[columnId][index];
            task.text = newText;
            task.description = document.getElementById('modalTaskDesc').value;
            task.priority = document.getElementById('modalTaskPriority').value;
            task.due_date = document.getElementById('modalTaskDueDate').value;
            updated = true;
            break;
        }
    }

    if (updated) {
        Kanban.save();
        Kanban.render(); // Redesenha o quadro para atualizar visualmente
        window.taskModal.hide();
    }
}

// Exclui a tarefa
function deleteTask() {
    if (!confirm("Tem certeza que deseja excluir esta tarefa?")) return;
    
    const taskId = document.getElementById('modalTaskId').value;
    const columnId = document.getElementById('modalDeleteTask').getAttribute('data-column-id');

    if (columnId && Kanban.data.tasks[columnId]) {
        Kanban.data.tasks[columnId] = Kanban.data.tasks[columnId].filter(t => t.id !== taskId);
        
        Kanban.save();
        Kanban.render(); // Redesenha o quadro
        window.taskModal.hide();
    }
}

// ---------------- EXPORTAÇÕES ----------------

export const UI = {
    // Adicionado 'count' para o contador de tarefas
    createColumn(id, title, color, count = 0) {
        const column = document.createElement("div");
        column.className = "kanban-column";
        column.id = id;
        column.style.setProperty('--column-border-color', safeColor(color));

        const h = document.createElement("h5");
        h.className = "kanban-title";
        // NOVO: Adiciona o contador de tarefas no título
        h.textContent = String(title ?? '');
        const countBadge = document.createElement('span');
        countBadge.className = 'badge bg-secondary';
        countBadge.textContent = String(count);
        h.appendChild(document.createTextNode(' '));
        h.appendChild(countBadge); 

        const addButton = document.createElement("button");
        addButton.className = "btn btn-sm btn-outline-primary w-100 mb-2 bi bi-plus-lg";
        addButton.textContent = 'Adicionar Tarefa';
        addButton.onclick = () => this.addNewTask(id);

        column.appendChild(h);
        column.appendChild(addButton);
        return column;
    },

    createCard(task, columnId) {
        const card = document.createElement("div");
        const isCompleted = Boolean(task.completed) || Kanban.isCompletedColumn(columnId);
        const dateStatus = isCompleted ? null : isDueDateTodayOrPast(task.due_date);
        
        card.className = `kanban-card priority-${task.priority || 'baixa'}${isCompleted ? ' completed' : ''}`;
        
        // Adiciona classes para destaque visual da data
        if (dateStatus === 'today') {
            card.classList.add('due-today');
        } else if (dateStatus === 'expired') {
            card.classList.add('due-expired');
        }

        card.draggable = true;
        card.id = task.id;
        card.onclick = () => openTaskDetails(task, columnId);

        const title = document.createElement("div");
        title.className = "card-text-title";

        let iconsHTML = '';
        
        // Ícone de Descrição
        if (task.description) {
            iconsHTML += ' <i class="bi bi-card-text text-muted" title="Possui Descrição"></i>';
        }
        
        // Ícone de Data de Vencimento
        if (task.due_date && !isCompleted) {
            const dateDisplay = task.due_date;
            let iconClass = 'bi-calendar';
            let iconColor = 'text-muted';
            
            if (dateStatus === 'today') {
                 iconClass = 'bi-calendar-event-fill'; // Hoje
                 iconColor = 'text-danger';
            } else if (dateStatus === 'expired') {
                 iconClass = 'bi-calendar-x-fill'; // Expirado
                 iconColor = 'text-danger';
            }

            // Ícone de data flutuante à direita
            iconsHTML += `<i class="bi ${iconClass} ${iconColor} float-end ms-2" title="Vencimento: ${escapeHtml(dateDisplay)}"></i>`;
        }

        title.innerHTML = escapeHtml(task.text) + iconsHTML;

        // NOVO: Ícone de exclusão rápida (DOM Element para manter o escopo do módulo Kanban)
        const deleteBtn = document.createElement("i");
        deleteBtn.className = "bi bi-trash text-danger float-end delete-card-btn fs-8";
        deleteBtn.title = "Excluir";
        deleteBtn.style.cursor = "pointer";
        deleteBtn.onclick = (e) => {
            e.stopPropagation();
            if (confirm('Tem certeza que deseja excluir esta tarefa?')) {
                Kanban.data.tasks[columnId] = Kanban.data.tasks[columnId].filter(t => t.id !== task.id);
                Kanban.save();
                Kanban.render();
            }
        };
        title.appendChild(deleteBtn);
        
        card.appendChild(title);

        card.ondragstart = e => {
            e.dataTransfer.setData("text/plain", task.id);
            // Mostra a lixeira ao começar a arrastar
            const trashZone = document.getElementById('trashZone');
            if(trashZone) trashZone.style.display = 'flex';
        };
        
        card.ondragend = e => {
            // Esconde a lixeira ao terminar
            const trashZone = document.getElementById('trashZone');
            if(trashZone) trashZone.style.display = 'none';
        };

        return card;
    },
    
    // Função para adicionar nova tarefa (com dados iniciais do novo formato)
    addNewTask(columnId) {
        const newTask = {
            id: generateUniqueId(),
            text: 'Nova Tarefa - Clique para editar',
            description: '',
            priority: 'media',
            due_date: null
        };

        if (!Kanban.data.tasks[columnId]) {
            Kanban.data.tasks[columnId] = [];
        }
        Kanban.data.tasks[columnId].push(newTask);
        Kanban.save();

        const columnElement = document.getElementById(columnId);
        const cardElement = this.createCard(newTask, columnId);
        
        const addButton = columnElement.querySelector('.btn-outline-primary');
        columnElement.insertBefore(cardElement, addButton.nextSibling);

        // Abre o modal para edição imediata
        openTaskDetails(newTask, columnId);
    }
};