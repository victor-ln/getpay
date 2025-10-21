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

# Para qualquer worker antigo antes de iniciar um novo
echo "Parando workers antigos..."
pkill -f "queue:work" || true

# Inicia o queue worker em background
echo "Iniciando queue worker..."
nohup php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=60 > /home/site/wwwroot/storage/logs/queue-worker.log 2>&1 &

# Salva o PID do processo
echo $! > /home/site/wwwroot/storage/queue-worker.pid

echo "Script de inicialização concluído com sucesso."
echo "Queue worker iniciado com PID: $(cat /home/site/wwwroot/storage/queue-worker.pid)"



echo "Script de inicialização concluído com sucesso."
