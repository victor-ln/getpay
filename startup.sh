#!/bin/bash

# Navega para a raiz da aplicação
cd /home/site/wwwroot

# Limpa os caches do Laravel (boa prática)
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Copia a configuração customizada do Nginx para o local correto.
# O Azure pode usar 'sites-available' ou 'sites-enabled'. Copiar para ambos garante a cobertura.
echo "Copiando configuração customizada do Nginx..."
cp /home/site/wwwroot/default /etc/nginx/sites-available/default
cp /home/site/wwwroot/default /etc/nginx/sites-enabled/default

# Recarrega o serviço Nginx para aplicar as alterações
echo "Recarregando o Nginx..."
service nginx reload

echo "Script de inicialização concluído."