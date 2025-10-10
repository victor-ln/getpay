document.addEventListener("DOMContentLoaded", function () {
    const medTabs = document.querySelectorAll(".nav-tabs .nav-link");
    let currentBankId = null;

    // Função para carregar dados de uma página específica
    async function loadMedData(bankId, page = 1) {
        const tabPane = document.querySelector(`#navs-med-${bankId}`);
        const contentDiv = tabPane.querySelector(".pt-4");

        // Mostra loading
        contentDiv.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Carregando disputas...</p>
            </div>
        `;

        try {
            const response = await fetch(
                `/admin/meds/data/${bankId}?page=${page}`,
            );
            const data = await response.json();

            contentDiv.innerHTML = data.html;
            contentDiv.dataset.loaded = "true";

            // Adiciona eventos aos botões de paginação
            attachPaginationEvents(bankId);
        } catch (error) {
            console.error("Error loading MED data:", error);
            contentDiv.innerHTML =
                '<div class="alert alert-danger">Ocorreu um erro ao carregar os dados. Por favor, tente novamente.</div>';
        }
    }

    // Função para adicionar eventos aos botões de paginação
    function attachPaginationEvents(bankId) {
        const paginationButtons = document.querySelectorAll(".med-page-btn");

        paginationButtons.forEach((button) => {
            button.addEventListener("click", function () {
                const page = parseInt(this.dataset.page);
                if (!isNaN(page) && page > 0) {
                    loadMedData(bankId, page);
                }
            });
        });
    }

    // Event listener para mudança de abas
    medTabs.forEach((tab) => {
        tab.addEventListener("shown.bs.tab", async function (event) {
            const bankId = event.target.dataset.bankId;
            currentBankId = bankId;
            const tabPane = document.querySelector(
                event.target.dataset.bsTarget,
            );
            const contentDiv = tabPane.querySelector(".pt-4");

            // If already loaded, just reattach events
            if (contentDiv.dataset.loaded === "true") {
                attachPaginationEvents(bankId);
                return;
            }

            // Carrega os dados da primeira página
            await loadMedData(bankId, 1);
        });
    });

    // Anexa eventos de paginação para a aba inicial (primeira aba carregada pelo servidor)
    const activeTab = document.querySelector(".nav-tabs .nav-link.active");
    if (activeTab) {
        currentBankId = activeTab.dataset.bankId;
        attachPaginationEvents(currentBankId);
    }
});
