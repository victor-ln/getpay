/**
 * Lógica da interface do Banking - REATORADA PARA SSR (Server-Side Rendering)
 * Foco: Interações dinâmicas de depósito, saque e modais.
 * A renderização inicial é feita pelo Laravel Blade.
 */

import axios from "axios";

// Objeto para dados de saque em andamento (ainda necessário para o fluxo de múltiplos passos)
let withdrawalData = {};

// ===================================================================
// --- FUNÇÕES DE AJUDA (Helpers) ---
// ===================================================================

function formatCurrency(value) {
    return new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
    }).format(value);
}

function showToast(message, type = "success") {
    const toastContainer = $(".toast-container");
    if (!toastContainer.length) return;

    const toastColors = {
        success: "bg-success text-white",
        danger: "bg-danger text-white",
        warning: "bg-warning text-dark",
        info: "bg-info text-dark",
    };
    const toastClass = toastColors[type] || "bg-secondary text-white";
    const toastId = `toast-${Date.now()}`;

    const toastHtml = `
    <div id="${toastId}" class="toast align-items-center ${toastClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>`;

    toastContainer.append(toastHtml);
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
    toast.show();
    toastElement.addEventListener("hidden.bs.toast", () =>
        toastElement.remove(),
    );
}

$(document).ready(function () {
    // Inicializa o CSRF token para todas as chamadas Axios
    axios.get("/sanctum/csrf-cookie");

    // ===================================================================
    // --- MANIPULADORES DE EVENTOS (Event Handlers) ---
    // ===================================================================

    // --- LÓGICA DE DEPÓSITO ---
    $("#generate-charge-form").on("submit", async function (e) {
        e.preventDefault();

        const form = $(this);
        const amount = parseFloat(form.find("#deposit-amount").val());
        const description = form.find("#deposit-description").val();
        const minDeposit = parseFloat(
            form.find("#deposit-amount").attr("min") || 1,
        );

        if (isNaN(amount) || amount < minDeposit) {
            showToast(
                `The amount must be at least ${formatCurrency(minDeposit)}.`,
                "warning",
            );
            return;
        }

        // --- CORREÇÃO APLICADA AQUI ---
        // 1. Lê os dados do usuário diretamente dos atributos data-* do formulário
        const userDocument = form.data("user-document");
        const userName = form.data("user-name");

        // 2. Validação para garantir que os dados existem
        if (!userDocument || !userName) {
            showToast(
                "Please register a document in your user profile.",
                "danger",
            );
            // Você pode até redirecionar para a página de perfil aqui
            return;
        }

        const formButton = form.find('button[type="submit"]');
        formButton
            .prop("disabled", true)
            .find(".spinner-border")
            .removeClass("d-none");

        // 3. Monta o payload com os dados lidos do HTML
        const payload = {
            externalId: `${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
            amount: amount,
            document: userDocument,
            name: userName,
            identification: "Deposit by " + userName,
            expire: 3600,
            description: description || "Deposit by " + userName,
        };

        console.log("Enviando payload para /create-payment:", payload);

        try {
            const response = await axios.post("/create-payment", payload);

            // O resto da sua lógica de sucesso continua a mesma
            const pixData = response.data;
            console.log("Cobrança criada com sucesso:", pixData);
            $("#pix-qr-code").attr("src", pixData.data.qrcode);
            $("#pix-copy-paste-code").val(pixData.data.pix);
            $("#pix-amount-display").html(formatCurrency(amount));

            $("#deposit-step-1").addClass("d-none");
            $("#deposit-step-2").removeClass("d-none");
        } catch (error) {
            showToast(
                error.response?.data?.message || "Something went wrong.",
                "danger",
            );
        } finally {
            formButton
                .prop("disabled", false)
                .find(".spinner-border")
                .addClass("d-none");
        }
    });

    // --- LÓGICA DE SAQUE ---
    $("#withdraw-step-1-form").on("submit", function (e) {
        e.preventDefault(); // Previne o envio do formulário

        const amount = parseFloat($("#withdraw-amount").val());
        const $selectedOption = $("#pix-key-select").find("option:selected");
        const selectedKey = $selectedOption.val();
        const availableBalance = parseFloat(
            $("#withdrawable-balance-value").data("balance") || 0,
        );

        // Validações
        if (!selectedKey)
            return showToast("Select a key to withdraw from.", "warning");
        if (isNaN(amount) || amount <= 0)
            return showToast("Invalid amount.", "warning");
        if (amount > availableBalance)
            return showToast("Insufficient available balance.", "danger");

        // Guarda os dados para o próximo passo
        withdrawalData = {
            amount: amount,
            key: selectedKey,
            keyType: $selectedOption.data("type"),
        };

        // Prepara a tela de confirmação 2FA
        $("#confirmation-summary").html(
            `You are sending <strong>${formatCurrency(amount)}</strong><br>for the key:<strong>${selectedKey}</strong>`,
        );

        // Esconde o passo 1 e mostra o passo 2
        $("#withdraw-step-1-form").addClass("d-none");
        $("#withdraw-step-2-confirmation").removeClass("d-none");

        // Foca no primeiro input do 2FA para o usuário já começar a digitar
        $(".2fa-input").first().focus();
    });

    // ETAPA 2: Confirmação com 2FA e envio para a API
    $("#withdraw-step-2-form").on("submit", async function (e) {
        e.preventDefault();

        const twoFaCode = $(".2fa-input")
            .map((_, el) => $(el).val())
            .get()
            .join("");
        if (twoFaCode.length < 6)
            return showToast("Enter the 6-digit 2FA code.", "warning");

        const formButton = $(this).find('button[type="submit"]');
        formButton
            .prop("disabled", true)
            .find(".spinner-border")
            .removeClass("d-none");

        try {
            // Monta o payload com os dados guardados na variável withdrawalData
            const payload = {
                externalId: `${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
                amount: withdrawalData.amount,
                pixKey: withdrawalData.key,
                pixKeyType: withdrawalData.keyType,
                tfa_code: twoFaCode,
                documentNumber: "11111111111",
                name: "Getpay",
            };

            await axios.post("/request-payout", payload);

            showToast("Withdrawal request sent successfully!", "success");

            // Recarrega a página para atualizar o saldo e a lista de transações
            window.location.reload();
        } catch (error) {
            showToast(
                error.response?.data?.message ||
                    "An unexpected error has occurred.",
                "danger",
            );
        } finally {
            formButton
                .prop("disabled", false)
                .find(".spinner-border")
                .addClass("d-none");
        }
    });

    // --- LÓGICA DO MODAL DE CHAVE PIX ---
    $("#formAddNewPixKeyInModal").on("submit", async function (e) {
        e.preventDefault();

        const form = $(this);
        const button = form.find('button[type="submit"]');

        const payload = {
            type: form.find('[name="type"]').val(),
            key: form.find('[name="key"]').val(),
            // O backend já sabe a conta a partir do formulário de seleção do admin ou do usuário logado
        };

        button.prop("disabled", true).text("Saving...");

        try {
            const response = await axios.post(form.attr("action"), payload);
            const newKey = response.data.data;

            const mainSelect = $("#pix-key-select");
            const displayType = newKey.type; // Ajuste conforme a resposta da sua API

            // Cria e adiciona a nova opção ao select
            const newOption = new Option(
                `${displayType}: ${newKey.key}`,
                newKey.key,
                true,
                true,
            );
            newOption.setAttribute("data-type", newKey.type);
            mainSelect.append(newOption).trigger("change");

            $("#addPixKeyModal").modal("hide");
            form[0].reset();
            showToast("New key added and selected!", "success");
        } catch (error) {
            showToast(
                error.response?.data?.message || "Failed to add key.",
                "danger",
            );
        } finally {
            button.prop("disabled", false).text("Save and Use Key");
        }
    });

    // --- OUTROS HANDLERS DE UI (Copiar, Voltar, etc.) ---
    // Estes continuam úteis e não precisam de grandes alterações.
    $("#copy-pix-code-btn").on("click", function () {
        navigator.clipboard
            .writeText($("#pix-copy-paste-code").val())
            .then(() => showToast("Copied to clipboard!", "info"));
    });

    $("#create-new-deposit-btn, #new-withdrawal-btn").on("click", function () {
        // Reseta os formulários para o estado inicial
        $("#deposit-step-2, #withdraw-step-3-pending").addClass("d-none");
        $("#deposit-step-1, #withdraw-step-1-form").removeClass("d-none");
        $("#deposit-amount, #withdraw-amount").val("");
    });

    // ... (Handlers do 2FA Input, Refund e outros modais podem ser mantidos aqui se necessário) ...
    const $2faInputs = $(".2fa-input");
    $2faInputs.on("input", function () {
        if (this.value.length === 1) $(this).next(".2fa-input").focus();
    });
    $2faInputs.on("keydown", function (e) {
        if (e.key === "Backspace" && this.value.length === 0)
            $(this).prev(".2fa-input").focus();
    });
    $2faInputs.on("paste", function (e) {
        e.preventDefault();
        const pastedData = (
            e.originalEvent.clipboardData || window.clipboardData
        )
            .getData("text")
            .trim();
        if (/^\d{6}$/.test(pastedData)) {
            pastedData
                .split("")
                .forEach((char, index) => $2faInputs.eq(index).val(char));
            $2faInputs.last().focus();
        }
    });
    $2faInputs.on("focus", function () {
        $(this).select();
    });

    // --- Handlers da Tabela (Refund) ---
    $("#transactions-table-body").on("click", ".btn-refund", function () {
        const transactionId = $(this).data("id");
        const transaction = bankData.transactions.find(
            (tx) => tx.id == transactionId,
        );

        if (!transaction) {
            alert("Error: Could not find transaction data.");
            return;
        }

        // Popula as informações principais do modal
        $("#refund-tx-id").text(transactionId);
        $("#refund-amount").text(formatCurrency(transaction.amount));

        const $alertNotice = $("#refund-alert-notice");
        if (transaction.fee > 0) {
            // Constrói a mensagem completa e a insere no container do alerta
            const feeMessage = `
            <i class="bx bx-info-circle me-1"></i>
            Please note: The non-refundable fee of <strong>${formatCurrency(transaction.fee)}</strong> will be kept. 
            The total amount of <strong>${formatCurrency(transaction.amount)}</strong> will be debited from the account balance.
        `;
            $alertNotice.html(feeMessage).show(); // Mostra o alerta
        } else {
            // Se não houver taxa, garante que o alerta fique escondido
            $alertNotice.hide().empty();
        }

        $("#refund-form").data("transaction-id", transactionId);
        $("#refundModal").modal("show");
    });

    $("#refund-form").on("submit", async function (e) {
        e.preventDefault();

        const formButton = $(this).find('button[type="submit"]');
        const transactionId = $(this).data("transaction-id");
        const twoFaCode = $(this)
            .find(".2fa-input")
            .map((_, el) => $(el).val())
            .get()
            .join("");

        if (twoFaCode.length < 6) {
            alert("Please enter the complete 6-digit 2FA code.");
            return;
        }

        formButton.prop("disabled", true).text("Processing...");

        try {
            // Envia o código 2FA no payload
            const response = await axios.post(
                `/payments/${transactionId}/refund`,
                {
                    tfa_code: twoFaCode,
                },
            );

            alert(response.data.message || "Refund processed successfully!");
            $("#refundModal").modal("hide"); // Fecha o modal
            fetchDataWithCurrentFilters(); // Atualiza a tabela
        } catch (error) {
            const errorMessage =
                error.response?.data?.message ||
                "An unexpected error occurred.";
            alert(`Error: ${errorMessage}`);
        } finally {
            formButton.prop("disabled", false).text("Confirm Refund");
            // Limpa os campos do 2FA para a próxima vez
            $(this).find(".2fa-input").val("");
        }
    });

    function showToast(message, type = "success") {
        const toastContainer = $(".toast-container");
        if (!toastContainer.length) return;

        // Mapeia o tipo para as classes de cor do Bootstrap
        const toastColors = {
            success: "bg-success text-white",
            danger: "bg-danger text-white",
            warning: "bg-warning text-dark",
            info: "bg-info text-dark",
        };
        const toastClass = toastColors[type] || "bg-secondary text-white";
        const toastId = `toast-${Date.now()}`;

        // Cria o HTML do toast dinamicamente
        const toastHtml = `
        <div id="${toastId}" class="toast align-items-center ${toastClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

        toastContainer.append(toastHtml);

        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: 3000 }); // O toast some após 5 segundos

        toast.show();

        // Remove o elemento do DOM após ele ser escondido para não poluir a página
        toastElement.addEventListener("hidden.bs.toast", () => {
            toastElement.remove();
        });
    }

    $("#submitPixKeyForm").on("click", async function (e) {
        // Previne o comportamento padrão do clique
        e.preventDefault();

        // =======================================================
        // == CORREÇÃO APLICADA AQUI ==
        // =======================================================
        // Em vez de usar $(this), buscamos o formulário explicitamente pelo seu ID.
        // Isso garante que sempre encontraremos o formulário correto.
        const form = $("#formAddNewPixKeyInModal");

        const button = $(this); // 'this' agora se refere ao botão que foi clicado
        const url = form.attr("action"); // Agora 'url' encontrará o atributo action do formulário
        const accountId =
            $("#account-selector").val() ?? $("#account-selector-modal").val();

        // Se a URL ainda não for encontrada, é um sinal de que a rota no Blade falhou
        if (!url) {
            showToast(
                "Configuration error: The form action URL is missing.",
                "danger",
            );
            return;
        }

        if (!accountId) {
            showToast(
                "Please select an account at the top of the page first.",
                "danger",
            );
            return;
        }

        const payload = {
            account_id: accountId,
            type: form.find('[name="type"]').val(),
            key: form.find('[name="key"]').val(),
        };

        button.prop("disabled", true).text("Saving...");

        try {
            const response = await axios.post(url, payload);
            // ... O resto da sua lógica de sucesso continua a mesma ...
            const newKey = response.data.data;
            const mainSelect = $("#pix-key-select");
            mainSelect.append(
                `<option value="${newKey.key}" data-type="${newKey.type}">${newKey.type}: ${newKey.key}</option>`,
            );
            mainSelect.val(newKey.key);

            $("#addPixKeyModal").modal("hide");
            form[0].reset();
            showToast("New PIX key added successfully!", "success");
        } catch (error) {
            const message =
                error.response?.data?.message || "Failed to add the key.";
            showToast(message, "danger");
        } finally {
            button.prop("disabled", false).text("Save and Use Key");
        }
    });
});
