// core/cardFilter.js
import { Kanban } from './kanban.js';

let currentFilter = null; // null = todas as prioridades

export const CardFilter = {
    setFilter(priority) {
        currentFilter = priority;
        this.applyFilter();
    },

    applyFilter() {
        // Itera sobre todos os cards no DOM e os esconde se não corresponderem ao filtro
        const cards = document.querySelectorAll('.kanban-card');
        
        cards.forEach(card => {
            let matchesFilter = true;
            
            if (currentFilter) {
                // Checa se o card possui a classe de prioridade atual
                matchesFilter = card.classList.contains(`priority-${currentFilter}`);
            }
            
            // Aplica o estilo de exibição
            card.style.display = matchesFilter ? 'block' : 'none';
        });
        
        // O Kanban.render() não é chamado aqui, o que é mais performático.
        // Apenas manipulamos o DOM.
    }
};