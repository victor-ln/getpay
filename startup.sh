#!/bin/bash

# Navega para a raiz da aplicação
cd /home/site/wwwroot

# Instalar dependências do Composer se não existirem ou estiverem incompletas
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "Instalando dependências do Composer..."
    composer install --no-dev --optimize-autoloader --no-interaction
else
    echo "Verificando integridade das dependências..."
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# Verifica se o autoload foi criado corretamente
if [ ! -f "vendor/autoload.php" ]; then
    echo "ERRO: Autoload do Composer não foi criado. Tentando novamente..."
    rm -rf vendor
    composer clear-cache
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# Aguarda um pouco para garantir que os arquivos foram escritos
sleep 2

# Limpa TODOS os caches antigos do Laravel
echo "Limpando caches do Laravel..."
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

# Recria o cache de configuração com as variáveis do Azure
echo "Recriando o cache de configuração com as variáveis do Azure..."
php artisan config:cache

# Cria as pastas necessárias se não existirem
mkdir -p /home/site/wwwroot/storage/logs
mkdir -p /home/site/wwwroot/storage/framework/cache
mkdir -p /home/site/wwwroot/storage/framework/sessions
mkdir -p /home/site/wwwroot/storage/framework/views
mkdir -p /home/site/wwwroot/bootstrap/cache

# Corrige as permissões das pastas (sem usar chown pois não temos permissão)
echo "Corrigindo permissões..."
chmod -R 755 /home/site/wwwroot/storage 2>/dev/null || true
chmod -R 755 /home/site/wwwroot/bootstrap/cache 2>/dev/null || true

# Copia a configuração customizada do Nginx
echo "Copiando configuração customizada do Nginx..."
if [ -f "/home/site/wwwroot/default" ]; then
    cp /home/site/wwwroot/default /etc/nginx/sites-available/default
    cp /home/site/wwwroot/default /etc/nginx/sites-enabled/default
    
    # Testa a configuração do Nginx antes de recarregar
    nginx -t && service nginx reload || echo "Erro na configuração do Nginx"
else
    echo "Arquivo de configuração do Nginx não encontrado"
fi

echo "Script de inicialização concluído com sucesso."