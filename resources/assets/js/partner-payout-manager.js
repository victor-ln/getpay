import axios from "axios";

$(document).ready(function () {
    const list = $("#payout-method-list");

    $("#formAddPayoutMethod").on("submit", async function (e) {
        e.preventDefault();
        const form = $(this);
        const button = form.find('button[type="submit"]');
        button.prop("disabled", true).text("Adding...");
        try {
            const response = await axios.post(
                form.attr("action"),
                new FormData(this),
            );
            $("#no-payout-methods-message").remove();
            list.append(response.data.html);
            showToast(response.data.message, "success");
            form[0].reset();
        } catch (error) {
            showToast(
                error.response?.data?.message || "Failed to add key.",
                "danger",
            );
        } finally {
            button.prop("disabled", false).text("Add Key");
        }
    });

    list.on("submit", ".form-delete-payout", async function (e) {
        e.preventDefault();
        if (!confirm("Are you sure?")) return;
        const form = $(this);
        const listItem = form.closest("li");
        try {
            const response = await axios.delete(form.attr("action"));
            listItem.fadeOut(300, () => listItem.remove());
            showToast(response.data.message, "success");
        } catch (error) {
            showToast(
                error.response?.data?.message || "Failed to remove key.",
                "danger",
            );
        }
    });

    // Para "Set as Default", um reload é a forma mais simples de atualizar a lista inteira
    list.on("submit", ".form-set-default", function () {
        if (!confirm("Set this method as default?")) {
            event.preventDefault();
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
        const toast = new bootstrap.Toast(toastElement, { delay: 6000 }); // O toast some após 5 segundos

        toast.show();

        // Remove o elemento do DOM após ele ser escondido para não poluir a página
        toastElement.addEventListener("hidden.bs.toast", () => {
            toastElement.remove();
        });
    }
});
