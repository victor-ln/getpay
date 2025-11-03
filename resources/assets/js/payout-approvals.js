document.addEventListener("DOMContentLoaded", function () {
    // Pega o token CSRF da meta tag (necessário para 'fetch' POST)
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    // --- LÓGICA DO MODAL "VIEW DETAILS" ---
    const viewModal = document.getElementById("viewDetailsModal");
    if (viewModal) {
        const loadingState = viewModal.querySelector("#view-modal-loading");
        const contentState = viewModal.querySelector("#view-modal-content");

        viewModal.addEventListener("show.bs.modal", async (event) => {
            const button = event.relatedTarget;
            const url = button.dataset.url;

            // 1. Resetar o modal
            loadingState.classList.remove("d-none");
            contentState.classList.add("d-none");

            try {
                // 2. Buscar os dados
                const response = await fetch(url);
                if (!response.ok) throw new Error("Failed to load details.");
                const data = await response.json();

                // 3. Preencher o modal
                viewModal.querySelector("#detail-amount").textContent =
                    data.amount;
                viewModal.querySelector("#detail-fee").textContent = data.fee;
                viewModal.querySelector("#detail-total-debit").textContent =
                    data.total_debit;
                viewModal.querySelector("#detail-dest-name").textContent =
                    data.destination_name;
                viewModal.querySelector("#detail-dest-doc").textContent =
                    data.destination_document;
                viewModal.querySelector("#detail-dest-key").textContent =
                    `${data.destination_key_type}: ${data.destination_key}`;
                viewModal.querySelector("#detail-account").textContent =
                    data.requested_by_account;
                viewModal.querySelector("#detail-date").textContent =
                    data.requested_at;

                // 4. Mostrar o conteúdo
                loadingState.classList.add("d-none");
                contentState.classList.remove("d-none");
            } catch (error) {
                console.error(error);
                loadingState.innerHTML =
                    '<div class="alert alert-danger">Could not load details.</div>';
            }
        });
    }

    // --- LÓGICA DO MODAL "APPROVE PIN" ---
    const approveModal = document.getElementById("approveModal");
    if (approveModal) {
        const form = approveModal.querySelector("#approve-modal-form");
        const formStep = form;
        const processingStep = approveModal.querySelector(
            "#approve-modal-processing",
        );
        const errorMsg = approveModal.querySelector("#pin-error-msg");
        const pinInputs = approveModal.querySelectorAll("#pin-inputs input");
        let actionUrl = ""; // Para guardar a URL de aprovação

        // Auto-focus para os campos de PIN
        pinInputs.forEach((input, index) => {
            input.addEventListener("input", () => {
                if (input.value.length === 1 && index < pinInputs.length - 1) {
                    pinInputs[index + 1].focus();
                }
            });
            input.addEventListener("keydown", (e) => {
                if (e.key === "Backspace" && !input.value && index > 0) {
                    pinInputs[index - 1].focus();
                }
            });
        });

        // 1. Quando o modal abrir, guardar a URL de ação
        approveModal.addEventListener("show.bs.modal", (event) => {
            const button = event.relatedTarget;
            actionUrl = button.dataset.url;

            // 2. Resetar o modal para a etapa de PIN
            formStep.classList.remove("d-none");
            processingStep.classList.add("d-none");
            errorMsg.classList.add("d-none");
            pinInputs.forEach((input) => (input.value = ""));
            pinInputs[0].focus();
        });

        // 3. Quando o formulário de PIN for submetido
        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            const pin = Array.from(pinInputs)
                .map((input) => input.value)
                .join("");
            if (pin.length < 6) {
                errorMsg.textContent = "PIN must be 6 digits.";
                errorMsg.classList.remove("d-none");
                return;
            }

            // 4. Mudar para o estado "Processing"
            formStep.classList.add("d-none");
            processingStep.classList.remove("d-none");
            errorMsg.classList.add("d-none");

            try {
                // 5. Enviar o PIN para o controller
                const response = await fetch(actionUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    body: JSON.stringify({ pin: pin }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || "Invalid request");
                }

                // 6. Sucesso! Fechar o modal e remover a linha da tabela
                const modalInstance = bootstrap.Modal.getInstance(approveModal);
                modalInstance.hide();
                // (Opcional) mostrar um toast de sucesso
                // showToast(data.message, 'success');

                // Remove a linha da tabela da DOM
                const paymentId = actionUrl.split("/")[3]; // Pega o ID da URL
                document.getElementById(`payout-row-${paymentId}`)?.remove();
            } catch (error) {
                // 7. Falha! (Ex: PIN errado)
                // Voltar para a etapa de PIN e mostrar o erro
                formStep.classList.remove("d-none");
                processingStep.classList.add("d-none");
                errorMsg.textContent = error.message;
                errorMsg.classList.remove("d-none");
                pinInputs.forEach((input) => (input.value = ""));
                pinInputs[0].focus();
            }
        });
    }

    // --- LÓGICA DO BOTÃO "CANCEL" ---
    document.body.addEventListener("click", async function (event) {
        if (event.target.matches(".btn-cancel-payout")) {
            if (!confirm("Are you sure you want to cancel this payout?")) {
                return;
            }

            const button = event.target;
            const url = button.dataset.url;
            button.disabled = true;

            try {
                const response = await fetch(url, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        Accept: "application/json",
                    },
                });
                const data = await response.json();

                if (!response.ok) throw new Error(data.message);

                // (Opcional) showToast(data.message, 'success');
                const paymentId = url.split("/")[3];
                document.getElementById(`payout-row-${paymentId}`)?.remove();
            } catch (error) {
                console.error(error);
                // (Opcional) showToast(error.message, 'danger');
                button.disabled = false;
            }
        }
    });
});
