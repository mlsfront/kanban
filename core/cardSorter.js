// core/cardSorter.js
import { Kanban } from './kanban.js';

export const CardSorter = {
    setSort(type) {
        // Itera sobre todas as colunas
        for (const columnId in Kanban.data.tasks) {
            let tasks = Kanban.data.tasks[columnId];
            
            switch (type) {
                case 'priority_desc':
                    // Ordena: Alta > Média > Baixa
                    tasks.sort((a, b) => this.getPriorityValue(b.priority) - this.getPriorityValue(a.priority));
                    break;
                case 'due_date_asc':
                    // Ordena: Mais próximos no topo
                    tasks.sort((a, b) => {
                        const dateA = a.due_date ? new Date(a.due_date).getTime() : Infinity;
                        const dateB = b.due_date ? new Date(b.due_date).getTime() : Infinity;
                        return dateA - dateB;
                    });
                    break;
                case 'default':
                    // Retorna à ordem manual/original (não faz nada, pois o Kanban.render() re-aplicará a ordem salva)
                    break;
            }
        }
        
        // Salva a nova ordem e força a renderização para atualização visual
        Kanban.save();
        Kanban.render(); 
    },

    getPriorityValue(priority) {
        switch (priority) {
            case 'alta': return 3;
            case 'media': return 2;
            case 'baixa': return 1;
            default: return 0;
        }
    }
};