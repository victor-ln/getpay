document.addEventListener("DOMContentLoaded", function () {
    // ✅ Lê o token CSRF da meta tag uma vez
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");

    // Usa delegação de eventos para uma melhor performance
    document.body.addEventListener("change", function (event) {
        // Verifica se o elemento alterado é o nosso interruptor
        if (event.target.matches('.form-check-input[role="switch"]')) {
            const toggle = event.target;
            const url = toggle.dataset.url;
            const isChecked = toggle.checked;

            fetch(url, {
                method: "PATCH",
                headers: {
                    "X-CSRF-TOKEN": csrfToken, // ✅ Usa a variável com o token
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ is_active: isChecked }),
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error("Network response was not ok");
                    }
                    return response.json();
                })
                .then((data) => {
                    if (!data.success) {
                        toggle.checked = !isChecked; // Reverte em caso de erro
                        alert("An error occurred while changing the status.");
                    }
                    // Opcional: mostrar uma notificação de sucesso
                })
                .catch((error) => {
                    console.error("Error:", error);
                    toggle.checked = !isChecked; // Reverte em caso de erro de rede
                    alert("A network error occurred.");
                });
        }
    });
});
