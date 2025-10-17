(function () {
    "use strict";

    // Configurações
    const CONFIG = {
        DISABLE_DURATION: 5000, // 5 segundos bloqueado após submit
        LOADING_TEXT: "Processing Transfers...",
        ORIGINAL_TEXT: "Confirm and Initiate All Transfers",
        STORAGE_KEY: "payout_processing_lock",
        LOCK_EXPIRY: 60000, // 1 minuto
    };

    class PayoutButtonProtection {
        constructor(buttonSelector) {
            this.button = document.querySelector(buttonSelector);
            this.form = this.button ? this.button.closest("form") : null;
            this.isProcessing = false;
            this.processedAt = null;

            if (!this.button || !this.form) {
                console.error("Button or form not found");
                return;
            }

            this.init();
        }

        init() {
            // Verifica se há um lock ativo de outra aba/janela
            this.checkExistingLock();

            // Adiciona listeners
            this.form.addEventListener("submit", (e) => this.handleSubmit(e));
            this.button.addEventListener("click", (e) => this.handleClick(e));

            // Previne double-click
            this.button.addEventListener("dblclick", (e) => {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });

            // Listener para mudanças no localStorage (sincroniza entre abas)
            window.addEventListener("storage", (e) => {
                if (e.key === CONFIG.STORAGE_KEY) {
                    this.checkExistingLock();
                }
            });

            // Limpa locks expirados ao carregar
            this.cleanExpiredLocks();
        }

        handleClick(e) {
            // Se já está processando, previne o click
            if (this.isProcessing) {
                e.preventDefault();
                e.stopPropagation();
                this.showWarning("Please wait, transfer is being processed...");
                return false;
            }
        }

        handleSubmit(e) {
            // PROTEÇÃO 1: Verifica se já está processando
            if (this.isProcessing) {
                e.preventDefault();
                this.showWarning("Transfer already in progress!");
                return false;
            }

            // PROTEÇÃO 2: Verifica lock no localStorage (outras abas)
            const existingLock = this.getStorageLock();
            if (existingLock && !this.isLockExpired(existingLock)) {
                e.preventDefault();
                this.showWarning(
                    "Transfer is being processed in another window!",
                );
                return false;
            }

            // PROTEÇÃO 3: Verifica se foi processado recentemente
            if (this.processedAt) {
                const timeSinceLastProcess = Date.now() - this.processedAt;
                if (timeSinceLastProcess < CONFIG.DISABLE_DURATION) {
                    e.preventDefault();
                    const remainingTime = Math.ceil(
                        (CONFIG.DISABLE_DURATION - timeSinceLastProcess) / 1000,
                    );
                    this.showWarning(
                        `Please wait ${remainingTime} seconds before submitting again.`,
                    );
                    return false;
                }
            }

            // Tudo ok, pode processar
            this.startProcessing();
            return true;
        }

        startProcessing() {
            this.isProcessing = true;
            this.processedAt = Date.now();

            // Desabilita o botão
            this.disableButton();

            // Cria lock no localStorage
            this.setStorageLock();

            // Após o tempo configurado, permite novo submit
            setTimeout(() => {
                this.stopProcessing();
            }, CONFIG.DISABLE_DURATION);
        }

        stopProcessing() {
            this.isProcessing = false;
            this.enableButton();
            this.removeStorageLock();
        }

        disableButton() {
            this.button.disabled = true;
            this.button.setAttribute(
                "data-original-text",
                this.button.textContent,
            );
            this.button.textContent = CONFIG.LOADING_TEXT;

            // Adiciona classes visuais
            this.button.classList.add("disabled", "processing");
            this.button.style.opacity = "0.6";
            this.button.style.cursor = "not-allowed";
            this.button.style.pointerEvents = "none";

            // Adiciona spinner (opcional)
            this.addSpinner();
        }

        enableButton() {
            this.button.disabled = false;

            const originalText =
                this.button.getAttribute("data-original-text") ||
                CONFIG.ORIGINAL_TEXT;
            this.button.textContent = originalText;

            this.button.classList.remove("disabled", "processing");
            this.button.style.opacity = "1";
            this.button.style.cursor = "pointer";
            this.button.style.pointerEvents = "auto";

            this.removeSpinner();
        }

        addSpinner() {
            if (!this.button.querySelector(".spinner")) {
                const spinner = document.createElement("span");
                spinner.className = "spinner";
                spinner.innerHTML = ' <i class="fas fa-spinner fa-spin"></i>';
                this.button.appendChild(spinner);
            }
        }

        removeSpinner() {
            const spinner = this.button.querySelector(".spinner");
            if (spinner) {
                spinner.remove();
            }
        }

        setStorageLock() {
            const lockData = {
                timestamp: Date.now(),
                expiresAt: Date.now() + CONFIG.LOCK_EXPIRY,
            };
            localStorage.setItem(CONFIG.STORAGE_KEY, JSON.stringify(lockData));
        }

        getStorageLock() {
            const lock = localStorage.getItem(CONFIG.STORAGE_KEY);
            return lock ? JSON.parse(lock) : null;
        }

        removeStorageLock() {
            localStorage.removeItem(CONFIG.STORAGE_KEY);
        }

        isLockExpired(lock) {
            return Date.now() > lock.expiresAt;
        }

        checkExistingLock() {
            const lock = this.getStorageLock();
            if (lock && !this.isLockExpired(lock)) {
                this.disableButton();

                // Habilita quando o lock expirar
                const timeUntilExpiry = lock.expiresAt - Date.now();
                setTimeout(() => {
                    this.checkExistingLock(); // Verifica novamente
                }, timeUntilExpiry);
            }
        }

        cleanExpiredLocks() {
            const lock = this.getStorageLock();
            if (lock && this.isLockExpired(lock)) {
                this.removeStorageLock();
            }
        }

        showWarning(message) {
            // Cria um alert toast (adapte ao seu sistema de notificações)
            if (typeof Swal !== "undefined") {
                // Se tiver SweetAlert2
                Swal.fire({
                    icon: "warning",
                    title: "Action Blocked",
                    text: message,
                    timer: 3000,
                    showConfirmButton: false,
                });
            } else if (typeof toastr !== "undefined") {
                // Se tiver Toastr
                toastr.warning(message, "Action Blocked");
            } else {
                // Fallback para alert nativo
                alert(message);
            }

            console.warn("[PayoutProtection]", message);
        }

        // Método público para resetar manualmente (útil para testes)
        reset() {
            this.stopProcessing();
            this.processedAt = null;
            console.log("[PayoutProtection] Reset completed");
        }
    }

    // Inicializa quando o DOM estiver pronto
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initProtection);
    } else {
        initProtection();
    }

    function initProtection() {
        // Inicializa a proteção
        const protection = new PayoutButtonProtection(
            'button[type="submit"].btn-primary.btn-lg',
        );

        // Expõe globalmente para debug (opcional)
        window.payoutProtection = protection;

        console.log("[PayoutProtection] Initialized successfully");
    }

    // Limpa locks quando a página é fechada
    window.addEventListener("beforeunload", () => {
        const lock = localStorage.getItem(CONFIG.STORAGE_KEY);
        if (lock) {
            const lockData = JSON.parse(lock);
            // Remove apenas se for recente (< 10 segundos)
            if (Date.now() - lockData.timestamp < 10000) {
                localStorage.removeItem(CONFIG.STORAGE_KEY);
            }
        }
    });
})();
