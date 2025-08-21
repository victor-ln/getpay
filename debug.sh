#!/bin/bash

echo "=== VERIFICANDO STATUS DOS SERVIÇOS ==="
docker-compose ps

echo -e "\n=== LOGS DO NGINX ==="
docker-compose exec app cat /var/log/nginx/error.log 2>/dev/null || echo "Arquivo não encontrado"

echo -e "\n=== LOGS DO PHP-FPM ==="
docker-compose exec app cat /var/log/supervisor/php-fpm.err.log 2>/dev/null || echo "Arquivo não encontrado"

echo -e "\n=== LOGS DO LARAVEL ==="
docker-compose exec app cat /var/www/html/storage/logs/laravel.log 2>/dev/null || echo "Arquivo não encontrado"

echo -e "\n=== LOGS DO SUPERVISOR ==="
docker-compose exec app cat /var/log/supervisor/supervisord.log 2>/dev/null || echo "Arquivo não encontrado"

echo -e "\n=== VERIFICANDO PERMISSÕES ==="
docker-compose exec app ls -la /var/www/html/public/

echo -e "\n=== VERIFICANDO SE O INDEX.PHP EXISTE ==="
docker-compose exec app ls -la /var/www/html/public/index.php

echo -e "\n=== TESTANDO PHP ==="
docker-compose exec app php -v

echo -e "\n=== TESTANDO ARTISAN ==="
docker-compose exec app php artisan --version

echo -e "\n=== VERIFICANDO PROCESSOS ==="
docker-compose exec app ps aux