#!/bin/bash

# Navega para a raiz da aplicação
cd /home/site/wwwroot

# Habilita a extensão pdo_pgsql no container (garantia)
echo "Habilitando a extensão PHP pdo_pgsql..."
docker-php-ext-enable pdo_pgsql

# Limpa TODOS os caches antigos
php artisan route:clear
php artisan view:clear
php artisan config:clear

# NOVO E MAIS IMPORTANTE: Recria o cache de configuração
# Este comando lê as variáveis de ambiente do Azure e cria um novo cache.
echo "Recriando o cache de configuração com as variáveis do Azure..."
php artisan config:cache

# Corrige as permissões das pastas
chown -R www-data:www-data /home/site/wwwroot/storage
chown -R www-data:www-data /home/site/wwwroot/bootstrap/cache
chmod -R 775 /home/site/wwwroot/storage
chmod -R 775 /home/site/wwwroot/bootstrap/cache

# Copia a configuração customizada do Nginx
echo "Copiando configuração customizada do Nginx..."
cp /home/site/wwwroot/default /etc/nginx/sites-available/default
cp /home/site/wwwroot/default /etc/nginx/sites-enabled/default

# Recarrega o serviço Nginx
echo "Recarregando o Nginx..."
service nginx reload

echo "Script de nginx inicialização concluído."