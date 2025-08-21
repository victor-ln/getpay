import axios from "axios";
// Garanta que a função showToast esteja disponível globalmente ou importe-a aqui.

$(document).ready(function () {
    const addPixKeyForm = $("#formAddPixKey");
    const pixKeyList = $("#pix-key-list");

    if (!addPixKeyForm.length) {
        return; // Se o formulário não está na página, não faz nada.
    }

    // --- LÓGICA PARA ADICIONAR UMA NOVA CHAVE ---
    addPixKeyForm.on("submit", async function (e) {
        e.preventDefault();

        const form = $(this);
        const button = form.find('button[type="submit"]');
        const url = form.attr("action");
        const data = {
            account_id: form.find("#account_id").val(),
            type: form.find("#pix_key_type").val(),
            key: form.find("#pix_key_value").val(),
        };

        button
            .prop("disabled", true)
            .html(
                '<span class="spinner-border spinner-border-sm"></span> Adding...',
            );

        try {
            const response = await axios.post(url, data);

            console.log(response.data);

            // Adiciona a nova chave à lista na tela
            addPixKeyToList(response.data.data);

            showToast("PIX key added successfully!", "success");
            form[0].reset(); // Limpa o formulário
        } catch (error) {
            const errorMessage =
                error.response?.data?.message || "Failed to add PIX key.";
            showToast(errorMessage, "danger");
        } finally {
            button.prop("disabled", false).html("Add Key");
        }
    });

    // --- LÓGICA PARA REMOVER UMA CHAVE ---
    // Usamos event delegation, pois os itens da lista podem ser adicionados dinamicamente
    pixKeyList.on("submit", ".form-delete-pix-key", async function (e) {
        e.preventDefault();

        if (!confirm("Are you sure you want to remove this PIX key?")) {
            return;
        }

        const form = $(this);
        const button = form.find('button[type="submit"]');
        const url = form.attr("action");
        const listItem = form.closest("li");

        button
            .prop("disabled", true)
            .html('<span class="spinner-border spinner-border-sm"></span>');

        try {
            const response = await axios.delete(url);

            // Remove o item da lista com uma animação de fade out
            listItem.fadeOut(400, function () {
                $(this).remove();
                if (pixKeyList.children().length === 0) {
                    pixKeyList.append(
                        '<li class="list-group-item text-muted" id="no-pix-keys-message">No PIX keys registered.</li>',
                    );
                }
            });

            showToast(response.data.message, "success");
        } catch (error) {
            const errorMessage =
                error.response?.data?.message || "Failed to remove PIX key.";
            showToast(errorMessage, "danger");
            button.prop("disabled", false).text("Remove");
        }
    });

    /**
     * Função helper para adicionar um novo item à lista de chaves PIX na UI.
     * @param {object} pixKey - O objeto da chave PIX retornado pela API.
     */
    function addPixKeyToList(pixKey) {
        // Remove a mensagem de "nenhuma chave" se ela existir
        $("#no-pix-keys-message").remove();

        const deleteUrl = `/account-pix-keys/${pixKey.id}`; // Constrói a URL de delete

        const newListItem = `
            <li class="list-group-item d-flex justify-content-between align-items-center" data-id="${pixKey.id}" style="display:none;">
                <span><strong>${pixKey.type}:</strong> ${pixKey.key}</span>
                <form action="${deleteUrl}" method="POST" class="form-delete-pix-key">
                    <input type="hidden" name="_token" value="${$('meta[name="csrf-token"]').attr("content")}">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                </form>
            </li>
        `;

        pixKeyList.append(newListItem);
        pixKeyList.find("li:last-child").fadeIn(400);
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
