import { Kanban } from './kanban.js';

function safeColor(value) {
    return /^#[0-9a-fA-F]{6}$/.test(String(value)) ? String(value) : '#6c757d';
}

let modalInstance = null;
const form = document.getElementById('columnForm');

export const ColumnManager = {
    // 1. Abertura e Inicialização do Modal
    openModal() {
        if (!modalInstance) {
            // Inicializa o modal do Bootstrap
            modalInstance = new bootstrap.Modal(document.getElementById('columnManagerModal'));
            form.addEventListener('submit', this.handleFormSubmit);
            document.getElementById('deleteColumnButton').addEventListener('click', this.handleDelete);
        }
        
        this.renderColumnList();
        this.resetForm();
        modalInstance.show();
    },

    // 2. Renderiza a lista de colunas no modal
    renderColumnList() {
        const container = document.getElementById('columnListContainer');
        container.innerHTML = '';
        
        Kanban.data.columns.forEach(col => {
            const item = document.createElement('div');
            item.className = 'list-group-item d-flex justify-content-between align-items-center';
            
            const info = document.createElement('div');
            info.className = 'd-flex align-items-center';
            const colorDot = document.createElement('span');
            colorDot.style.cssText = 'width: 15px; height: 15px; border-radius: 50%; display: inline-block; margin-right: 10px;';
            colorDot.style.backgroundColor = safeColor(col.color);
            const label = document.createElement('span');
            label.textContent = `${col.title ?? ''} (ID: ${col.id ?? ''})`;
            info.append(colorDot, label);

            const editButton = document.createElement('button');
            editButton.type = 'button';
            editButton.className = 'btn btn-sm btn-info bi bi-pencil';
            editButton.textContent = ' Editar';
            editButton.dataset.columnId = String(col.id ?? '');
            editButton.addEventListener('click', () => this.loadColumnForEdit(col.id));
            item.append(info, editButton);
            
            container.appendChild(item);
        });
    },

    // 3. Carrega os dados da coluna no formulário para edição
    loadColumnForEdit(id) {
        const column = Kanban.data.columns.find(c => c.id === id);
        if (!column) return;

        // Preenche o formulário
        document.getElementById('columnFormTitle').textContent = `Editar Coluna: ${column.title}`;
        document.getElementById('columnIdInput').value = column.id;
        document.getElementById('columnTitleInput').value = column.title;
        document.getElementById('columnColorInput').value = column.color;
        
        document.getElementById('saveColumnButton').textContent = 'Salvar Alterações';
        
        // Colunas criadas dinamicamente podem ser excluídas; as colunas de template são tratadas como editáveis.
        document.getElementById('deleteColumnButton').style.display = 'block';
        document.getElementById('deleteColumnButton').setAttribute('data-target-id', id);
    },

    // 4. Lida com o envio do formulário (Criação ou Atualização)
    handleFormSubmit(event) {
        event.preventDefault();
        
        const id = document.getElementById('columnIdInput').value.trim();
        const title = document.getElementById('columnTitleInput').value.trim();
        const color = document.getElementById('columnColorInput').value;
        if (!title || !/^#[0-9a-fA-F]{6}$/.test(color)) return;

        if (id) {
            // Se o ID existe, é uma ATUALIZAÇÃO
            Kanban.updateColumn(id, title, color);
        } else {
            // Se o ID é novo, é uma CRIAÇÃO
            const newId = title.toLowerCase().replace(/\s/g, '_').replace(/[^\w-]/g, '');
            if (Kanban.data.columns.some(c => c.id === newId)) {
                 alert("Erro: Já existe uma coluna com um ID gerado similar. Por favor, escolha um título mais único.");
                 return;
            }
            Kanban.addColumn(newId, title, color);
        }
        
        // Atualiza a visualização e reseta o formulário
        ColumnManager.renderColumnList();
        ColumnManager.resetForm();
    },
    
    // 5. Lida com a exclusão de coluna
    handleDelete() {
        const id = document.getElementById('deleteColumnButton').getAttribute('data-target-id');
        const column = Kanban.data.columns.find(c => c.id === id);

        if (column && confirm(`ATENÇÃO: Você tem certeza que deseja EXCLUIR a coluna "${column.title}"? Todas as ${Kanban.data.tasks[id]?.length || 0} tarefas serão perdidas.`)) {
            Kanban.deleteColumn(id);
            ColumnManager.renderColumnList();
            ColumnManager.resetForm();
            alert(`Coluna "${column.title}" excluída com sucesso.`);
        }
    },

    // 6. Reseta o formulário para o modo "Criar"
    resetForm() {
        document.getElementById('columnFormTitle').textContent = 'Adicionar Nova Coluna';
        document.getElementById('columnIdInput').value = '';
        document.getElementById('columnTitleInput').value = '';
        document.getElementById('columnColorInput').value = '#0d6efd';
        document.getElementById('saveColumnButton').textContent = 'Criar Coluna';
        document.getElementById('deleteColumnButton').style.display = 'none';
        document.getElementById('deleteColumnButton').removeAttribute('data-target-id');
    }
};