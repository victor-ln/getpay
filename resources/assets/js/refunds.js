/**
 * Lógica dedicada para a página de Gestão de Reembolsos (Refund Management).
 */
import axios from "axios";

// Garanta que a função showToast esteja disponível globalmente ou importe-a aqui.
// Exemplo de fallback:
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

$(document).ready(function () {
    const refundTable = $("#refund-table-body"); // Dando um ID para o corpo da tabela para ser mais específico
    const refundModalEl = document.getElementById("refundModal");

    if (!refundTable.length || !refundModalEl) {
        // Só executa se os elementos necessários estiverem na página
        return;
    }

    const refundModal = new bootstrap.Modal(refundModalEl);

    // --- LÓGICA PARA ABRIR O MODAL DE CONFIRMAÇÃO DE REEMBOLSO ---
    refundTable.on("click", ".btn-refund", function () {
        const refundButton = $(this);
        const paymentId = refundButton.data("id");

        // Pega os dados diretamente do botão ou da linha da tabela
        const amount = refundButton
            .closest("tr")
            .find(".payment-amount")
            .data("amount");
        const fee = refundButton.closest("tr").find(".payment-fee").data("fee");

        if (!paymentId || amount === undefined) {
            showToast("Error: Could not retrieve transaction data.", "danger");
            return;
        }

        // Preenche os dados no modal
        $("#refund-tx-id").text(paymentId);
        $("#refund-amount").text(
            new Intl.NumberFormat("pt-BR", {
                style: "currency",
                currency: "BRL",
            }).format(amount),
        );

        const $alertNotice = $("#refund-alert-notice");
        if (fee > 0) {
            const feeMessage = `<i class="bx bx-info-circle me-1"></i> Please note: The non-refundable fee of <strong>${new Intl.NumberFormat("pt-BR", { style: "currency", currency: "BRL" }).format(fee)}</strong> will be kept. The total amount of <strong>${new Intl.NumberFormat("pt-BR", { style: "currency", currency: "BRL" }).format(amount)}</strong> will be debited from the account balance.`;
            $alertNotice.html(feeMessage).removeClass("d-none");
        } else {
            $alertNotice.addClass("d-none").empty();
        }

        // Armazena o ID da transação no formulário do modal para uso posterior
        $("#refund-form").data("payment-id", paymentId);

        // Abre o modal
        refundModal.show();
    });

    // --- LÓGICA PARA SUBMETER O FORMULÁRIO DE REEMBOLSO DENTRO DO MODAL ---
    $("#refund-form").on("submit", async function (e) {
        e.preventDefault();

        const form = $(this);
        const formButton = form.find('button[type="submit"]');
        const paymentId = form.data("payment-id");
        const twoFaCode = form
            .find(".2fa-input")
            .map((_, el) => $(el).val())
            .get()
            .join("");

        if (twoFaCode.length < 6) {
            showToast("Please enter the complete 6-digit 2FA code.", "warning");
            return;
        }

        formButton
            .prop("disabled", true)
            .html(
                '<span class="spinner-border spinner-border-sm me-1"></span>Processing...',
            );

        try {
            // A rota para processar o refund deve ser definida aqui ou no formulário
            const response = await axios.post(`/payments/${paymentId}/refund`, {
                tfa_code: twoFaCode,
            });

            showToast(response.data.message, "success");

            // Esconde a linha da tabela com um efeito de fade out
            refundButton.closest("tr").fadeOut(500, function () {
                $(this).remove();
            });

            refundModal.hide();
        } catch (error) {
            const errorMessage =
                error.response?.data?.message ||
                "An unexpected error occurred.";
            showToast(errorMessage, "danger");
        } finally {
            formButton.prop("disabled", false).html("Confirm Refund");
            form.find(".2fa-input").val(""); // Limpa o campo 2FA
        }
    });
});
