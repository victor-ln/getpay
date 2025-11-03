import axios from "axios";
// Supondo que a função showToast esteja disponível globalmente

$(document).ready(function () {
    const partnerSelect = $("#partner_id_select");
    const partnersTableBody = $("#partners-table-body");

    // Adicionar Participação de Sócio
    $("#formAttachPartner").on("submit", async function (e) {
        e.preventDefault();
        const form = $(this);
        const button = form.find('button[type="submit"]');
        const data = {
            partner_id: partnerSelect.val(),
            commission_rate: form.find('[name="commission_rate"]').val(),
            platform_withdrawal_fee_rate: form
                .find('[name="platform_withdrawal_fee_rate"]')
                .val(),
            min_fee_for_commission: form
                .find('[name="min_fee_for_commission"]')
                .val(),
        };

        button.prop("disabled", true).text("...");

        try {
            const response = await axios.post(form.attr("action"), data);

            $("#no-partners-row").remove();
            $(response.data.html)
                .hide()
                .appendTo(partnersTableBody)
                .fadeIn(300);

            // Remove o sócio do dropdown para não ser adicionado novamente
            partnerSelect.find(`option[value="${data.partner_id}"]`).remove();

            form[0].reset();
            showToast(response.data.message, "success");
        } catch (error) {
            const message =
                error.response?.data?.message || "Unknown error occurred.";
            showToast(message, "danger");
        } finally {
            button.prop("disabled", false).text("Add");
        }
    });

    // Remover Participação de Sócio (usando event delegation)
    $("body").on("submit", ".form-detach-partner", async function (e) {
        e.preventDefault();
        if (!confirm("Are you sure?")) return;

        const form = $(this);
        const button = form.find('button[type="submit"]');
        const tableRow = form.closest("tr");
        const partnerId = tableRow.data("partner-id");
        const partnerName = tableRow.find("td:first").text();

        button.prop("disabled", true);

        try {
            const response = await axios.delete(form.attr("action"));

            tableRow.fadeOut(300, function () {
                $(this).remove();
                // Adiciona o sócio de volta ao dropdown
                partnerSelect.append(
                    $("<option>", {
                        value: partnerId,
                        text: partnerName,
                    }),
                );
            });

            showToast(response.data.message, "success");
        } catch (error) {
            showToast(
                error.response?.data?.message || "Unknown error occurred.",
                "danger",
            );
            button.prop("disabled", false);
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
