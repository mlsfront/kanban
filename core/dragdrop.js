// core/dragdrop.js
import { Kanban } from './kanban.js';

export const DragDrop = {
    // Inicializa os listeners para um container (o quadro kanban principal)
    enable(container) {
        // O container é o elemento pai (#kanban) para as colunas,
        // ou a própria coluna para os cards.
        
        // Se for o container principal, adiciona listeners para as COLUNAS
        if (container.id === 'kanban') { 
            // Adiciona listeners aos elementos .kanban-column
            container.querySelectorAll('.kanban-column').forEach(column => {
                column.draggable = true; // Torna a coluna arrastável
                column.ondragstart = this.handleColumnDragStart;
            });

            // Listeners no container para DROPAR colunas
            container.ondragover = this.handleColumnDragOver;
            container.ondrop = this.handleColumnDrop;
        } 
        // Se for uma coluna, adiciona listeners para os CARDS
        else if (container.classList.contains('kanban-column')) {
            // Listeners para DROPAR cards (o código existente)
            container.ondragover = this.handleCardDragOver;
            container.ondrop = this.handleCardDrop;
        }
    },

    // =============================
    // LÓGICA DE CARDS (EXISTENTE)
    // =============================
    
    // ... handleCardDragStart (Método original para cards: sem alteração)
    handleCardDragStart(e) {
        e.dataTransfer.setData("text/plain", e.target.id);
        e.dataTransfer.effectAllowed = "move";
    },

    handleCardDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = "move";
        // Lógica visual para indicar onde o card será inserido (opcional)
    },

    handleCardDrop(e) {
        e.preventDefault();
        const taskId = e.dataTransfer.getData("text/plain");
        const card = document.getElementById(taskId);
        
        // Verifica se soltou na lixeira
        let trashZone = e.target.closest('#trashZone');
        if (trashZone) {
            if (confirm("Tem certeza que deseja excluir esta tarefa?")) {
                const sourceColumnId = card.parentElement.id;
                Kanban.data.tasks[sourceColumnId] = Kanban.data.tasks[sourceColumnId].filter(t => t.id !== taskId);
                Kanban.save();
                Kanban.render();
            }
            return;
        }

        // Identifica a coluna de destino (sempre o elemento com a classe kanban-column)
        let targetColumn = e.target.closest('.kanban-column');

        if (!targetColumn || !card) return;

        const sourceColumnId = card.parentElement.id;
        const targetColumnId = targetColumn.id;
        
        // Insere o card visualmente
        targetColumn.appendChild(card); // Por enquanto, apenas move para o final

        // 1. Atualiza o modelo de dados
        Kanban.moveTask(taskId, sourceColumnId, targetColumnId);

        // 2. Chama o render para recontar as tarefas e atualizar o DOM de forma segura
        Kanban.render(); 
    },

    // =============================
    // LÓGICA DE COLUNAS (NOVO)
    // =============================

    handleColumnDragStart(e) {
        // Define o tipo de dado para COLUNA para diferenciar dos cards
        e.dataTransfer.setData("column/id", e.target.id);
        e.dataTransfer.effectAllowed = "move";
        e.stopPropagation(); // Importante para não disparar o dragstart do container pai
    },

    handleColumnDragOver(e) {
        // Só permite drop se o elemento arrastado for uma coluna
        if (e.dataTransfer.types.includes("column/id")) {
            e.preventDefault();
            e.dataTransfer.dropEffect = "move";
        }
    },
    
    // core/dragdrop.js (Função handleColumnDrop CORRIGIDA)
    handleColumnDrop(e) {
        e.preventDefault();
        
        // **1. Obter os dados da coluna**
        // Esta linha é crucial. O dado "column/id" só deve ser transferido quando
        // uma COLUNA estiver sendo arrastada.
        const columnId = e.dataTransfer.getData("column/id");
        const draggedColumn = document.getElementById(columnId);
        
        // Se a colunaId não existe, ou se o ID é de um Card (não de Coluna), SAIA!
        // Esta verificação garante que a lógica de coluna não tente mover um Card.
        if (!columnId || !draggedColumn || !draggedColumn.classList.contains('kanban-column')) {
            return; 
        }

        // Solução alternativa para o erro:
        const container = e.currentTarget;
        let targetColumn = e.target.closest('.kanban-column:not(.dragging)');
        
        if (targetColumn && targetColumn.id === columnId) {
            targetColumn = null; 
        }

        if (targetColumn && targetColumn.parentElement === container) {
            // Se o alvo for válido e for filho do container (correto)
            container.insertBefore(draggedColumn, targetColumn); 
            Kanban.reorderColumn(columnId, targetColumn.id);
        } else if (targetColumn === null) {
            // Soltou no final
            container.appendChild(draggedColumn);
            Kanban.reorderColumn(columnId, null);
        }
        
        if (draggedColumn) {
            draggedColumn.classList.remove('dragging');
        }
    }
};