import axios from "axios";

$(document).ready(function () {
    const periodFilter = $("#periodFilter");

    function updateMetrics(period, accountId) {
        // Validação final para garantir que não fazemos chamadas inválidas
        if (!period || !accountId) {
            console.error(
                "Faltando período ou ID da conta. Abortando a chamada de API.",
            );
            return;
        }

        $("#periodIndicator").html(
            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Carregando...',
        );

        axios
            .get(`/dashboard/metrics?period=${period}&account_id=${accountId}`)
            .then((response) => {
                // ... (Sua lógica de sucesso para atualizar os cards, que já está correta)
            })
            .catch((error) => {
                console.error("Erro ao buscar métricas:", error);
                // ... (Sua lógica de erro, que já está correta)
            });
    }

    // --- LÓGICA DE EVENTOS E CARREGAMENTO (SIMPLIFICADA) ---

    // 1. Ouve o evento de 'change' no seletor de período
    periodFilter.on("change", function () {
        const selectedPeriod = $(this).val();
        // Lê o ID da conta diretamente do atributo 'data-account-id' do seletor
        const accountId = $(this).data("account-id");
        updateMetrics(selectedPeriod, accountId);
    });

    // 2. Carrega os dados iniciais ao abrir a página
    // O jQuery automaticamente dispara o evento 'change' na primeira carga,
    // então o código acima já cuidará do carregamento inicial.
    // Para garantir, podemos acioná-lo manualmente.
    periodFilter.trigger("change");
});
