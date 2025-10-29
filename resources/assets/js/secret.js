document.addEventListener("DOMContentLoaded", function () {
    const generateBtn = document.getElementById("generateApiCredentialsBtn");
    const newCredentialsSection = document.getElementById(
        "newCredentialsSection",
    );
    const currentClientIdField = document.getElementById("currentApiClientId");
    const newClientIdField = document.getElementById("newApiClientId");
    const newClientSecretField = document.getElementById("newApiClientSecret");
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");

    // Função para copiar texto para a área de transferência
    function copyToClipboard(elementId) {
        const inputElement = document.getElementById(elementId.substring(1)); // Remove '#' do ID
        if (inputElement && inputElement.value) {
            try {
                // Seleciona o texto dentro do input
                inputElement.select();
                inputElement.setSelectionRange(0, 99999); // Para mobile

                // Tenta copiar usando a API moderna (pode falhar em iFrames)
                // navigator.clipboard.writeText(inputElement.value);

                // Usa o método antigo 'execCommand' que é mais compatível em iFrames
                document.execCommand("copy");

                // Desseleciona o texto
                window.getSelection().removeAllRanges();

                return true; // Indica sucesso
            } catch (err) {
                console.error("Failed to copy text: ", err);
                return false; // Indica falha
            }
        }
        return false;
    }

    // Listener para o botão de gerar credenciais
    if (generateBtn) {
        generateBtn.addEventListener("click", async function () {
            const url = this.dataset.url;
            this.disabled = true;
            this.innerHTML =
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...';
            newCredentialsSection.style.display = "none";

            try {
                const response = await fetch(url, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        Accept: "application/json",
                    },
                });
                const data = await response.json();

                if (data.success) {
                    currentClientIdField.value = data.client_id;
                    newClientIdField.value = data.client_id;
                    newClientSecretField.value = data.client_secret;
                    newCredentialsSection.style.display = "block";
                    alert(
                        "New credentials generated successfully! Copy the Client Secret now.",
                    );
                } else {
                    alert(
                        "Error generating credentials: " +
                            (data.message || "Unknown error"),
                    );
                }
            } catch (error) {
                console.error("Error:", error);
                alert("An error occurred while communicating with the server.");
            } finally {
                this.disabled = false;
                this.textContent = "Generate New Credentials";
            }
        });
    }

    // ✅ [NOVA LÓGICA] Listener para os botões de copiar usando delegação de eventos
    document.body.addEventListener("click", function (event) {
        const copyButton = event.target.closest(".btn-copy"); // Procura pelo botão ou seu ícone interno

        if (copyButton) {
            const targetId = copyButton.dataset.clipboardTarget;
            const originalIcon = copyButton.innerHTML; // Guarda o ícone original

            if (copyToClipboard(targetId)) {
                // Feedback visual de sucesso
                copyButton.innerHTML =
                    '<i class="bx bx-check text-success"></i>'; // Muda para ícone de check
                setTimeout(() => {
                    copyButton.innerHTML = originalIcon; // Volta ao ícone original após 2 segundos
                }, 2000);
            } else {
                // Feedback visual de erro (opcional)
                copyButton.innerHTML = '<i class="bx bx-x text-danger"></i>'; // Muda para ícone de erro
                setTimeout(() => {
                    copyButton.innerHTML = originalIcon;
                }, 2000);
                alert("Could not copy text.");
            }
        }
    });
});
