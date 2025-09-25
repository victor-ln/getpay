<div class="offcanvas offcanvas-end" tabindex="-1" id="transactionReceiptOffcanvas" aria-labelledby="transactionReceiptLabel">
    <div class="offcanvas-header border-bottom">
        <h5 id="transactionReceiptLabel" class="offcanvas-title">Comprovante de Transferência PIX</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        {{-- Estado de Carregamento --}}
        <div id="receipt-loading-state" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Carregando detalhes...</p>
        </div>

        {{-- ✅ LAYOUT PIX SIMPLIFICADO (começa escondido) --}}
        <div id="receipt-content-state" class="d-none">

            {{-- 1. CABEÇALHO COM LOGO E DADOS DA EMPRESA --}}
            <div class="receipt-header text-center mb-4">
                {{-- Coloque o caminho para o seu logo aqui --}}
                <img src="https://via.placeholder.com/150x50.png?text=Seu+Logo" alt="Logo da Empresa" style="max-height: 50px;" class="mb-3">
                <div class="company-details text-muted small">
                    <p class="mb-0"><strong>GETPAY LTDA</strong></p>
                    <p class="mb-0">CNPJ: 00.000.000/0001-00</p>
                </div>
            </div>

            {{-- 2. STATUS E VALOR PRINCIPAL DA TRANSFERÊNCIA --}}
            <div class="text-center mb-4 pb-4 border-bottom">
                <p class="text-muted mb-1">Transferência PIX realizada com sucesso</p>
                <h1 class="display-5 fw-bold text-success mb-1">R$ <span id="receipt-amount">--</span></h1>
                <p class="text-muted small">
                    em <span id="receipt-date">--</span> às <span id="receipt-time">--</span>
                </p>
            </div>

            {{-- 3. INFORMAÇÕES DO BENEFICIÁRIO (QUEM RECEBE) --}}
            <div class="receipt-section">
                <h6 class="text-muted small-caps">PARA (BENEFICIÁRIO)</h6>
                <ul class="list-unstyled mb-0">
                    <li class="d-flex justify-content-between">
                        <span>Nome </span>
                        <strong id="receipt-receiver-name">--</strong>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span>Documento</span>
                        <strong id="receipt-receiver-document">--</strong>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span>Instituição</span>
                        <strong id="receipt-receiver-institution">--</strong>
                    </li>
                </ul>
            </div>

            {{-- 4. INFORMAÇÕES DO PAGADOR (QUEM ENVIA) --}}
            <div class="receipt-section">
                <h6 class="text-muted small-caps">DE (PAGADOR)</h6>
                <ul class="list-unstyled mb-0">
                    <li class="d-flex justify-content-between">
                        <span>Nome </span>
                        <strong id="receipt-payer-name">--</strong>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span>Instituição</span>
                        <strong id="receipt-payer-institution">--</strong>
                    </li>
                </ul>
            </div>

            {{-- 5. DETALHES TÉCNICOS DA TRANSAÇÃO --}}
            <div class="receipt-section">
                <h6 class="text-muted small-caps">AUTENTICAÇÃO</h6>
                <ul class="list-unstyled mb-0">
                    <li class="d-flex justify-content-between">
                        <span>ID da Transação</span>
                        <strong id="receipt-transaction-id">--</strong>
                    </li>
                    <li class="d-flex justify-content-between text-break">
                        <span>ID End-to-End</span>
                        <strong id="receipt-e2e-id" class="text-end" style="max-width: 70%;">--</strong>
                    </li>
                </ul>
            </div>

        </div>
    </div>
    <div class="offcanvas-footer p-3 border-top bg-light">
        <a href="#" id="receipt-download-link" class="btn btn-primary w-100" target="_blank">
            <i class="bx bx-download me-1"></i> DOWNLOAD (PDF)
        </a>
    </div>
</div>

{{-- Adicione este CSS ao seu arquivo de estilos principal para os pequenos ajustes --}}
<style>
    .small-caps {
        text-transform: uppercase;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }

    .receipt-section {
        padding: 1rem 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .receipt-section:last-of-type {
        border-bottom: none;
    }

    .receipt-section ul li {
        padding: 0.25rem 0;
    }

    #receipt-payer-name,
    #receipt-transaction-id {
        text-align: right;
    }
</style>