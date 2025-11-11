#!/bin/bash

# Navega para a raiz da aplicação
cd /home/site/wwwroot

cp .azure/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
cp .azure/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Limpa TODOS os caches antigos do Laravel para garantir que as novas configurações sejam lidas
echo "Limpando caches do Laravel..."
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan opcache:clear

# Recria o cache de configuração com as variáveis de ambiente do Azure
echo "Recriando o cache de configuração..."
php artisan config:cache

# Garante que as permissões de escrita estão corretas
echo "Corrigindo permissões..."
chmod -R 775 /home/site/wwwroot/storage
chmod -R 775 /home/site/wwwroot/bootstrap/cache

DD_API_KEY=66316bf9be7c6cbe53dc28303e9c266f DD_SITE="datadoghq.com" DD_REMOTE_UPDATES=true DD_APM_INSTRUMENTATION_ENABLED=host DD_ENV=production DD_REMOTE_UPDATES=true DD_PROFILING_ENABLED=auto DD_APM_INSTRUMENTATION_LIBRARIES=php:1 bash -c "$(curl -L https://s3.amazonaws.com/dd-agent/scripts/install_script_agent7.sh)"

cd /root && \
    curl -LO https://github.com/DataDog/dd-trace-php/releases/download/1.13.1/datadog-setup.php && \
    php datadog-setup.php --php-bin=all --enable-appsec --enable-profiling

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

mkdir -p /home/site/wwwroot/storage/app/certificates/e2
chmod 755 /home/site/wwwroot/storage/app/certificates/e2


nohup php artisan queue:work --daemon > storage/logs/queue-worker.log 2>&1 &




echo "Script de inicialização concluído com sucesso."


#php artisan queue:work redis --tries=3 -vvv
