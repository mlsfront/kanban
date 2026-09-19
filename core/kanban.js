// core/kanban.js
import { UI } from './ui.js';
import { DragDrop } from './dragdrop.js';
import { Storage } from './storage.js';

const AUTOSAVE_INTERVAL_MS = 3000; // Debounce: Salva 3s após o usuário parar de editar
let saveTimeout = null;
let changesPending = false;
let saveInFlight = false;
let savedIndicatorTimeout = null;
let lastInteractionTime = 0;
let saveBlockedAfterError = false; 

export const Kanban = {
    config: null,
    data: {
        title: '',
        columns: [], // Estrutura de colunas carregada do JSON
        tasks: {}    // Tasks carregadas do JSON
    },

    async init(cfg) {
        this.config = cfg;

        // Carregar dados (LocalStorage ou JSON)
        const loadedData = await Storage.load(cfg);

        // Se o dado do LocalStorage estiver corrompido ou o primeiro load,
        // ele conterá a estrutura completa (columns, tasks, title)
        this.data = loadedData;

        // Atualizar o título do quadro na página (apenas se existir, para não quebrar a Landing Page)
        const titleElement = document.getElementById('board-title');
        if (titleElement) {
            titleElement.innerText = `Kanban - ${this.data.title}`;
        }

        // Montar interface
        this.render();
    },

    render() {
        const container = document.querySelector(this.config.container);
        container.innerHTML = '';
        
        // NOVO: Atualiza o título do quadro na página
        this.updateTitle(); 

        this.data.columns.forEach(col => {
            const tasksInColumn = this.data.tasks[col.id] || [];
            const taskCount = tasksInColumn.length; // NOVO: Contagem de tarefas

            // Passa a contagem para createColumn
            const colElement = UI.createColumn(col.id, col.title, col.color, taskCount); 

            tasksInColumn.forEach(task => {
                colElement.appendChild(UI.createCard(task, col.id)); 
            });

            DragDrop.enable(colElement);
            container.appendChild(colElement);
        });

        // NOVO: Inicializa o DragDrop no container principal para COLUNAS
        DragDrop.enable(container);
    },

    // NOVO: Função para atualizar o título
    updateTitle() {
        const titleElement = document.getElementById('board-title');
        if (titleElement && this.data.title) {
            // Assume que o título no HTML é "Kanban - [Título do Quadro]"
            titleElement.textContent = `Kanban - ${this.data.title}`;
        }
        
        // Opcional: Atualiza o título da aba do navegador
        document.title = `Kanban - ${this.data.title}`;
    },

    save() {
        // Implementa Debounce: O salvamento só acontece após AUTOSAVE_INTERVAL_MS
        // de inatividade do usuário. Cada novo save() reseta o timer.
        
        lastInteractionTime = Date.now();
        changesPending = true;
        saveBlockedAfterError = false;
        this.scheduleServerSave();
    },

    scheduleServerSave() {
        // Cancela o timer anterior para implementar debounce
        if (saveTimeout) {
            clearTimeout(saveTimeout);
        }
        
        // Agenda novo timer: só vai executar se nenhum save() for chamado nos próximos 3s
        saveTimeout = setTimeout(() => {
            if (changesPending && !saveInFlight && !saveBlockedAfterError) {
                this.saveToServer();
            }
        }, AUTOSAVE_INTERVAL_MS);
    },

    // NOVO: Método para enviar os dados via AJAX/Fetch
    async saveToServer() {
        if (saveInFlight) return;
        saveInFlight = true;
        // 1. Prepara o payload
        const boardId = Storage.getBoardId(this.config); // Obtém o ID primeiro

        // Se for o quadro de teste, salva apenas no localStorage
        if (this.config.isTest) {
            localStorage.setItem(this.config.storageKey, JSON.stringify(this.data));
            changesPending = false;
            saveInFlight = false;
            this.showSavedIndicator();
            return;
        }

        const payload = {
            board_id: boardId,
            version: Number(this.data._version || 1),
            data: this.data
        };

        this.showSavingIndicator();
    
        try {
            const saveUrl = this.config.saveUrl || './save_board.php';
            const response = await fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },                
                body: JSON.stringify(payload)
            });

            const rawResponse = await response.text();
            let result;
            try {
                result = JSON.parse(rawResponse);
            } catch (parseError) {
                changesPending = false;
                saveBlockedAfterError = true;
                this.showErrorIndicator(`Servidor respondeu HTTP ${response.status}.`);
                console.error('[Kanban] resposta não JSON de save_board.php', {
                    url: saveUrl,
                    status: response.status,
                    body: rawResponse.slice(0, 500),
                    parseError
                });
                return;
            }
            if (response.status === 409) {
                changesPending = false;
                this.showErrorIndicator('Conflito: o quadro foi alterado em outro dispositivo. Recarregue antes de salvar.');
            } else if (result.success) {
                if (Number.isInteger(result.version)) this.data._version = result.version;
                changesPending = false;
                this.showSavedIndicator();
            } else {
                changesPending = false;
                saveBlockedAfterError = true;
                this.showErrorIndicator(result.message || 'Falha ao salvar o quadro.');
                console.error('[Kanban] save_board rejeitou o payload', { status: response.status, result });
            }
        } catch (error) {
            changesPending = false;
            saveBlockedAfterError = true;
            this.showErrorIndicator("Falha na conexão com o servidor.");
            console.error('[Kanban] save_board não respondeu como JSON ou não foi encontrado', error);
        } finally {
            saveInFlight = false;
        }
    }, // <-- Corrigido o erro de sintaxe aqui (continua o objeto)

    // NOVO: Adiciona uma nova coluna ao quadro
    addColumn(id, title, color) {
        // 1. Adiciona ao array de colunas
        this.data.columns.push({
            id: id,
            title: title,
            color: color
        });
        // 2. Cria a entrada vazia no objeto de tasks
        this.data.tasks[id] = [];
        
        this.save();
        this.render(); // Redesenha o quadro
    },

    // NOVO: Atualiza o título e/ou a cor de uma coluna
    updateColumn(id, newTitle, newColor) {
        const index = this.data.columns.findIndex(c => c.id === id);
        
        if (index !== -1) {
            this.data.columns[index].title = newTitle;
            this.data.columns[index].color = newColor;
            
            this.save();
            this.render(); // Redesenha o quadro
        }
    },

    // NOVO: Exclui uma coluna e todas as tarefas associadas
    deleteColumn(id) {
        // 1. Remove do array de colunas
        this.data.columns = this.data.columns.filter(c => c.id !== id);
        
        // 2. Remove as tasks associadas (ATENÇÃO: Perda de dados)
        delete this.data.tasks[id];
        
        this.save();
        this.render(); // Redesenha o quadro
    }, // <-- Corrigido o erro de sintaxe aqui (continua o objeto)

    isCompletedColumn(columnId) {
        const column = this.data.columns.find(col => col.id === columnId);
        const value = `${columnId} ${column?.title || ''}`
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();
        return /(^|[^a-z])(conclu|finaliz|done|complete)/.test(value);
    },

    // Move a tarefa de uma coluna para outra.
    moveTask(taskId, sourceColumnId, targetColumnId) {
        if (sourceColumnId === targetColumnId) return;

        const sourceTasks = this.data.tasks[sourceColumnId];
        const targetTasks = this.data.tasks[targetColumnId];
        
        const taskIndex = sourceTasks.findIndex(t => t.id === taskId);
        if (taskIndex === -1) return;

        const task = sourceTasks[taskIndex];
        
        // 1. Remove da origem
        sourceTasks.splice(taskIndex, 1);
        
        // 2. Adiciona ao destino (no final por enquanto)
        const completed = this.isCompletedColumn(targetColumnId);
        task.completed = completed;
        if (completed) {
            // Vencimentos não devem continuar ativos após a conclusão.
            task.due_date = null;
            task.dueDate = null;
        }
        targetTasks.push(task);

        this.save();
    },

    // NOVO: Reordena as colunas
    reorderColumn(columnId, targetColumnId) {
        const columns = this.data.columns;
        const columnToMoveIndex = columns.findIndex(c => c.id === columnId);
        
        if (columnToMoveIndex === -1) return;

        const columnToMove = columns[columnToMoveIndex];

        // 1. Remove a coluna do local atual
        columns.splice(columnToMoveIndex, 1);

        // 2. Insere na nova posição (targetColumnId é a coluna ANTES da qual vamos inserir)
        if (targetColumnId) {
            // Se há um alvo, insere antes dele
            const targetIndex = columns.findIndex(c => c.id === targetColumnId);
            if (targetIndex !== -1) {
                columns.splice(targetIndex, 0, columnToMove);
            }
        } else {
            // ✅ CORREÇÃO: Sem alvo (movido para o final do container)
            columns.push(columnToMove);
        }

        this.save();
        // A renderização completa é pesada, mas garante que o DOM reflete o modelo.
        // Se a reordenação no DOM já foi feita no dragdrop.js, pode ser removido.
        // Por enquanto, mantenha para garantir a sincronização do modelo.
        this.render(); 
    },

    // ===========================================
    // LÓGICA DO INDICADOR DE SALVAMENTO (UX)
    // Movido para DENTRO do objeto Kanban
    // ===========================================

    _updateIndicator(text, className, duration = 2000) {
        const indicator = document.getElementById('save-indicator');
        if (!indicator) return;

        // Limpa timeouts anteriores
        if (savedIndicatorTimeout) {
            clearTimeout(savedIndicatorTimeout);
        }

        indicator.textContent = text;
        indicator.className = `ms-3 badge ${className}`;
        indicator.style.display = 'inline-block';
        indicator.style.opacity = '1';

        // Agenda o desaparecimento, se a duração for maior que zero
        if (duration > 0) {
            savedIndicatorTimeout = setTimeout(() => {
                indicator.style.opacity = '0';
                // Esconde após a transição
                setTimeout(() => {
                    indicator.style.display = 'none';
                }, 300); 
            }, duration);
        }
    },

    showSavingIndicator() {
        // Salvando não deve desaparecer automaticamente (duração = 0)
        this._updateIndicator("Salvando...", "bg-warning text-dark", 0);
    },

    showSavedIndicator() {
        this._updateIndicator("Salvo!", "bg-success");
    },

    showErrorIndicator(message) {
        // Erro deve ficar mais tempo visível (5 segundos)
        this._updateIndicator(`ERRO: ${message}`, "bg-danger", 5000);
    }
};