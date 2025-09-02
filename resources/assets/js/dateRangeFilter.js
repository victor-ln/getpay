

/**
 * Inicializa a lógica para mostrar/ocultar os campos de data customizada.
 */
function initializeDateRangeFilter() {
    const dateFilterSelect = document.getElementById('date_filter_select');
    const customDateFields = document.getElementById('custom_date_range_fields');

    // VERIFICAÇÃO DE SEGURANÇA: Só executa o resto do código se os elementos existirem nesta página.
    if (!dateFilterSelect || !customDateFields) {
        return;
    }

    function toggleCustomDateFields() {
        if (dateFilterSelect.value === 'custom') {
            customDateFields.style.display = 'block';
        } else {
            customDateFields.style.display = 'none';
        }
    }

    // Executa a função quando o script carrega para definir o estado inicial correto.
    toggleCustomDateFields();

    // Adiciona o listener para futuras mudanças.
    dateFilterSelect.addEventListener('change', toggleCustomDateFields);
}

// Chama a função para inicializar o filtro.
initializeDateRangeFilter();