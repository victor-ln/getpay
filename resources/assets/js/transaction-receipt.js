/**
 * Lógica para buscar e exibir o comprovante de transação no Offcanvas.
 * Versão com novo layout de recibo.
 */
import axios from "axios";

document.addEventListener("DOMContentLoaded", function () {
    const receiptOffcanvasElement = document.getElementById(
        "transactionReceiptOffcanvas",
    );
    if (!receiptOffcanvasElement) return;

    const receiptOffcanvas = new bootstrap.Offcanvas(receiptOffcanvasElement);
    const loadingState = document.getElementById("receipt-loading-state");
    const contentState = document.getElementById("receipt-content-state");

    // Mapeamento dos IDs do HTML para as chaves do JSON da API
    const receiptFields = {
        "receipt-amount": "amount",
        "receipt-date": "date",
        "receipt-time": "time",
        "receipt-status-text": "status",
        "receipt-transaction-id": "transaction_id",
        "receipt-e2e-id": "end_to_end_id",
        "receipt-payer-name": "payer.name",
        "receipt-receiver-name": "receiver.name",
        "receipt-receiver-document": "receiver.document",
    };

    document.body.addEventListener("click", async function (event) {
        const receiptButton = event.target.closest(".view-receipt-btn");
        if (!receiptButton) return;

        const paymentId = receiptButton.dataset.paymentId;
        if (!paymentId) return;

        loadingState.classList.remove("d-none");
        contentState.classList.add("d-none");
        receiptOffcanvas.show();

        try {
            const response = await axios.get(`/payments/${paymentId}/receipt`);
            const receiptData = response.data.receipt;

            // Preenche todos os campos de texto do comprovante
            for (const [elementId, dataKey] of Object.entries(receiptFields)) {
                const element = document.getElementById(elementId);
                if (element) {
                    const value = dataKey
                        .split(".")
                        .reduce((o, i) => (o ? o[i] : null), receiptData);
                    element.textContent = value || "--";
                }
            }
            document.getElementById("receipt-download-link").href =
                `/payments/${paymentId}/download`;
            loadingState.classList.add("d-none");
            contentState.classList.remove("d-none");
        } catch (error) {
            console.error("Failed to load transaction receipt:", error);
            loadingState.innerHTML =
                '<p class="text-center text-danger p-5">Failed to load details. Please try again.</p>';
        }
    });
});
