#!/bin/bash

# Navega para a raiz da aplicação
cd /home/site/wwwroot

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

# Copia a configuração customizada do Nginx
echo "Copiando configuração do Nginx..."
if [ -f "/home/site/wwwroot/default" ]; then
    cp /home/site/wwwroot/default /etc/nginx/sites-available/default
    cp /home/site/wwwroot/default /etc/nginx/sites-enabled/default
    
    # Testa a configuração do Nginx antes de recarregar (ótima prática!)
    nginx -t && service nginx reload || echo "ERRO: Falha ao recarregar o Nginx. Verifique a configuração."
else
    echo "AVISO: Arquivo de configuração 'default' do Nginx não encontrado. Usando configuração padrão do Azure."
fi

echo "Script de inicialização concluído."
