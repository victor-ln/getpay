$(function () {
    // Seleciona os elementos do formulário usando a sintaxe do jQuery
    const calculationTypeSelect = $("#calculation_type");
    const simpleFixedFields = $("#simple_fixed_fields");
    const basePercentageFields = $("#base_percentage_fields");

    function toggleFields() {
        const selectedType = calculationTypeSelect.val();

        // Esconde os dois containers de campos condicionais
        // Usamos slideUp() para uma animação suave
        simpleFixedFields.slideUp();
        basePercentageFields.slideUp();

        // Mostra o container correto com base na seleção
        if (selectedType === "SIMPLE_FIXED") {
            simpleFixedFields.slideDown();
        } else if (selectedType === "GREATER_OF_BASE_PERCENTAGE") {
            basePercentageFields.slideDown();
        }
    }

    // 1. Executa a função uma vez quando a página carrega para definir o estado inicial correto
    toggleFields();

    // 2. Adiciona um "ouvinte" que executa a função toda vez que o valor do dropdown mudar
    calculationTypeSelect.on("change", toggleFields);
});
