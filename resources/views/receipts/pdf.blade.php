<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprovante de Transferência PIX</title>
    <style>
        /* Define as fontes e o box-sizing para um layout consistente */
        * {
            box-sizing: border-box;
            font-family: 'Helvetica', 'Arial', sans-serif;
        }

        body {
            background-color: #e0e0e0;
            /* Fundo cinza claro para destacar o comprovante */
            margin: 0;
            padding: 20px;
        }

        /* O container principal que simula a folha do comprovante */
        .receipt-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            /* Sombra para dar profundidade */
            border-left: 1px solid #dcdcdc;
            border-right: 1px solid #dcdcdc;
            position: relative;
        }

        /* Área de conteúdo principal dentro do container */
        .content-area {
            padding: 25px 35px;
        }

        /* BORDAS ONDULADAS - A MÁGICA ACONTECE AQUI */
        .receipt-container::before,
        .receipt-container::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            height: 20px;
            /* Altura da onda */
            background-size: 40px 20px;
            /* Largura e altura de cada "dente" da onda */
            background-repeat: repeat-x;
        }

        /* Borda ondulada superior */
        .receipt-container::before {
            top: -20px;
            /* Posiciona acima do container */
            background-image: radial-gradient(circle at 20px 0, transparent 20px, #ffffff 20px);
        }

        /* Borda ondulada inferior */
        .receipt-container::after {
            bottom: -20px;
            /* Posiciona abaixo do container */
            background-image: radial-gradient(circle at 20px 20px, #ffffff 20px, transparent 20px);
        }

        /* Estilos do cabeçalho */
        .receipt-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px dashed #dcdcdc;
        }

        .receipt-header img {
            max-height: 50px;
            margin-bottom: 10px;
        }

        .receipt-header .company-details {
            font-size: 0.8rem;
            color: #666;
        }

        /* Área de status e valor principal */
        .status-area {
            text-align: center;
            padding: 25px 0;
        }

        .status-area .transfer-amount {
            font-size: 2.5rem;
            font-weight: bold;
            color: #28a745;
            /* Verde para sucesso */
            margin: 5px 0;
        }

        .status-area .date-time {
            font-size: 0.9rem;
            color: #666;
        }

        /* Seções de detalhes (Beneficiário, Pagador, etc) */
        .details-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .details-section h6 {
            font-size: 0.75rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 10px 0;
        }

        .details-section .info-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .details-section .info-line span {
            color: #333;
        }

        .details-section .info-line strong {
            font-weight: 600;
            text-align: right;
        }

        /* Quebra de linha para IDs longos */
        .text-break {
            word-break: break-all;
        }

        #pdf-receiver-name {
            text-transform: uppercase;
            text-align: right;
        }
    </style>
</head>

<body>

    <div class="receipt-container">
        <div class="content-area">
            <div class="receipt-header">
                <img src="https://i.imgur.com/your-logo.png" alt="Logo da Empresa">
                <div class="company-details">
                    <strong>GETPAY LTDA</strong><br>
                    CNPJ: 00.000.000/0001-00
                </div>
            </div>

            <div class="status-area">
                <p>Transferência PIX realizada com sucesso</p>
                <div class="transfer-amount">R$ <span id="pdf-amount">{{ number_format($payment->amount, 2, ',', '.') }}</span></div>
                <div class="date-time">
                    em <span id="pdf-date">{{ $payment->created_at->format('d/m/Y') }}</span> às <span id="pdf-time">{{ $payment->created_at->format('H:i:s') }}</span>
                </div>
            </div>

            <div class="details-section">
                <h6>PARA (BENEFICIÁRIO)</h6>
                <div class="info-line">
                    <span>Nome</span>
                    <strong id="pdf-receiver-name">{{ $payment->user->name }}</strong>
                </div>
                <div class="info-line">
                    <span>Documento</span>
                    <strong id="pdf-receiver-document">
                        @php
                        $doc = preg_replace('/\D/', '', $payment->document);
                        if (strlen($doc) == 11) { // Verifica se é CPF
                        $masked = '***.' . substr($doc, 3, 3) . '.' . substr($doc, 6, 3) . '-**';
                        } else {
                        $masked = $payment->document; // Se não for CPF, mostra original
                        }
                        @endphp
                        {{ $masked }}
                    </strong>
                </div>
                <div class="info-line">
                    <span>Instituição</span>
                    <strong id="pdf-receiver-institution"> --- </strong>
                </div>
            </div>

            <div class="details-section">
                <h6>DE (PAGADOR)</h6>
                <div class="info-line">
                    <span>Nome</span>
                    <strong id="pdf-payer-name"> GETPAY </strong>
                </div>
                <div class="info-line">
                    <span>Instituição</span>
                    <strong id="pdf-payer-institution"> --- </strong>
                </div>
            </div>

            <div class="details-section">
                <h6>AUTENTICAÇÃO</h6>
                <div class="info-line">
                    <span>ID da Transação: </span>
                    <strong id="pdf-transaction-id" class="text-break">{{ $payment->external_payment_id }}</strong>
                </div>
                <div class="info-line">
                    <span>ID (E2E): </span>
                    <strong id="pdf-e2e-id" class="text-break">{{ $payment->end_to_end_id }}</strong>
                </div>
            </div>
        </div>
    </div>

</body>

</html>