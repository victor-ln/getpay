import axios from "axios";
// Supondo que a função showToast esteja disponível globalmente ou importe-a
// import { showToast } from './utils';

$(document).ready(function () {
    $("#formAddUserToAccount").on("submit", async function (e) {
        e.preventDefault();

        const form = $(this);
        const button = form.find('button[type="submit"]');
        const url = form.attr("action");
        const data = {
            name: form.find('[name="name"]').val(),
            email: form.find('[name="email"]').val(),
            password: form.find('[name="password"]').val(),
            role: form.find('[name="role"]').val(),
        };

        button.prop("disabled", true).text("Saving...");

        try {
            const response = await axios.post(url, data);

            // Adiciona a nova linha de usuário na tabela
            $("#no-users-row").remove(); // Remove a mensagem "Nenhum usuário"
            $("#account-users-table-body").append(response.data.html);

            $("#addUserModal").modal("hide"); // Esconde o modal
            form[0].reset(); // Limpa o formulário
            showToast(response.data.message, "success");
        } catch (error) {
            const errorMessage =
                error.response?.data?.message || "Failed to add user.";
            // Exibir erros de validação, se houver
            if (error.response?.status === 422) {
                let errors = Object.values(error.response.data.errors).join(
                    "\n",
                );
                alert("Validation errors:\n" + errors);
            } else {
                showToast(errorMessage, "danger");
            }
        } finally {
            button.prop("disabled", false).text("Save");
        }
    });

    // Limpa o formulário quando o modal é fechado
    $("#addUserModal").on("hidden.bs.modal", function () {
        $("#formAddUserToAccount")[0].reset();
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
