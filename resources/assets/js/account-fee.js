import axios from "axios";

$(document).ready(function () {
    const assignFeeModal = $("#assignFeeModal");

    if (assignFeeModal.length) {
        // Evento ao abrir o modal
        assignFeeModal.on("show.bs.modal", function (event) {
            const button = $(event.relatedTarget);
            const feeId = button.data("fee-id");

            // Definir o fee_id no input hidden
            $("#assignedFeeId").val(feeId);

            // Resetar os selects ao abrir o modal
            $("#assignedAccountId").prop("selectedIndex", 0);
            $("#feeType").prop("selectedIndex", 0);
        });

        // Evento ao clicar no botão salvar
        $("#saveAccountFee").on("click", function () {
            const feeId = $("#assignedFeeId").val();
            const accountId = $("#assignedAccountId").val();
            const feeType = $("#feeType").val();

            if (accountId && feeType) {
                // Usar Axios para fazer a requisição
                axios
                    .post("/account-fees", {
                        account_id: accountId,
                        fee_id: feeId,
                        type: feeType,
                    })
                    .then(function (response) {
                        if (response.data.success) {
                            showToast("Fee assigned successfully!");
                            assignFeeModal.modal("hide");
                            // Opcional: recarregar a página ou atualizar a lista
                            // location.reload();
                        } else {
                            showToast(
                                response.data.message ||
                                    "An error occurred while assigning the fee.",
                                "danger",
                            );
                        }
                    })
                    .catch(function (error) {
                        console.error("Error:", error);

                        if (error.response && error.response.data) {
                            showToast(
                                error.response.data.message ||
                                    "An error occurred while assigning the fee.",
                                "danger",
                            );
                        }

                        if (
                            error.response &&
                            error.response.data &&
                            error.response.data.errors
                        ) {
                            // Mostrar erros de validação
                            let errorMessages = [];
                            Object.keys(error.response.data.errors).forEach(
                                (key) => {
                                    errorMessages.push(
                                        ...error.response.data.errors[key],
                                    );
                                },
                            );
                            alert(
                                "Validation errors:\n" +
                                    errorMessages.join("\n"),
                            );
                        } else {
                            alert("An error occurred while assigning the fee.");
                        }
                    });
            } else {
                alert("Please select an account and fee type.");
            }
        });
    }

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
