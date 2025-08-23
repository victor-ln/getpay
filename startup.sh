#!/bin/bash

# Navega para a raiz da aplicação
cd /home/site/wwwroot

echo "=== INICIANDO CONFIGURAÇÃO DO AMBIENTE ==="

# Instala Node.js se não estiver disponível (para Azure App Service)
echo "Verificando Node.js..."
if ! command -v node &> /dev/null; then
    echo "Node.js não encontrado. Instalando..."
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
    apt-get install -y nodejs
fi

# Verifica se o npm está disponível
if ! command -v npm &> /dev/null; then
    echo "ERRO: npm não está disponível"
    exit 1
fi

echo "Node.js versão: $(node --version)"
echo "npm versão: $(npm --version)"

# Instala dependências do Node.js
echo "Instalando dependências do Node.js..."
if [ -f "package.json" ]; then
    npm ci --production=false || npm install
else
    echo "AVISO: package.json não encontrado"
fi

# Instala Vite globalmente se necessário (opcional)
echo "Verificando Vite..."
if ! npm list vite &> /dev/null && ! npm list -g vite &> /dev/null; then
    echo "Instalando Vite..."
    npm install vite --save-dev
fi

# Executa o build do Vite
echo "Executando build do Vite..."
if [ -f "vite.config.js" ] || [ -f "vite.config.ts" ]; then
    npm run build || {
        echo "ERRO: Falha no build do Vite"
        exit 1
    }
else
    echo "AVISO: Configuração do Vite não encontrada"
fi

echo "=== CONFIGURANDO LARAVEL ==="

# Limpa TODOS os caches antigos do Laravel para garantir que as novas configurações sejam lidas
echo "Limpando caches do Laravel..."
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan cache:clear

# Recria o cache de configuração com as variáveis de ambiente do Azure
echo "Recriando o cache de configuração..."
php artisan config:cache

# Garante que as permissões de escrita estão corretas
echo "Corrigindo permissões..."
chmod -R 775 /home/site/wwwroot/storage
chmod -R 775 /home/site/wwwroot/bootstrap/cache

# Garante permissões para arquivos gerados pelo Vite
if [ -d "public/build" ]; then
    chmod -R 755 /home/site/wwwroot/public/build
    echo "Permissões ajustadas para arquivos do Vite"
fi

echo "=== CONFIGURANDO NGINX ==="

# Copia a configuração customizada do Nginx se existir
echo "Copiando configuração do Nginx..."
if [ -f "/home/site/wwwroot/default" ]; then
    cp /home/site/wwwroot/default /etc/nginx/sites-available/default
    cp /home/site/wwwroot/default /etc/nginx/sites-enabled/default
    
    # Testa a configuração do Nginx antes de recarregar (ótima prática!)
    nginx -t && service nginx reload || echo "ERRO: Falha ao recarregar o Nginx. Verifique a configuração."
else
    echo "AVISO: Arquivo de configuração 'default' do Nginx não encontrado. Usando configuração padrão do Azure."
fi

echo "=== SCRIPT CONCLUÍDO COM SUCESSO ==="