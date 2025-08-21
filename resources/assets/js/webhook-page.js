import axios from "axios";

$(document).ready(function () {
    // Intercepta a criação de um novo webhook
    $("#formAddWebhook").on("submit", async function (e) {
        e.preventDefault();
        const form = $(this);
        const button = form.find('button[type="submit"]');
        const url = form.attr("action");
        const data = {
            url: form.find('input[name="url"]').val(),
            event: form.find('select[name="event"]').val(),
        };

        button.prop("disabled", true).text("Saving...");

        try {
            const response = await axios.post(url, data);
            // A resposta do controller precisa retornar o HTML do novo item
            addWebhookToUI(response.data.html);
            showToast(response.data.message, "success");
            form[0].reset();
        } catch (error) {
            const errorMessage =
                error.response?.data?.message || "Failed to save webhook.";
            showToast(errorMessage, "danger");
        } finally {
            button.prop("disabled", false).text("Save Webhook");
        }
    });

    // Usa 'event delegation' para os botões que são adicionados dinamicamente
    $("body").on("submit", ".form-delete-webhook", async function (e) {
        e.preventDefault();
        if (!confirm("Are you sure you want to delete this webhook?")) return;

        const form = $(this);
        const button = form.find('button[type="submit"]');
        const url = form.attr("action");
        const listItem = form.closest("li");

        button.prop("disabled", true);

        try {
            const response = await axios.delete(url);
            listItem.fadeOut(400, () => listItem.remove());
            showToast(response.data.message, "success");
        } catch (error) {
            showToast(
                error.response?.data?.message || "Failed to delete webhook.",
                "danger",
            );
            button.prop("disabled", false);
        }
    });

    // Função helper para adicionar o webhook na lista
    function addWebhookToUI(webhookHtml) {
        $("#no-webhooks-message").remove();
        const webhookList = $("#webhook-list");
        $(webhookHtml).hide().appendTo(webhookList).fadeIn(400);
    }

    // Função para mostrar/esconder o token
    $("body").on("click", ".toggle-token", function () {
        const targetId = $(this).data("target-id");
        const targetInput = $("#" + targetId);
        if (targetInput.attr("type") === "password") {
            targetInput.attr("type", "text");
            $(this).text("Hide");
        } else {
            targetInput.attr("type", "password");
            $(this).text("Show");
        }
    });

    // (Opcional) Script para regenerar token via AJAX
    $("body").on("submit", ".form-regenerate-webhook", async function (e) {
        e.preventDefault();
        if (!confirm("Do you really want to regenerate this token?")) return;

        const form = $(this);
        const url = form.attr("action");
        const tokenInput = form
            .closest("li")
            .find('input[type="password"], input[type="text"]');

        try {
            const response = await axios.put(url);
            tokenInput.val(response.data.new_token);
            showToast(response.data.message, "success");
        } catch (error) {
            showToast(
                error.response?.data?.message || "Failed to regenerate token.",
                "danger",
            );
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
