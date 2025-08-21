(() => {
    const copiarTexto = () => {
        const input = document.getElementById("base-url");
        navigator.clipboard
            .writeText(input.value)
            .then(() => {
                const icone = document.querySelector(".bx-copy");
                icone.classList.remove("bx-copy");
                icone.classList.add("bx-check");
                setTimeout(() => {
                    icone.classList.remove("bx-check");
                    icone.classList.add("bx-copy");
                }, 2000);
            })
            .catch((err) => {
                console.error("Erro ao copiar texto: ", err);
            });
    };

    // Adicione o evento de clique ao ícone
    document.querySelector(".bx-copy").addEventListener("click", copiarTexto);
})();

(() => {
    document.querySelectorAll(".toggle-token").forEach((toggle) => {
        toggle.addEventListener("click", () => {
            const id = toggle.dataset.id;
            const input = document.getElementById(`token-${id}`);
            input.type = input.type === "password" ? "text" : "password";
        });
    });
})();
