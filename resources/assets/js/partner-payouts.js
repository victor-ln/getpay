/**
 * Lógica AJAX para a página de Organização de Lucros dos Sócios.
 * com atualização dinâmica da UI (sem refresh).
 */
import axios from "axios";

// Garanta que a função showToast esteja disponível globalmente.
// Se não estiver, você pode defini-la aqui.
function showToast(message, type = "success") {
    // ... (sua implementação da função showToast)
    console.log(`Toast (${type}): ${message}`); // Fallback para console
}

function formatCurrency(value) {
    return new Intl.NumberFormat("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
}

// --- FUNÇÃO PRINCIPAL ---
$(document).ready(function () {
    const addPartnerForm = $("#addPartnerForm");
    const editPartnerForm = $("#editPartnerForm");
    const partnersListContainer = $("#partners-list-container");
    const addModalEl = document.getElementById("addPartnerModal");
    const editModalEl = document.getElementById("editPartnerModal");

    if (!addModalEl || !editModalEl) return;

    const addModal = new bootstrap.Modal(addModalEl);
    const editModal = new bootstrap.Modal(editModalEl);

    // --- Template para novos cards ---
    const cardTemplate = $("#partner-card-template").html();

    function createPartnerCard(partner) {
        const netForDistribution = parseFloat(
            $("#net-for-distribution")
                .text()
                .replace(/\./g, "")
                .replace(",", "."),
        );
        const availableAmount =
            netForDistribution * (partner.receiving_percentage / 100);

        let newCardHtml = cardTemplate
            .replace(/PARTNER_ID_PLACEHOLDER/g, partner.id)
            .replace(
                /PARTNER_JSON_PLACEHOLDER/g,
                `'${JSON.stringify(partner)}'`,
            )
            .replace(/PARTNER_NAME_PLACEHOLDER/g, partner.name)
            .replace(
                /AVAILABLE_AMOUNT_PLACEHOLDER/g,
                formatCurrency(availableAmount),
            )
            .replace(/PIX_KEY_PLACEHOLDER/g, partner.pix_key)
            .replace(
                /PIX_TYPE_PLACEHOLDER/g,
                partner.pix_key_type.toUpperCase(),
            )
            .replace(
                /PERCENTAGE_PLACEHOLDER/g,
                `${formatCurrency(partner.receiving_percentage)}%`,
            );

        return newCardHtml;
    }

    function updateTotalPercentage() {
        let total = 0;
        partnersListContainer.find(".percentage-info").each(function () {
            total += parseFloat(
                $(this).text().replace("%", "").replace(",", "."),
            );
        });
        $("#total-percentage").text(`${formatCurrency(total)}%`);
    }

    // --- ADICIONAR SÓCIO ---
    addPartnerForm.on("submit", async function (e) {
        e.preventDefault();
        const form = $(this);
        const button = form.find('button[type="submit"]');
        const url = form.attr("action");
        const data = Object.fromEntries(new FormData(this));

        button
            .prop("disabled", true)
            .html(
                '<span class="spinner-border spinner-border-sm"></span> Saving...',
            );

        try {
            const response = await axios.post(url, data);
            const newPartner = response.data.partner;
            $("#no-partners-alert").parent().remove();
            partnersListContainer.append(createPartnerCard(newPartner));
            updateTotalPercentage();
            showToast(response.data.message, "success");
            addModal.hide();
            form[0].reset();
        } catch (error) {
            const errorMessage =
                error.response?.data?.message || "Failed to add partner.";
            showToast(errorMessage, "danger");
        } finally {
            button.prop("disabled", false).html("Save Partner");
        }
    });

    // --- ABRIR MODAL DE EDIÇÃO ---
    partnersListContainer.on("click", ".edit-partner-btn", function () {
        const partnerData = $(this).data("partner");
        editPartnerForm.attr("action", `/partners/${partnerData.id}`);
        editForm.find("#editPartnerName").val(partnerData.name);
        // ... (preencher outros campos do formulário de edição)
        editModal.show();
    });

    // --- EDITAR SÓCIO ---
    editPartnerForm.on("submit", async function (e) {
        e.preventDefault();
        const form = $(this);
        const button = form.find('button[type="submit"]');
        const url = form.attr("action");
        const data = Object.fromEntries(new FormData(this));
        data._method = "PUT"; // Adiciona o método para o Laravel

        button
            .prop("disabled", true)
            .html(
                '<span class="spinner-border spinner-border-sm"></span> Saving...',
            );

        try {
            const response = await axios.post(url, data); // Axios envia PUT como POST com _method
            const updatedPartner = response.data.partner;
            $(`#partner-card-${updatedPartner.id}`).replaceWith(
                createPartnerCard(updatedPartner),
            );
            updateTotalPercentage();
            showToast(response.data.message, "success");
            editModal.hide();
        } catch (error) {
            const errorMessage =
                error.response?.data?.message || "Failed to update partner.";
            showToast(errorMessage, "danger");
        } finally {
            button.prop("disabled", false).html("Save Changes");
        }
    });

    // --- REMOVER SÓCIO ---
    partnersListContainer.on(
        "click",
        ".delete-partner-btn",
        async function (e) {
            e.preventDefault();
            const partnerName = $(this).data("item-name");
            const partnerId = $(this).data("id");
            const url = `/partners/${partnerId}`;

            if (
                confirm(
                    `Are you sure you want to remove partner "${partnerName}"?`,
                )
            ) {
                try {
                    const response = await axios.delete(url);
                    showToast(response.data.message, "success");
                    $(`#partner-card-${partnerId}`).fadeOut(400, function () {
                        $(this).remove();
                        updateTotalPercentage();
                        if (partnersListContainer.children().length === 0) {
                            partnersListContainer.html(
                                '<div class="col-12" id="no-partners-alert"><div class="alert alert-warning">No active partners found.</div></div>',
                            );
                        }
                    });
                } catch (error) {
                    showToast(
                        error.response?.data?.message ||
                            "Failed to remove partner.",
                        "danger",
                    );
                }
            }
        },
    );

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
