import axios from "axios";
// Certifique-se de que a função showToast esteja disponível globalmente ou importe-a aqui.

$(document).ready(function () {
    const $2faCard = $("#twoFactorAuthCard");
    if (!$2faCard.length) return;

    // ✅ FUNÇÃO DE CONTROLE DA UI APRIMORADA
    // Esta função agora é responsável por limpar qualquer estado antigo antes de mostrar o novo.
    const showSection = (sectionToShow) => {
        // Esconde todas as seções principais
        $2faCard.find(".tfa-section").hide();
        // Garante que elementos secundários, como os códigos de recuperação, também sejam escondidos.
        $2faCard.find("#recoveryCodesInfo").hide();
        // Agora, mostra apenas a seção que queremos
        $2faCard.find(sectionToShow).show();
    };

    // --- Habilitar 2FA ---
    $2faCard.on("submit", "#formEnable2fa", async function (e) {
        e.preventDefault();
        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const originalButtonText = $button.html();
        $button
            .prop("disabled", true)
            .html(
                '<span class="spinner-border spinner-border-sm"></span> Enabling...',
            );

        try {
            const response = await axios.post($form.attr("action"));

            $("#qrCodeContainer").html(response.data.qrCodeSvg);
            $("#secretKeyContainer").text(response.data.secretKey);

            showSection("#tfaConfirmationSection"); // Apenas mostra a seção de confirmação
        } catch (error) {
            showToast("Failed to start 2FA setup. Please try again.", "danger");
            $button.prop("disabled", false).html(originalButtonText);
        }
    });

    // --- Confirmar 2FA ---
    $2faCard.on("submit", "#formConfirm2fa", async function (e) {
        e.preventDefault();
        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const $input = $form.find('input[name="code"]');
        const originalButtonText = $button.html();

        $button
            .prop("disabled", true)
            .html(
                '<span class="spinner-border spinner-border-sm"></span> Confirming...',
            );

        try {
            const response = await axios.post($form.attr("action"), {
                code: $input.val(),
            });

            showToast(response.data.message, "success");

            const codesHtml = response.data.recoveryCodes
                .map((code) => `<li><code>${code}</code></li>`)
                .join("");
            $("#recoveryCodesList").html(codesHtml);

            showSection("#tfaEnabledSection"); // Mostra a seção de "Habilitado"
            $("#recoveryCodesInfo").show(); // E AGORA mostra os códigos de recuperação
        } catch (error) {
            const errorMessage =
                error.response?.data?.message || "An error occurred.";
            showToast(errorMessage, "danger");
            $button.prop("disabled", false).html(originalButtonText);
        }
    });

    // --- Desabilitar 2FA ---
    $2faCard.on("submit", "#formDisable2fa", async function (e) {
        e.preventDefault();
        if (!confirm("Are you sure you want to disable 2FA?")) return;

        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const originalButtonText = $button.html();
        $button
            .prop("disabled", true)
            .html(
                '<span class="spinner-border spinner-border-sm"></span> Disabling...',
            );

        try {
            const response = await axios.post($form.attr("action"));
            showToast(response.data.message, "success");

            // ✅ A função showSection agora limpa automaticamente os códigos de recuperação da tela
            showSection("#tfaDisabledSection");
        } catch (error) {
            showToast("Failed to disable 2FA. Please try again.", "danger");
        } finally {
            // Em caso de erro, o botão precisa ser reativado
            $button.prop("disabled", false).html(originalButtonText);
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
});
