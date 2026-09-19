// core/storage.js

export const Storage = {

    // Helper: Obtém o ID do quadro a partir da configuração
    getBoardId(config) {
        return config && config.id ? config.id : undefined;
    },
    
    // Helper: Cria uma estrutura de dados inicial para um novo quadro
    _getInitialData(boardId) {
        return {
            title: boardId || 'Novo Quadro',
            columns: [
                { id: 'backlog', title: 'Backlog', color: '#0d6efd' },
                { id: 'desenvolvimento', title: 'Em Desenvolvimento', color: '#ffc107' },
                { id: 'concluido', title: 'Concluído', color: '#198754' }
            ],
            tasks: {
                'backlog': [],
                'desenvolvimento': [],
                'concluido': []
            }
        };
    },

    /**
     * Carrega os dados do quadro via API MySQL.
     * @param {object} config - Objeto de configuração contendo 'id'.
     * @returns {Promise<object>} Os dados do quadro.
     */
    async load(config) {
        // Agora busca dados do banco de dados MySQL usando o ID do quadro
        const jsonPath = config.source || `./get_board_db.php?id=${encodeURIComponent(config.id)}`; 
        
        try {
            const response = await fetch(jsonPath);
            
            if (!response.ok) {
                console.warn(`Quadro não encontrado no Banco de Dados: ${jsonPath}. Retornando estrutura inicial.`);
                return this._getInitialData(config.id); 
            }

            const data = await response.json();
            const version = Number(response.headers.get('X-Board-Version') || 1);
            Object.defineProperty(data, '_version', {
                value: Number.isInteger(version) && version > 0 ? version : 1,
                enumerable: false,
                writable: true,
            });
            console.log("Dados carregados com sucesso do Banco de Dados.");
            return data;

        } catch (error) {
            console.error("Erro ao carregar dados do BD. Falha de conexão ou JSON inválido:", error);
            return this._getInitialData(config.id); 
        }
    },
    
    /**
     * O método save() no Kanban.js foi modificado para apenas agendar o saveToServer.
     * Portanto, este método de Storage (que antes salvava no LocalStorage) não é mais necessário.
     * Mantenha-o vazio ou remova-o para evitar confusão.
     */
    save() {
        // Antiga lógica de LocalStorage removida.
        // O salvamento agora é feito apenas em Kanban.saveToServer.
    }
};