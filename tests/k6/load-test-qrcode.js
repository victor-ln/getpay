import http from "k6/http";
import { check, sleep, fail } from "k6";
import { Rate } from "k6/metrics";
import { b64encode } from "k6/encoding";

// Métricas customizadas
const errorRate = new Rate("errors");

// Configuração de carga progressiva
export const options = {
    stages: [
        { duration: "2m", target: 50 },  // 50 req/s
        { duration: "2m", target: 100 }, // 100 req/s
        { duration: "1m", target: 100 }, // Sustenta 100 req/s
        { duration: "1m", target: 0 },   // Ramp down
    ],
    thresholds: {
        http_req_duration: ["p(95)<500"], // 95% das requests < 500ms
        http_req_failed: ["rate<0.01"], // < 1% de erros HTTP
        errors: ["rate<0.05"], // < 5% de erros de validação
    },
};

// --- Configuração da API GetPay ---
const BASE_URL =
    __ENV.BASE_URL || "https://app-getpay-prod-3-staging.azurewebsites.net";

export function setup() {
    // Gera token Basic Auth
    // const basicAuth = `Basic ${b64encode(API_USER + ":" + API_PASS)}`;
    const basicAuth = `Bearer 1433422|K6aazxCj094kbjOy7X6FpyL0CKIhLLSxRmtRqyyce2ea07a1`;

    console.log("✅ Autenticação configurada com sucesso!");

    return { authToken: basicAuth };
}

export default function (data) {
    const authToken = data.authToken;

    // Gera dados únicos para cada request
    const externalId = `k6_getpay_${Date.now()}_${__VU}_${__ITER}`;
    const amount = (Math.random() * 15 + 5).toFixed(2); // 5.00 a 20.00

    const payload = JSON.stringify({
        externalId: externalId,
        amount: parseFloat(amount),
        document: "25689754895",
        name: "Getpay k6 Test",
        identification: "",
        expire: 3600,
        description: "k6 load test transaction",
    });

    const params = {
        headers: {
            "Content-Type": "application/json",
            Authorization: authToken,
            Accept: "application/json",
        },
        tags: {
            name: "qrcode",
            endpoint: "/api/create-payment",
        },
    };

    // Request principal
    const response = http.post(`${BASE_URL}/api/create-payment`, payload, params);

    // Validações
    const success = check(response, {
        "status é 2XX": (r) => r.status >= 200 && r.status < 300,
        "resposta tem JSON válido": (r) => {
            try {
                const json = r.json();
                return json !== null;
            } catch (e) {
                console.error(`Erro ao parsear JSON: ${e}`);
                return false;
            }
        },
    });

    // Log de erros
    if (!success) {
        console.error(
            `❌ Falha na request: ${response.status} - ${response.body.substring(0, 200)}`,
        );
    }

    errorRate.add(!success);

    // Simula comportamento real de usuário (1-3 segundos)
    sleep(Math.random() * 2 + 1);
}

export function teardown(data) {
    console.log("=== Teste de Carga GetPay PayIn Finalizado! ===");
}
