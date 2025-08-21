<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayFlow Pro - Documentação de API</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --dark: #0f172a;
            --darker: #020617;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--darker);
            color: #e2e8f0;
        }

        .gradient-bg {
            background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 100%);
        }

        .sidebar {
            scrollbar-width: thin;
            scrollbar-color: #4b5563 var(--dark);
        }

        .sidebar::-webkit-scrollbar {
            width: 8px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: var(--dark);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: #4b5563;
            border-radius: 4px;
        }

        .code-block {
            font-family: 'Fira Code', monospace;
            background-color: #1e293b;
            border-radius: 0.5rem;
            border-left: 4px solid var(--primary);
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .tab-content.active {
            display: block;
        }

        .endpoint-method {
            width: 90px;
            font-family: 'Fira Code', monospace;
        }

        .copy-btn {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .copy-btn:hover {
            background-color: #334155;
            transform: translateY(-1px);
        }

        .copy-btn.copied {
            background-color: #10b981;
        }

        .nav-item {
            position: relative;
            transition: all 0.2s;
        }

        .nav-item:hover {
            background-color: rgba(99, 102, 241, 0.1);
        }

        .nav-item.active {
            background-color: rgba(99, 102, 241, 0.2);
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background-color: var(--primary);
            border-radius: 0 3px 3px 0;
        }

        .method-get {
            background-color: #10b981;
        }

        .method-post {
            background-color: #3b82f6;
        }

        .method-put {
            background-color: #f59e0b;
        }

        .method-delete {
            background-color: #ef4444;
        }

        .method-patch {
            background-color: #8b5cf6;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
        }

        .badge-new {
            background-color: #ec4899;
            color: white;
        }

        .badge-updated {
            background-color: #f59e0b;
            color: black;
        }

        .parameter-required::after {
            content: ' *';
            color: #ef4444;
        }

        .floating-action-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s;
            z-index: 50;
        }

        .floating-action-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .dark-highlight {
            background-color: rgba(99, 102, 241, 0.1);
            border-left: 3px solid var(--primary);
            padding-left: 1rem;
            margin-left: -1rem;
        }
    </style>
</head>

<body class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <div class="sidebar w-72 bg-slate-900 overflow-y-auto flex-shrink-0 border-r border-slate-800">
        <div class="p-6 sticky top-0 gradient-bg z-10 border-b border-slate-800">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-600 flex items-center justify-center">
                    <i class="fas fa-bolt text-white text-lg"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold">PayFlow Pro</h1>
                    <p class="text-slate-400 text-xs">API v2.2.0</p>
                </div>
            </div>

            <div class="mt-6 relative">
                <input type="text" placeholder="Buscar na documentação..."
                    class="w-full bg-slate-800 border border-slate-700 rounded-lg py-2 px-4 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <i class="fas fa-search absolute right-3 top-2.5 text-slate-500"></i>
            </div>
        </div>

        <nav class="mt-2 px-4 pb-10">
            <div class="mt-2">
                <h2 class="text-slate-400 uppercase text-xs font-semibold tracking-wider px-2 py-2 flex items-center">
                    <i class="fas fa-home mr-2"></i> Introdução
                </h2>
                <a href="#overview"
                    class="nav-item flex items-center px-3 py-2 rounded-lg text-slate-300 hover:text-white">
                    <i class="fas fa-info-circle mr-3 text-slate-400"></i> Visão Geral
                </a>
                <a href="#authentication"
                    class="nav-item flex items-center px-3 py-2 rounded-lg text-slate-300 hover:text-white">
                    <i class="fas fa-key mr-3 text-slate-400"></i> Autenticação
                </a>
                <a href="#rate-limiting"
                    class="nav-item flex items-center px-3 py-2 rounded-lg text-slate-300 hover:text-white">
                    <i class="fas fa-tachometer-alt mr-3 text-slate-400"></i> Limites de Requisição
                </a>
                <a href="#errors"
                    class="nav-item flex items-center px-3 py-2 rounded-lg text-slate-300 hover:text-white">
                    <i class="fas fa-exclamation-circle mr-3 text-slate-400"></i> Tratamento de Erros
                </a>
            </div>

            <div class="mt-6">
                <h2 class="text-slate-400 uppercase text-xs font-semibold tracking-wider px-2 py-2 flex items-center">
                    <i class="fas fa-plug mr-2"></i> Endpoints
                </h2>
                <a href="#payments"
                    class="nav-item flex items-center px-3 py-2 rounded-lg text-slate-300 hover:text-white">
                    <i class="fas fa-credit-card mr-3 text-slate-400"></i> Pagamentos
                    <span class="badge badge-new ml-auto">Novo</span>
                </a>
                <a href="#refunds"
                    class="nav-item flex items-center px-3 py-2 rounded-lg text-slate-300 hover:text-white">
                    <i class="fas fa-exchange-alt mr-3 text-slate-400"></i> Reembolsos
                </a>
                <a href="#customers"
                    class="nav-item flex items-center px-3 py-2 rounded-lg text-slate-300 hover:text-white">
                    <i class="fas fa-users mr-3 text-slate-400"></i> Clientes
                </a>
                <a href="#subscriptions"
                    class="nav-item flex items-center px-3 py-2 rounded-lg text-slate-300 hover:text-white">
                    <i class="fas fa-calendar-alt mr-3 text-slate-400"></i> Assinaturas
                    <span class="badge badge-updated ml-auto">Atualizado</span>
                </a>
                <a href="#webhooks"
                    class="nav-item flex items-center px-3 py-2 rounded-lg text-slate-300 hover:text-white">
                    <i class="fas fa-bell mr-3 text-slate-400"></i> Webhooks
                </a>
                <a href="#invoices"
                    class="nav-item flex items-center px-3 py-2 rounded-lg text-slate-300 hover:text-white">
                    <i class="fas fa-file-invoice mr-3 text-slate-400"></i> Faturas
                </a>
            </div>

            <div class="mt-6">
                <h2 class="text-slate-400 uppercase text-xs font-semibold tracking-wider px-2 py-2 flex items-center">
                    <i class="fas fa-code mr-2"></i> SDKs & Bibliotecas
                </h2>
                <a href="#javascript"
                    class="nav-item flex items-center px-3 py-2 rounded-lg text-slate-300 hover:text-white">
                    <i class="fab fa-js mr-3 text-yellow-400"></i> JavaScript
                </a>
                <a href="#python"
                    class="nav-item flex items-center px-3 py-2 rounded-lg text-slate-300 hover:text-white">
                    <i class="fab fa-python mr-3 text-blue-400"></i> Python
                </a>
                <a href="#php" class="nav-item flex items-center px-3 py-2 rounded-lg text-slate-300 hover:text-white">
                    <i class="fab fa-php mr-3 text-purple-400"></i> PHP
                </a>
                <a href="#java" class="nav-item flex items-center px-3 py-2 rounded-lg text-slate-300 hover:text-white">
                    <i class="fab fa-java mr-3 text-red-400"></i> Java
                </a>
            </div>

            <div class="mt-6">
                <h2 class="text-slate-400 uppercase text-xs font-semibold tracking-wider px-2 py-2 flex items-center">
                    <i class="fas fa-book mr-2"></i> Recursos
                </h2>
                <a href="#support"
                    class="nav-item flex items-center px-3 py-2 rounded-lg text-slate-300 hover:text-white">
                    <i class="fas fa-headset mr-3 text-slate-400"></i> Suporte
                </a>
                <a href="#changelog"
                    class="nav-item flex items-center px-3 py-2 rounded-lg text-slate-300 hover:text-white">
                    <i class="fas fa-history mr-3 text-slate-400"></i> Changelog
                </a>
                <a href="#faq" class="nav-item flex items-center px-3 py-2 rounded-lg text-slate-300 hover:text-white">
                    <i class="fas fa-question-circle mr-3 text-slate-400"></i> FAQ
                </a>
            </div>
        </nav>
    </div>

    <!-- Main content -->
    <div class="flex-1 overflow-y-auto relative">
        <!-- Floating action button -->
        <a href="#support" class="floating-action-btn">
            <i class="fas fa-question text-xl"></i>
        </a>

        <div class="max-w-5xl mx-auto px-8 py-10">
            <!-- Overview section -->
            <section id="overview" class="mb-20 scroll-mt-20">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1
                            class="text-3xl font-bold bg-gradient-to-r from-indigo-400 to-purple-600 bg-clip-text text-transparent">
                            PayFlow Pro API</h1>
                        <p class="text-slate-400 mt-2">Solução completa para processamento de pagamentos</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs bg-slate-800 text-slate-300 px-3 py-1 rounded-full">Versão 2.2.0</span>
                        <span class="text-xs bg-green-900 text-green-300 px-3 py-1 rounded-full">Ativo</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                    <div
                        class="bg-slate-800 p-6 rounded-xl border border-slate-700 hover:border-indigo-500 transition-all">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 rounded-lg bg-indigo-500/20 flex items-center justify-center mr-3">
                                <i class="fas fa-bolt text-indigo-400"></i>
                            </div>
                            <h3 class="font-semibold">Rápido e Seguro</h3>
                        </div>
                        <p class="text-slate-400">Processamento de pagamentos em menos de 2 segundos com criptografia de
                            nível bancário.</p>
                    </div>
                    <div
                        class="bg-slate-800 p-6 rounded-xl border border-slate-700 hover:border-indigo-500 transition-all">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 rounded-lg bg-indigo-500/20 flex items-center justify-center mr-3">
                                <i class="fas fa-globe text-indigo-400"></i>
                            </div>
                            <h3 class="font-semibold">Global</h3>
                        </div>
                        <p class="text-slate-400">Suporte a mais de 135 moedas e todos os principais métodos de
                            pagamento.</p>
                    </div>
                    <div
                        class="bg-slate-800 p-6 rounded-xl border border-slate-700 hover:border-indigo-500 transition-all">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 rounded-lg bg-indigo-500/20 flex items-center justify-center mr-3">
                                <i class="fas fa-sliders-h text-indigo-400"></i>
                            </div>
                            <h3 class="font-semibold">Flexível</h3>
                        </div>
                        <p class="text-slate-400">API RESTful com SDKs para todas as principais linguagens de
                            programação.</p>
                    </div>
                </div>

                <div class="bg-slate-800/50 p-6 rounded-xl border border-slate-700 mb-8">
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <i class="fas fa-link mr-3 text-indigo-400"></i> URL Base
                    </h3>
                    <div class="flex items-center justify-between bg-slate-900 p-4 rounded-lg border border-slate-700">
                        <div class="flex items-center">
                            <span
                                class="bg-indigo-500/10 text-indigo-400 px-2 py-1 rounded text-xs mr-3">Produção</span>
                            <code class="text-indigo-300 font-mono">https://api.payflowpro.com/v2</code>
                        </div>
                        <button
                            class="copy-btn bg-slate-700 hover:bg-slate-600 px-3 py-1.5 rounded-lg text-sm flex items-center">
                            <i class="fas fa-copy mr-2"></i> Copiar
                        </button>
                    </div>
                    <div
                        class="flex items-center justify-between bg-slate-900 p-4 rounded-lg border border-slate-700 mt-3">
                        <div class="flex items-center">
                            <span class="bg-yellow-500/10 text-yellow-400 px-2 py-1 rounded text-xs mr-3">Sandbox</span>
                            <code class="text-yellow-300 font-mono">https://api.sandbox.payflowpro.com/v2</code>
                        </div>
                        <button
                            class="copy-btn bg-slate-700 hover:bg-slate-600 px-3 py-1.5 rounded-lg text-sm flex items-center">
                            <i class="fas fa-copy mr-2"></i> Copiar
                        </button>
                    </div>
                </div>

                <div class="mt-10">
                    <h3 class="text-2xl font-semibold mb-6 flex items-center">
                        <i class="fas fa-rocket mr-3 text-indigo-400"></i> Primeiros Passos
                    </h3>

                    <div class="space-y-6">
                        <div class="flex items-start">
                            <div
                                class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center mr-4 mt-1">
                                <span class="text-indigo-400 font-bold">1</span>
                            </div>
                            <div>
                                <h4 class="font-medium mb-1">Crie sua conta</h4>
                                <p class="text-slate-400">Registre-se em <a href="#"
                                        class="text-indigo-400 hover:underline">PayFlow Pro Dashboard</a> para obter
                                    suas chaves de API.</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div
                                class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center mr-4 mt-1">
                                <span class="text-indigo-400 font-bold">2</span>
                            </div>
                            <div>
                                <h4 class="font-medium mb-1">Configure seu ambiente</h4>
                                <p class="text-slate-400">Use o modo sandbox para testar sem custos antes de ir para
                                    produção.</p>
                                <div class="mt-2 code-block p-4 rounded">
                                    <pre class="text-slate-300 overflow-x-auto text-sm">
# Configure a base URL
PAYFLOW_API_BASE = "https://api.sandbox.payflowpro.com/v2"
PAYFLOW_API_KEY = "sk_test_4eC39HqLyjWDarjtT1zdp7dc"</pre>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div
                                class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center mr-4 mt-1">
                                <span class="text-indigo-400 font-bold">3</span>
                            </div>
                            <div>
                                <h4 class="font-medium mb-1">Faça sua primeira requisição</h4>
                                <p class="text-slate-400">Teste a API com um pagamento simulado.</p>
                                <div class="mt-2 code-block p-4 rounded">
                                    <pre class="text-slate-300 overflow-x-auto text-sm">
curl -X POST https://api.sandbox.payflowpro.com/v2/payments \
  -H "Authorization: Bearer sk_test_4eC39HqLyjWDarjtT1zdp7dc" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 1000,
    "currency": "brl",
    "source": "tok_visa",
    "description": "Pagamento de teste"
  }'</pre>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div
                                class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center mr-4 mt-1">
                                <span class="text-indigo-400 font-bold">4</span>
                            </div>
                            <div>
                                <h4 class="font-medium mb-1">Implemente na sua aplicação</h4>
                                <p class="text-slate-400">Use um de nossos SDKs para integrar facilmente.</p>
                                <div class="mt-2 flex space-x-3">
                                    <button class="bg-slate-700 hover:bg-slate-600 px-3 py-1 rounded text-sm">
                                        <i class="fab fa-js mr-1 text-yellow-400"></i> JavaScript
                                    </button>
                                    <button class="bg-slate-700 hover:bg-slate-600 px-3 py-1 rounded text-sm">
                                        <i class="fab fa-python mr-1 text-blue-400"></i> Python
                                    </button>
                                    <button class="bg-slate-700 hover:bg-slate-600 px-3 py-1 rounded text-sm">
                                        <i class="fab fa-php mr-1 text-purple-400"></i> PHP
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Authentication section -->
            <section id="authentication" class="mb-20 scroll-mt-20">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold flex items-center">
                        <i class="fas fa-key mr-3 text-indigo-400"></i> Autenticação
                    </h2>
                    <span class="text-xs bg-slate-800 text-slate-300 px-3 py-1 rounded-full">Segurança Nível 1 PCI
                        DSS</span>
                </div>

                <p class="text-slate-300 mb-6">
                    Todas as requisições à API PayFlow Pro devem incluir seu token de API no cabeçalho de autorização.
                    Recomendamos armazenar suas chaves de API com segurança e nunca compartilhá-las publicamente.
                </p>

                <div class="bg-slate-800/50 p-6 rounded-xl border border-slate-700 mb-8">
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <i class="fas fa-lock mr-3 text-indigo-400"></i> Chaves de API
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-slate-900 p-5 rounded-lg border border-slate-700">
                            <div class="flex items-center mb-3">
                                <div class="w-3 h-3 rounded-full bg-green-400 mr-2"></div>
                                <h4 class="font-medium">Chave Secreta</h4>
                            </div>
                            <p class="text-slate-400 text-sm mb-4">
                                Use esta chave para requisições do servidor. Nunca exponha no cliente-side.
                            </p>
                            <div
                                class="flex items-center justify-between bg-slate-800 p-3 rounded border border-slate-700">
                                <code class="text-slate-300 font-mono text-sm">sk_live_4eC39HqLyjWDarjtT1zdp7dc</code>
                                <button class="copy-btn bg-slate-700 hover:bg-slate-600 p-1.5 rounded text-sm">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <p class="text-xs text-red-400 mt-2">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Esta chave tem acesso completo à sua
                                conta
                            </p>
                        </div>

                        <div class="bg-slate-900 p-5 rounded-lg border border-slate-700">
                            <div class="flex items-center mb-3">
                                <div class="w-3 h-3 rounded-full bg-blue-400 mr-2"></div>
                                <h4 class="font-medium">Chave Pública</h4>
                            </div>
                            <p class="text-slate-400 text-sm mb-4">
                                Use esta chave no cliente-side para tokenizar cartões de crédito de forma segura.
                            </p>
                            <div
                                class="flex items-center justify-between bg-slate-800 p-3 rounded border border-slate-700">
                                <code class="text-slate-300 font-mono text-sm">pk_live_4eC39HqLyjWDarjtT1zdp7dc</code>
                                <button class="copy-btn bg-slate-700 hover:bg-slate-600 p-1.5 rounded text-sm">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <p class="text-xs text-green-400 mt-2">
                                <i class="fas fa-check-circle mr-1"></i> Segura para uso no navegador
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-800/50 p-6 rounded-xl border border-slate-700 mb-8">
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <i class="fas fa-code mr-3 text-indigo-400"></i> Exemplo de Autenticação
                    </h3>

                    <div class="flex border-b border-slate-700 mb-4">
                        <button class="tab-btn active px-4 py-2 font-medium text-sm" data-tab="auth-curl">cURL</button>
                        <button class="tab-btn px-4 py-2 font-medium text-sm" data-tab="auth-js">JavaScript</button>
                        <button class="tab-btn px-4 py-2 font-medium text-sm" data-tab="auth-python">Python</button>
                        <button class="tab-btn px-4 py-2 font-medium text-sm" data-tab="auth-php">PHP</button>
                    </div>

                    <div id="auth-curl" class="tab-content active">
                        <div class="code-block p-4 rounded mb-4">
                            <pre class="text-slate-300 overflow-x-auto text-sm">
curl -X GET https://api.payflowpro.com/v2/payments \
  -H "Authorization: Bearer sk_test_4eC39HqLyjWDarjtT1zdp7dc" \
  -H "Content-Type: application/json"</pre>
                        </div>
                        <button
                            class="copy-btn bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-lg text-sm flex items-center">
                            <i class="fas fa-copy mr-2"></i> Copiar cURL
                        </button>
                    </div>

                    <div id="auth-js" class="tab-content">
                        <div class="code-block p-4 rounded mb-4">
                            <pre class="text-slate-300 overflow-x-auto text-sm">
// Usando a biblioteca oficial PayFlow.js
const payflow = require('payflow-js');

payflow.configure({
  apiKey: 'sk_test_4eC39HqLyjWDarjtT1zdp7dc'
});

// Listar pagamentos
const payments = await payflow.payments.list();</pre>
                        </div>
                        <button
                            class="copy-btn bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-lg text-sm flex items-center">
                            <i class="fas fa-copy mr-2"></i> Copiar Código
                        </button>
                    </div>

                    <div id="auth-python" class="tab-content">
                        <div class="code-block p-4 rounded mb-4">
                            <pre class="text-slate-300 overflow-x-auto text-sm">
import payflow

# Configurar a chave da API
payflow.api_key = "sk_test_4eC39HqLyjWDarjtT1zdp7dc"

# Listar pagamentos
payments = payflow.Payment.list()</pre>
                        </div>
                        <button
                            class="copy-btn bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-lg text-sm flex items-center">
                            <i class="fas fa-copy mr-2"></i> Copiar Código
                        </button>
                    </div>

                    <div id="auth-php" class="tab-content">
                        <div class="code-block p-4 rounded mb-4">
                            <pre class="text-slate-300 overflow-x-auto text-sm">
require_once('payflow-php/init.php');

\PayFlow\PayFlow::setApiKey('sk_test_4eC39HqLyjWDarjtT1zdp7dc');

// Listar pagamentos
$payments = \PayFlow\Payment::all();</pre>
                        </div>
                        <button
                            class="copy-btn bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-lg text-sm flex items-center">
                            <i class="fas fa-copy mr-2"></i> Copiar Código
                        </button>
                    </div>
                </div>

                <div class="mt-8">
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <i class="fas fa-shield-alt mr-3 text-indigo-400"></i> Melhores Práticas de Segurança
                    </h3>

                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1">
                                <div class="w-6 h-6 rounded-full bg-indigo-500/10 flex items-center justify-center">
                                    <i class="fas fa-check text-indigo-400 text-xs"></i>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-slate-300">Nunca armazene chaves de API no seu repositório de código. Use
                                    variáveis de ambiente.</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1">
                                <div class="w-6 h-6 rounded-full bg-indigo-500/10 flex items-center justify-center">
                                    <i class="fas fa-check text-indigo-400 text-xs"></i>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-slate-300">Revogue imediatamente qualquer chave que possa ter sido
                                    comprometida.</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1">
                                <div class="w-6 h-6 rounded-full bg-indigo-500/10 flex items-center justify-center">
                                    <i class="fas fa-check text-indigo-400 text-xs"></i>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-slate-300">Use HTTPS para todas as requisições e nunca envie dados
                                    sensíveis via HTTP.</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex-shrink-0 mt-1">
                                <div class="w-6 h-6 rounded-full bg-indigo-500/10 flex items-center justify-center">
                                    <i class="fas fa-check text-indigo-400 text-xs"></i>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-slate-300">Implemente autenticação de dois fatores no seu painel PayFlow
                                    Pro.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Payments endpoint -->
            <section id="payments" class="mb-20 scroll-mt-20">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold flex items-center">
                        <i class="fas fa-credit-card mr-3 text-indigo-400"></i> Pagamentos
                    </h2>
                    <span class="text-xs bg-indigo-900 text-indigo-300 px-3 py-1 rounded-full">Novo</span>
                </div>

                <p class="text-slate-300 mb-6">
                    O endpoint de pagamentos permite que você processe transações com cartões de crédito, débito,
                    carteiras digitais e outros métodos de pagamento. Todos os valores são especificados na menor
                    unidade da moeda (por exemplo, centavos para BRL ou USD).
                </p>

                <div class="mt-8">
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <i class="fas fa-plus-circle mr-3 text-indigo-400"></i> Criar um Pagamento
                    </h3>

                    <div class="flex items-center mb-4">
                        <div class="endpoint-method method-post text-white px-3 py-1 rounded text-sm font-mono">POST
                        </div>
                        <code class="ml-3 text-slate-300 font-mono">/payments</code>
                    </div>

                    <div class="bg-slate-800/50 p-6 rounded-xl border border-slate-700">
                        <div class="flex border-b border-slate-700 mb-4">
                            <button class="tab-btn active px-4 py-2 font-medium text-sm"
                                data-tab="payment-request">Request</button>
                            <button class="tab-btn px-4 py-2 font-medium text-sm"
                                data-tab="payment-response">Response</button>
                            <button class="tab-btn px-4 py-2 font-medium text-sm"
                                data-tab="payment-errors">Errors</button>
                        </div>

                        <div id="payment-request" class="tab-content active">
                            <h4 class="font-semibold mb-2 text-slate-300">Corpo da Requisição</h4>
                            <div class="code-block p-4 rounded mb-4">
                                <pre class="text-slate-300 overflow-x-auto text-sm">
{
  "amount": 1990,                // Valor em centavos (R$ 19,90)
  "currency": "brl",             // Moeda (ISO 4217)
  "source": "tok_visa",          // Token do cartão ou ID de método de pagamento
  "description": "Assinatura Premium",
  "capture": true,               // Capturar o pagamento imediatamente
  "statement_descriptor": "PAYFLOW*ASSINATURA",
  "metadata": {
    "order_id": "12345",
    "customer_id": "cus_123"
  },
  "payment_method_options": {
    "card": {
      "installments": 3          // Parcelamento em 3x
    }
  }
}</pre>
                            </div>

                            <h4 class="font-semibold mb-2 text-slate-300">Parâmetros</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full bg-slate-900 rounded-lg overflow-hidden">
                                    <thead class="bg-slate-800">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">Parâmetro
                                            </th>
                                            <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">Tipo</th>
                                            <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">
                                                Obrigatório</th>
                                            <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">Descrição
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800">
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-300 parameter-required">amount</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">integer</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">Sim</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">Valor na menor unidade da moeda
                                                (ex: centavos)</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-300 parameter-required">currency
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-300">string</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">Sim</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">Código ISO 4217 da moeda (brl,
                                                usd, eur, etc)</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-300 parameter-required">source</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">string</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">Sim</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">Token do cartão ou ID de método
                                                de pagamento salvo</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-300">description</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">string</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">Não</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">Descrição do pagamento (máx.
                                                500 caracteres)</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-300">metadata</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">object</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">Não</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">Metadados personalizados (até
                                                20 pares chave-valor)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="payment-response" class="tab-content">
                            <h4 class="font-semibold mb-2 text-slate-300">Resposta de Sucesso (201 Created)</h4>
                            <div class="code-block p-4 rounded mb-4">
                                <pre class="text-slate-300 overflow-x-auto text-sm">
{
  "id": "pay_123456789",
  "object": "payment",
  "amount": 1990,
  "amount_captured": 1990,
  "amount_refunded": 0,
  "currency": "brl",
  "status": "succeeded",
  "description": "Assinatura Premium",
  "statement_descriptor": "PAYFLOW*ASSINATURA",
  "metadata": {
    "order_id": "12345",
    "customer_id": "cus_123"
  },
  "created_at": 1625097600,
  "payment_method": {
    "id": "pm_123456789",
    "object": "payment_method",
    "type": "card",
    "card": {
      "brand": "visa",
      "last4": "4242",
      "exp_month": 12,
      "exp_year": 2025,
      "country": "BR",
      "fingerprint": "XYZ123456789"
    },
    "customer": "cus_123",
    "created_at": 1625097600
  },
  "receipt_url": "https://payflowpro.com/receipts/pay_123456789",
  "refunds": {
    "object": "list",
    "data": [],
    "has_more": false,
    "total_count": 0
  }
}</pre>
                            </div>

                            <h4 class="font-semibold mb-2 text-slate-300">Campos da Resposta</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full bg-slate-900 rounded-lg overflow-hidden">
                                    <thead class="bg-slate-800">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">Campo
                                            </th>
                                            <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">Tipo</th>
                                            <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">Descrição
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800">
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-300">status</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">string</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">succeeded, pending, failed,
                                                canceled</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-300">receipt_url</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">string</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">URL para o comprovante do
                                                pagamento</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-300">payment_method</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">object</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">Detalhes do método de pagamento
                                                utilizado</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-300">refunds</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">object</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">Lista de reembolsos associados
                                                a este pagamento</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="payment-errors" class="tab-content">
                            <h4 class="font-semibold mb-4 text-slate-300">Possíveis Erros</h4>
                            <div class="space-y-4">
                                <div
                                    class="bg-slate-900 p-4 rounded-lg border border-slate-700 hover:border-red-500 transition-all">
                                    <div class="flex items-center">
                                        <div
                                            class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center text-red-400 font-bold mr-3">
                                            400
                                        </div>
                                        <div>
                                            <h5 class="font-medium">Requisição Inválida</h5>
                                            <p class="text-sm text-slate-400">Parâmetro obrigatório ausente: amount</p>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="bg-slate-900 p-4 rounded-lg border border-slate-700 hover:border-red-500 transition-all">
                                    <div class="flex items-center">
                                        <div
                                            class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center text-red-400 font-bold mr-3">
                                            402
                                        </div>
                                        <div>
                                            <h5 class="font-medium">Pagamento Falhou</h5>
                                            <p class="text-sm text-slate-400">Cartão foi recusado: fundos insuficientes
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="bg-slate-900 p-4 rounded-lg border border-slate-700 hover:border-red-500 transition-all">
                                    <div class="flex items-center">
                                        <div
                                            class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center text-red-400 font-bold mr-3">
                                            401
                                        </div>
                                        <div>
                                            <h5 class="font-medium">Não Autorizado</h5>
                                            <p class="text-sm text-slate-400">Chave de API inválida fornecida</p>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="bg-slate-900 p-4 rounded-lg border border-slate-700 hover:border-red-500 transition-all">
                                    <div class="flex items-center">
                                        <div
                                            class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center text-red-400 font-bold mr-3">
                                            429
                                        </div>
                                        <div>
                                            <h5 class="font-medium">Muitas Requisições</h5>
                                            <p class="text-sm text-slate-400">Limite de requisições excedido</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-12">
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <i class="fas fa-list mr-3 text-indigo-400"></i> Listar Pagamentos
                    </h3>

                    <div class="flex items-center mb-4">
                        <div class="endpoint-method method-get text-white px-3 py-1 rounded text-sm font-mono">GET</div>
                        <code class="ml-3 text-slate-300 font-mono">/payments</code>
                    </div>

                    <div class="bg-slate-800/50 p-6 rounded-xl border border-slate-700">
                        <div class="flex border-b border-slate-700 mb-4">
                            <button class="tab-btn active px-4 py-2 font-medium text-sm"
                                data-tab="list-request">Request</button>
                            <button class="tab-btn px-4 py-2 font-medium text-sm"
                                data-tab="list-response">Response</button>
                        </div>

                        <div id="list-request" class="tab-content active">
                            <h4 class="font-semibold mb-2 text-slate-300">Parâmetros de Consulta</h4>
                            <div class="code-block p-4 rounded mb-4">
                                <pre class="text-slate-300 overflow-x-auto text-sm">
GET /payments?limit=10&starting_after=pay_123</pre>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full bg-slate-900 rounded-lg overflow-hidden">
                                    <thead class="bg-slate-800">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">Parâmetro
                                            </th>
                                            <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">Tipo</th>
                                            <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">Descrição
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800">
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-300">limit</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">integer</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">Número de itens por página
                                                (padrão: 10, máximo: 100)</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-300">starting_after</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">string</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">ID do pagamento para começar a
                                                lista após</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-300">created</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">object</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">Filtro por data de criação (gt,
                                                gte, lt, lte)</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-300">status</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">string</td>
                                            <td class="px-4 py-3 text-sm text-slate-300">Filtrar por status (succeeded,
                                                pending, failed)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="list-response" class="tab-content">
                            <h4 class="font-semibold mb-2 text-slate-300">Resposta de Sucesso (200 OK)</h4>
                            <div class="code-block p-4 rounded mb-4">
                                <pre class="text-slate-300 overflow-x-auto text-sm">
{
  "object": "list",
  "data": [
    {
      "id": "pay_123456789",
      "object": "payment",
      "amount": 1990,
      "currency": "brl",
      "status": "succeeded",
      "created_at": 1625097600,
      "payment_method": {
        "type": "card",
        "card": {
          "brand": "visa",
          "last4": "4242"
        }
      }
    },
    // ... mais pagamentos
  ],
  "has_more": true,
  "next_page": "/payments?starting_after=pay_789"
}</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-12">
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <i class="fas fa-search mr-3 text-indigo-400"></i> Recuperar um Pagamento
                    </h3>

                    <div class="flex items-center mb-4">
                        <div class="endpoint-method method-get text-white px-3 py-1 rounded text-sm font-mono">GET</div>
                        <code class="ml-3 text-slate-300 font-mono">/payments/:id</code>
                    </div>

                    <div class="bg-slate-800/50 p-6 rounded-xl border border-slate-700">
                        <div class="flex border-b border-slate-700 mb-4">
                            <button class="tab-btn active px-4 py-2 font-medium text-sm"
                                data-tab="retrieve-request">Request</button>
                            <button class="tab-btn px-4 py-2 font-medium text-sm"
                                data-tab="retrieve-response">Response</button>
                        </div>

                        <div id="retrieve-request" class="tab-content active">
                            <div class="code-block p-4 rounded mb-4">
                                <pre class="text-slate-300 overflow-x-auto text-sm">
curl -X GET https://api.payflowpro.com/v2/payments/pay_123456789 \
  -H "Authorization: Bearer sk_test_4eC39HqLyjWDarjtT1zdp7dc"</pre>
                            </div>
                        </div>

                        <div id="retrieve-response" class="tab-content">
                            <div class="code-block p-4 rounded mb-4">
                                <pre class="text-slate-300 overflow-x-auto text-sm">
{
  "id": "pay_123456789",
  "object": "payment",
  "amount": 1990,
  "currency": "brl",
  "status": "succeeded",
  "created_at": 1625097600,
  "payment_method": {
    "id": "pm_123456789",
    "type": "card",
    "card": {
      "brand": "visa",
      "last4": "4242",
      "exp_month": 12,
      "exp_year": 2025
    }
  },
  "receipt_url": "https://payflowpro.com/receipts/pay_123456789"
}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Webhooks section -->
            <section id="webhooks" class="mb-20 scroll-mt-20">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold flex items-center">
                        <i class="fas fa-bell mr-3 text-indigo-400"></i> Webhooks
                    </h2>
                    <span class="text-xs bg-purple-900 text-purple-300 px-3 py-1 rounded-full">Recomendado</span>
                </div>

                <p class="text-slate-300 mb-6">
                    Webhooks permitem que você receba notificações em tempo real sobre eventos em sua conta PayFlow Pro.
                    Configure endpoints de webhook em seu <a href="#" class="text-indigo-400 hover:underline">painel</a>
                    para receber
                    requisições HTTP POST quando eventos ocorrerem.
                </p>

                <div class="bg-slate-800/50 p-6 rounded-xl border border-slate-700 mb-8">
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <i class="fas fa-shield-alt mr-3 text-indigo-400"></i> Verificando Webhooks
                    </h3>
                    <p class="text-slate-300 mb-4">
                        Cada chamada de webhook inclui cabeçalhos que permitem verificar se a requisição veio do PayFlow
                        Pro.
                    </p>

                    <div class="code-block p-4 rounded mb-4">
                        <pre class="text-slate-300 overflow-x-auto text-sm">
// Exemplo de verificação de assinatura de webhook em Node.js
const crypto = require('crypto');

const verifyWebhook = (req, secret) => {
  const signature = req.headers['payflow-signature'];
  const payload = JSON.stringify(req.body);
  
  const expectedSignature = crypto
    .createHmac('sha256', secret)
    .update(payload)
    .digest('hex');
    
  if (signature !== expectedSignature) {
    throw new Error('Invalid signature');
  }
  
  return true;
};

// Uso
try {
  verifyWebhook(request, process.env.PAYFLOW_WEBHOOK_SECRET);
  // Processar evento
} catch (err) {
  console.error('Webhook verification failed:', err);
  return response.status(400).send('Invalid signature');
}</pre>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="bg-slate-900 p-4 rounded-lg border border-slate-700">
                            <h4 class="font-medium mb-2 text-slate-300">Cabeçalhos de Webhook</h4>
                            <ul class="text-sm text-slate-400 space-y-2">
                                <li><code class="bg-slate-800 px-1.5 py-0.5 rounded">Payflow-Signature</code> -
                                    Assinatura HMAC-SHA256</li>
                                <li><code class="bg-slate-800 px-1.5 py-0.5 rounded">Payflow-Event-Id</code> - ID único
                                    do evento</li>
                                <li><code class="bg-slate-800 px-1.5 py-0.5 rounded">Payflow-Event-Type</code> - Tipo de
                                    evento</li>
                                <li><code class="bg-slate-800 px-1.5 py-0.5 rounded">Payflow-Event-Time</code> -
                                    Timestamp do evento</li>
                            </ul>
                        </div>
                        <div class="bg-slate-900 p-4 rounded-lg border border-slate-700">
                            <h4 class="font-medium mb-2 text-slate-300">Melhores Práticas</h4>
                            <ul class="text-sm text-slate-400 space-y-2">
                                <li>Sempre verifique a assinatura do webhook</li>
                                <li>Configure um endpoint redundante para eventos críticos</li>
                                <li>Trate eventos idempotentemente</li>
                                <li>Registre todos os eventos recebidos</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <i class="fas fa-list-ol mr-3 text-indigo-400"></i> Eventos de Webhook
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-slate-900 rounded-lg overflow-hidden">
                            <thead class="bg-slate-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">Evento</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">Descrição</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">Frequência</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <tr>
                                    <td class="px-4 py-3 text-sm text-slate-300 font-mono">payment.succeeded</td>
                                    <td class="px-4 py-3 text-sm text-slate-300">Pagamento concluído com sucesso</td>
                                    <td class="px-4 py-3 text-sm text-slate-300">Alta</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 text-sm text-slate-300 font-mono">payment.failed</td>
                                    <td class="px-4 py-3 text-sm text-slate-300">Pagamento falhou ou foi recusado</td>
                                    <td class="px-4 py-3 text-sm text-slate-300">Média</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 text-sm text-slate-300 font-mono">payment.refunded</td>
                                    <td class="px-4 py-3 text-sm text-slate-300">Reembolso foi processado</td>
                                    <td class="px-4 py-3 text-sm text-slate-300">Baixa</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 text-sm text-slate-300 font-mono">charge.dispute.created</td>
                                    <td class="px-4 py-3 text-sm text-slate-300">Cliente iniciou uma disputa</td>
                                    <td class="px-4 py-3 text-sm text-slate-300">Rara</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 text-sm text-slate-300 font-mono">subscription.created</td>
                                    <td class="px-4 py-3 text-sm text-slate-300">Nova assinatura criada</td>
                                    <td class="px-4 py-3 text-sm text-slate-300">Média</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 text-sm text-slate-300 font-mono">invoice.payment_succeeded
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-300">Pagamento de fatura bem-sucedido</td>
                                    <td class="px-4 py-3 text-sm text-slate-300">Alta</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-8 bg-slate-800/50 p-6 rounded-xl border border-slate-700">
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <i class="fas fa-code mr-3 text-indigo-400"></i> Exemplo de Payload
                    </h3>

                    <div class="code-block p-4 rounded">
                        <pre class="text-slate-300 overflow-x-auto text-sm">
{
  "id": "evt_123456789",
  "object": "event",
  "type": "payment.succeeded",
  "created": 1625097600,
  "data": {
    "object": {
      "id": "pay_123456789",
      "object": "payment",
      "amount": 1990,
      "currency": "brl",
      "status": "succeeded",
      "payment_method": {
        "type": "card",
        "card": {
          "brand": "visa",
          "last4": "4242"
        }
      },
      "customer": "cus_123456789",
      "metadata": {
        "order_id": "12345"
      }
    }
  },
  "request": {
    "id": "req_123456789",
    "idempotency_key": "xyz123456789"
  }
}</pre>
                    </div>
                </div>
            </section>

            <!-- Support section -->
            <section id="support" class="mb-16 scroll-mt-20">
                <h2 class="text-2xl font-bold mb-6 flex items-center">
                    <i class="fas fa-headset mr-3 text-indigo-400"></i> Suporte
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        class="bg-slate-800/50 p-6 rounded-xl border border-slate-700 hover:border-indigo-500 transition-all">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-lg bg-indigo-500/10 flex items-center justify-center mr-4">
                                <i class="fas fa-envelope text-indigo-400 text-xl"></i>
                            </div>
                            <h3 class="font-semibold">Email de Suporte</h3>
                        </div>
                        <p class="text-slate-400 mb-4">
                            Nossa equipe está disponível 24/7 para ajudar com quaisquer problemas.
                        </p>
                        <a href="mailto:support@payflowpro.com" class="text-indigo-400 hover:underline font-medium">
                            support@payflowpro.com
                        </a>
                    </div>

                    <div
                        class="bg-slate-800/50 p-6 rounded-xl border border-slate-700 hover:border-indigo-500 transition-all">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-lg bg-indigo-500/10 flex items-center justify-center mr-4">
                                <i class="fas fa-comments text-indigo-400 text-xl"></i>
                            </div>
                            <h3 class="font-semibold">Chat ao Vivo</h3>
                        </div>
                        <p class="text-slate-400 mb-4">
                            Converse com nosso time em tempo real diretamente do seu painel.
                        </p>
                        <a href="#" class="text-indigo-400 hover:underline font-medium">
                            Iniciar conversa
                        </a>
                    </div>

                    <div
                        class="bg-slate-800/50 p-6 rounded-xl border border-slate-700 hover:border-indigo-500 transition-all">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-lg bg-indigo-500/10 flex items-center justify-center mr-4">
                                <i class="fas fa-users text-indigo-400 text-xl"></i>
                            </div>
                            <h3 class="font-semibold">Comunidade</h3>
                        </div>
                        <p class="text-slate-400 mb-4">
                            Junte-se a milhares de desenvolvedores na nossa comunidade.
                        </p>
                        <div class="flex space-x-3">
                            <a href="#" class="text-indigo-400 hover:underline font-medium">
                                <i class="fab fa-slack mr-1"></i> Slack
                            </a>
                            <a href="#" class="text-indigo-400 hover:underline font-medium">
                                <i class="fab fa-github mr-1"></i> GitHub
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-8 bg-slate-800/50 p-6 rounded-xl border border-slate-700">
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <i class="fas fa-question-circle mr-3 text-indigo-400"></i> Perguntas Frequentes
                    </h3>

                    <div class="space-y-4">
                        <div class="group">
                            <button class="flex items-center justify-between w-full text-left py-3">
                                <h4 class="font-medium group-hover:text-indigo-400 transition-colors">
                                    Como faço para testar a API antes de ir para produção?
                                </h4>
                                <i
                                    class="fas fa-chevron-down text-slate-400 group-hover:text-indigo-400 transition-transform transform group-focus:rotate-180"></i>
                            </button>
                            <div class="text-slate-400 pb-3 hidden group-focus:block">
                                Use nosso ambiente sandbox com cartões de teste. Todos os endpoints estão disponíveis em
                                <code
                                    class="bg-slate-700 px-1.5 py-0.5 rounded">https://api.sandbox.payflowpro.com/v2</code>
                                com chaves de API de teste.
                            </div>
                        </div>

                        <div class="group">
                            <button class="flex items-center justify-between w-full text-left py-3">
                                <h4 class="font-medium group-hover:text-indigo-400 transition-colors">
                                    Quais são os limites de requisição da API?
                                </h4>
                                <i
                                    class="fas fa-chevron-down text-slate-400 group-hover:text-indigo-400 transition-transform transform group-focus:rotate-180"></i>
                            </button>
                            <div class="text-slate-400 pb-3 hidden group-focus:block">
                                O limite padrão é de 100 requisições por minuto por chave de API. Se precisar de um
                                limite maior, entre em contato com nosso time de suporte.
                            </div>
                        </div>

                        <div class="group">
                            <button class="flex items-center justify-between w-full text-left py-3">
                                <h4 class="font-medium group-hover:text-indigo-400 transition-colors">
                                    Como posso receber notificações em tempo real?
                                </h4>
                                <i
                                    class="fas fa-chevron-down text-slate-400 group-hover:text-indigo-400 transition-transform transform group-focus:rotate-180"></i>
                            </button>
                            <div class="text-slate-400 pb-3 hidden group-focus:block">
                                Configure webhooks no seu painel PayFlow Pro para receber notificações sobre eventos
                                importantes como pagamentos bem-sucedidos, reembolsos e disputas.
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <footer class="border-t border-slate-800 pt-8 mt-16">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="flex items-center mb-4 md:mb-0">
                        <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center mr-3">
                            <i class="fas fa-bolt text-white text-sm"></i>
                        </div>
                        <span class="font-bold">PayFlow Pro</span>
                    </div>

                    <div class="flex space-x-6">
                        <a href="#" class="text-slate-400 hover:text-indigo-400">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-slate-400 hover:text-indigo-400">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="#" class="text-slate-400 hover:text-indigo-400">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <a href="#" class="text-slate-400 hover:text-indigo-400">
                            <i class="fab fa-discord"></i>
                        </a>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-8">
                    <div>
                        <h5 class="text-sm font-semibold text-slate-300 mb-3">Document

</html>