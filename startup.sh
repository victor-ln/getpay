#!/bin/bash

# NOVO: Habilita a extensão pdo_pgsql no container
echo "Habilitando a extensão PHP pdo_pgsql..."
docker-php-ext-enable pdo_pgsql

# ---------------------------------------------------

# Navega para a raiz da aplicação
cd /home/site/wwwroot

# Limpa os caches do Laravel
php artisan config:clear
php artisan route:clear
php artisan view:clear

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

echo "Script de inicialização concluído."