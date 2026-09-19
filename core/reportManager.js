// core/reportManager.js
import { Kanban } from './kanban.js';

// Reutiliza a função de utilidade de data
function isDueDateTodayOrPast(dueDateStr) {
    if (!dueDateStr) return null;
    try {
        const today = new Date();
        today.setHours(0, 0, 0, 0); 
        const dueDate = new Date(dueDateStr);
        dueDate.setHours(0, 0, 0, 0); 

        if (dueDate.getTime() < today.getTime()) {
             return 'expired'; // Passou
        } else if (dueDate.getTime() === today.getTime()) {
             return 'today'; // Hoje
        }
        return 'future'; // Futuro

    } catch (e) {
        return null;
    }
}

export const ReportManager = {
    modalInstance: null,

    openReportModal() {
        if (!this.modalInstance) {
            this.modalInstance = new bootstrap.Modal(document.getElementById('reportModal'));
        }
        
        this.generateReport();
        this.modalInstance.show();
    },

    generateReport() {
        const allTasks = this.collectAllTasks();
        
        // 1. Estatísticas Gerais
        const totalTasks = allTasks.length;
        const totalColumns = Kanban.data.columns.length;
        document.getElementById('totalTasksCount').textContent = totalTasks;
        document.getElementById('totalColumnsCount').textContent = totalColumns;

        // 2. Estatísticas por Prioridade
        const priorityCounts = this.countByPriority(allTasks);
        this.renderPriorityStats(priorityCounts);

        // 3. Estatísticas por Data de Vencimento
        const dueDateCounts = this.countByDueDate(allTasks);
        this.renderDueDateStats(dueDateCounts);
    },

    collectAllTasks() {
        // Acha todas as tarefas em todas as colunas e injeta o ID da coluna
        const tasks = [];
        for (const colId in Kanban.data.tasks) {
            Kanban.data.tasks[colId].forEach(task => {
                tasks.push({ ...task, _columnId: colId });
            });
        }
        return tasks;
    },

    countByPriority(tasks) {
        const counts = { alta: 0, media: 0, baixa: 0 };
        tasks.forEach(task => {
            const priority = task.priority || 'baixa';
            if (counts.hasOwnProperty(priority)) {
                counts[priority]++;
            }
        });
        return counts;
    },

    countByDueDate(tasks) {
        const counts = { expired: 0, today: 0, future: 0, noDate: 0 };
        tasks.forEach(task => {
            // Ignorar tarefas concluídas/finalizadas nos contadores de vencimento
            const isCompleted = ['concluido', 'finalizado', 'done'].includes(task._columnId.toLowerCase());
            
            if (!task.due_date || isCompleted) {
                if (!isCompleted) counts.noDate++;
                return;
            }
            const status = isDueDateTodayOrPast(task.due_date);
            if (status) {
                counts[status]++;
            }
        });
        return counts;
    },
    
    // ---------------- RENDERIZAÇÃO ----------------

    renderPriorityStats(counts) {
        const container = document.getElementById('priorityStats');
        container.innerHTML = '';

        const renderItem = (label, count, colorClass) => `
            <div class="text-center">
                <span class="badge ${colorClass} fs-5">${count}</span>
                <p class="mb-0"><small>${label}</small></p>
            </div>
        `;

        container.innerHTML += renderItem('Alta', counts.alta, 'bg-danger');
        container.innerHTML += renderItem('Média', counts.media, 'bg-warning text-dark');
        container.innerHTML += renderItem('Baixa', counts.baixa, 'bg-info');
    },

    renderDueDateStats(counts) {
        const container = document.getElementById('dueDateStats');
        container.innerHTML = '';

        const renderItem = (label, count, colorClass) => `
            <div class="text-center">
                <span class="badge ${colorClass} fs-5">${count}</span>
                <p class="mb-0"><small>${label}</small></p>
            </div>
        `;

        // Atrasadas (Expired)
        container.innerHTML += renderItem('Atrasadas', counts.expired, 'bg-danger');
        // Vencem Hoje (Today)
        container.innerHTML += renderItem('Vencem Hoje', counts.today, 'bg-warning text-dark');
        // No Futuro (Future)
        container.innerHTML += renderItem('No Futuro', counts.future, 'bg-success');
        // Sem Data (NoDate)
        container.innerHTML += renderItem('Sem Data', counts.noDate, 'bg-secondary');
    }
};