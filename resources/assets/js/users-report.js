document.addEventListener("DOMContentLoaded", function () {
    // ==========================================
    // Multi-Account Tab Lazy Loading
    // ==========================================
    initMultiAccountTab();

    // ==========================================
    // Individual User Analysis - Search
    // ==========================================
    initUserSearch();

    // ==========================================
    // Multi-Account Table - View User Details
    // ==========================================
    initMultiAccountViewButtons();

    // ==========================================
    // Custom Event Listener for Loading User Behavior
    // ==========================================
    initUserBehaviorLoader();
});

/**
 * Inicializa o carregamento lazy da aba Multi-Account
 */
function initMultiAccountTab() {
    const multiAccountTab = document.querySelector(
        'button[data-bs-target="#multi-account-tab"]',
    );

    if (!multiAccountTab) return;

    multiAccountTab.addEventListener("shown.bs.tab", async function (event) {
        const button = event.target;
        const tabPane = document.querySelector(button.dataset.bsTarget);

        if (!tabPane) {
            console.error("Tab pane not found!");
            return;
        }

        if (tabPane.dataset.loaded === "true") {
            return;
        }

        const url = button.dataset.url;

        if (!url) {
            console.error("URL not found in data-url attribute!");
            tabPane.innerHTML =
                '<div class="alert alert-danger">Configuration error: URL not defined.</div>';
            return;
        }

        try {
            const response = await fetch(url, {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (!data.html) {
                throw new Error("No HTML content in response");
            }

            tabPane.innerHTML = data.html;
            tabPane.dataset.loaded = "true";

            // Reinicializa os botões "View" após carregar o conteúdo
            initMultiAccountViewButtons();
        } catch (error) {
            console.error("Error loading multi-account data:", error);
            tabPane.innerHTML = `
                <div class="alert alert-danger">
                    <h5>Error loading data</h5>
                    <p>${error.message}</p>
                </div>
            `;
        }
    });
}

/**
 * Inicializa a funcionalidade de busca de usuários
 */
function initUserSearch() {
    const searchInput = document.getElementById("user-search-input");
    const searchBtn = document.getElementById("search-user-btn");
    const searchResults = document.getElementById("search-results");

    if (!searchInput) return;

    let searchTimeout;

    // Busca ao digitar (com debounce)
    searchInput.addEventListener("input", function () {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        if (query.length < 3) {
            searchResults.style.display = "none";
            return;
        }

        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 500);
    });

    // Busca ao pressionar Enter
    searchInput.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();
            const query = this.value.trim();
            if (query.length >= 3) {
                performSearch(query);
            }
        }
    });

    // Busca ao clicar no botão
    if (searchBtn) {
        searchBtn.addEventListener("click", function () {
            const query = searchInput.value.trim();
            if (query.length >= 3) {
                performSearch(query);
            } else {
                alert("Please enter at least 3 characters to search");
            }
        });
    }

    // Fecha os resultados de busca ao clicar fora
    document.addEventListener("click", function (e) {
        if (
            searchResults &&
            !searchResults.contains(e.target) &&
            e.target !== searchInput
        ) {
            searchResults.style.display = "none";
        }
    });
}

/**
 * Realiza a busca de usuários
 */
async function performSearch(query) {
    const searchResults = document.getElementById("search-results");

    if (!searchResults) return;

    try {
        searchResults.innerHTML =
            '<div class="list-group-item">Searching...</div>';
        searchResults.style.display = "block";

        const response = await fetch(
            `/admin/user-reports/search-users?search=${encodeURIComponent(query)}`,
            {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
            },
        );

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (!data.success) {
            searchResults.innerHTML = `<div class="list-group-item text-danger">${data.message}</div>`;
            return;
        }

        if (data.users.length === 0) {
            searchResults.innerHTML =
                '<div class="list-group-item text-muted">No users found</div>';
            return;
        }

        // Renderiza os resultados
        let html = "";
        data.users.forEach((user) => {
            html += `
                <button type="button" 
                        class="list-group-item list-group-item-action user-result-item" 
                        data-document="${user.document}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${user.name}</strong>
                            <br>
                            <small class="text-muted">${user.document}</small>
                        </div>
                        <i class="bx bx-chevron-right"></i>
                    </div>
                </button>
            `;
        });
        searchResults.innerHTML = html;

        // Adiciona event listeners aos resultados
        document.querySelectorAll(".user-result-item").forEach((item) => {
            item.addEventListener("click", function () {
                const document = this.dataset.document;
                const userName = this.querySelector("strong").textContent;

                loadUserBehavior(document);
                searchResults.style.display = "none";

                const searchInput =
                    document.getElementById("user-search-input");
                if (searchInput) {
                    searchInput.value = userName;
                }
            });
        });
    } catch (error) {
        console.error("Error searching users:", error);
        searchResults.innerHTML = `<div class="list-group-item text-danger">Error: ${error.message}</div>`;
    }
}

/**
 * Carrega o comportamento detalhado do usuário
 */
async function loadUserBehavior(document) {
    const behaviorContent = document.getElementById("user-behavior-content");

    if (!behaviorContent) {
        console.error("Behavior content element not found");
        return;
    }

    try {
        behaviorContent.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading user behavior analysis...</p>
            </div>
        `;

        const response = await fetch(
            `/admin/user-reports/user-behavior?document=${encodeURIComponent(document)}`,
            {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
            },
        );

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || "Unknown error");
        }

        behaviorContent.innerHTML = data.html;
    } catch (error) {
        console.error("Error loading user behavior:", error);
        behaviorContent.innerHTML = `
            <div class="alert alert-danger">
                <h5>Error loading user data</h5>
                <p>${error.message}</p>
                <button class="btn btn-sm btn-outline-danger" onclick="location.reload()">
                    Try Again
                </button>
            </div>
        `;
    }
}

/**
 * Inicializa os botões "View" da tabela Multi-Account
 * Usando delegação de eventos para suportar conteúdo carregado dinamicamente
 */
function initMultiAccountViewButtons() {
    // Remove listeners antigos para evitar duplicação
    const multiAccountTab = document.getElementById("multi-account-tab");
    if (!multiAccountTab) return;

    // Usa delegação de eventos no container pai
    multiAccountTab.removeEventListener("click", handleViewUserDetail);
    multiAccountTab.addEventListener("click", handleViewUserDetail);
}

/**
 * Handler para o clique nos botões "View User Detail"
 */
function handleViewUserDetail(e) {
    const btn = e.target.closest(".view-user-detail");
    if (!btn) return;

    e.preventDefault();
    const document = btn.dataset.document;

    if (!document) {
        console.error("Document not found in button data");
        return;
    }

    // Muda para a aba de análise individual
    const individualTab = document.querySelector(
        'button[data-bs-target="#individual-analysis-tab"]',
    );
    if (!individualTab) {
        console.error("Individual analysis tab not found");
        return;
    }

    const tab = new bootstrap.Tab(individualTab);
    tab.show();

    // Aguarda a aba ser mostrada antes de carregar os dados
    individualTab.addEventListener(
        "shown.bs.tab",
        function loadBehavior() {
            const searchInput = document.getElementById("user-search-input");
            if (searchInput) {
                searchInput.value = document;
            }

            loadUserBehavior(document);

            // Remove o listener para não acumular
            individualTab.removeEventListener("shown.bs.tab", loadBehavior);
        },
        { once: true },
    );
}

/**
 * Inicializa o listener para eventos customizados de carregamento
 * Permite que outras partes do código disparem o carregamento
 */
function initUserBehaviorLoader() {
    document.addEventListener("loadUserBehavior", function (e) {
        const document = e.detail?.document;
        if (document) {
            loadUserBehavior(document);
        }
    });
}

// Exporta funções para uso global se necessário
window.userReports = {
    loadUserBehavior,
    performSearch,
};
