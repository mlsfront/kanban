// core/boardManager.js
import { Storage } from "./storage.js";
import { Kanban } from "./kanban.js";

export const Board = {
    config: null,

    init(cfg) {
        this.config = cfg;
    },

    exportJSON() {
        const data = JSON.stringify(Kanban.data, null, 2);
        const blob = new Blob([data], { type: "application/json" });
        const a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = this.config.id + ".json";
        a.click();
    },

    importJSON(event) {
        const file = event.target.files[0];
        const reader = new FileReader();
        reader.onload = async e => {
            try {
                const importedData = JSON.parse(e.target.result);
                if (importedData && importedData.columns && importedData.tasks) {
                    // O JSON exportado não inclui _version. Consulte o servidor imediatamente
                    // antes de importar para evitar salvar uma versão antiga (normalmente 1).
                    const baseUrl = this.config.source || `./get_board_db.php?id=${encodeURIComponent(this.config.id)}`;
                    const versionUrl = `${baseUrl}${baseUrl.includes('?') ? '&' : '?'}_version=${Date.now()}`;
                    const versionResponse = await fetch(versionUrl, { cache: 'no-store' });
                    if (!versionResponse.ok) {
                        throw new Error('Não foi possível sincronizar a versão do quadro.');
                    }
                    const serverVersion = Number(versionResponse.headers.get('X-Board-Version') || 0);
                    const currentVersion = Number.isInteger(serverVersion) && serverVersion > 0
                        ? serverVersion
                        : Number(Kanban.data?._version || 1);
                    Object.defineProperty(importedData, '_version', {
                        value: Number.isInteger(currentVersion) && currentVersion > 0 ? currentVersion : 1,
                        enumerable: false,
                        writable: true,
                    });
                    Kanban.data = importedData;
                    Kanban.save();
                    Kanban.render();
                } else {
                    alert("JSON Inválido para importação.");
                }
            } catch (err) {
                alert("Erro ao processar arquivo JSON.");
            }
            event.target.value = '';
        };
        reader.readAsText(file);
    },

    clear() {
        if (confirm("Tem certeza que deseja limpar este Kanban?")) {
            localStorage.removeItem(this.config.storageKey);
            location.reload();
        }
    },

    async reload() {
        // A persistência oficial é o banco; a próxima inicialização faz GET autorizado.
        localStorage.removeItem(this.config.storageKey);
        location.reload();
    }
};

