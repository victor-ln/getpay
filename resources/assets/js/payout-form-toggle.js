document.addEventListener("DOMContentLoaded", function () {
    // Seleciona os elementos
    const registeredRadio = document.getElementById("useRegisteredKey");
    const manualRadio = document.getElementById("useManualKey");
    const registeredBlock = document.getElementById("registeredKeyBlock");
    const manualBlock = document.getElementById("manualKeyBlock");

    // Seleciona os campos de formulário para validação
    const registeredSelect = document.getElementById("pix-key-select");
    const manualType = document.getElementById("manual-pix-key-type");
    const manualValue = document.getElementById("manual-pix-key-value");

    function togglePayoutMethod() {
        if (manualRadio.checked) {
            // Mostra o bloco manual e esconde o registado
            registeredBlock.style.display = "none";
            manualBlock.style.display = "block";

            // Ajusta a validação: campos manuais são obrigatórios
            registeredSelect.required = false;
            manualType.required = true;
            manualValue.required = true;
        } else {
            // Mostra o bloco registado e esconde o manual
            registeredBlock.style.display = "block";
            manualBlock.style.display = "none";

            // Ajusta a validação: select registado é obrigatório
            registeredSelect.required = true;
            manualType.required = false;
            manualValue.required = false;
        }
    }

    // Adiciona os "ouvintes" de evento
    registeredRadio.addEventListener("change", togglePayoutMethod);
    manualRadio.addEventListener("change", togglePayoutMethod);

    // Garante que o estado correto é mostrado no carregamento da página (caso o browser guarde a seleção)
    togglePayoutMethod();
});
