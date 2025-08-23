#!/bin/bash

# Navega para a raiz da aplicação
cd /home/site/wwwroot

echo "=== INICIANDO CONFIGURAÇÃO DO AMBIENTE ==="

# Função para verificar se comando existe
command_exists() {
    command -v "$1" &> /dev/null
}

# Verifica Node.js e npm
echo "Verificando Node.js..."
if ! command_exists node; then
    echo "ERRO: Node.js não encontrado. Tentando instalar..."
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
    apt-get install -y nodejs || {
        echo "ERRO: Falha ao instalar Node.js"
        exit 1
    }
fi

if ! command_exists npm; then
    echo "ERRO: npm não está disponível"
    exit 1
fi

echo "Node.js versão: $(node --version)"
echo "npm versão: $(npm --version)"

echo "=== BUILD DO FRONTEND (VITE) ==="

# Verifica se precisa fazer build do frontend
if [ -f "package.json" ]; then
    echo "Instalando dependências do Node.js..."
    
    # Limpa cache do npm se necessário
    npm cache clean --force || echo "Aviso: Falha ao limpar cache npm"
    
    # Instala dependências
    npm ci --no-audit --no-fund || npm install --no-audit --no-fund || {
        echo "ERRO: Falha ao instalar dependências npm"
        exit 1
    }
    
    # Verifica se Vite está disponível
    if [ -f "vite.config.js" ] || [ -f "vite.config.ts" ]; then
        echo "Executando build do Vite..."
        
        # Remove build anterior se existir
        if [ -d "public/build" ]; then
            echo "Removendo build anterior..."
            rm -rf public/build/*
        fi
        
        # Executa o build
        npm run build || {
            echo "ERRO: Falha no build do Vite. Tentando build de desenvolvimento..."
            npm run dev --build || {
                echo "ERRO CRÍTICO: Falha total no build do Vite"
                exit 1
            }
        }
        
        # Verifica se o manifest foi gerado
        if [ -f "public/build/manifest.json" ]; then
            echo "✅ Manifest do Vite gerado com sucesso!"
            echo "Conteúdo do diretório build:"
            ls -la public/build/
        else
            echo "❌ ERRO: manifest.json não foi gerado!"
            echo "Conteúdo do diretório public/build:"
            ls -la public/build/ || echo "Diretório build não existe"
            exit 1
        fi
        
    else
        echo "⚠️  AVISO: Configuração do Vite não encontrada"
    fi
else
    echo "⚠️  AVISO: package.json não encontrado - pulando build do frontend"
fi

echo "=== CONFIGURANDO LARAVEL ==="

# Limpa TODOS os caches antigos do Laravel
echo "Limpando caches do Laravel..."
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan cache:clear

# Recria o cache de configuração
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
    
    # Testa a configuração do Nginx antes de recarregar
    nginx -t && service nginx reload || echo "ERRO: Falha ao recarregar o Nginx. Verifique a configuração."
else
    echo "AVISO: Arquivo de configuração 'default' do Nginx não encontrado. Usando configuração padrão do Azure."
fi

echo "=== VERIFICAÇÃO FINAL ==="

# Status do Laravel
echo "Verificando status do Laravel..."
php artisan --version

# Status dos arquivos do Vite
if [ -f "public/build/manifest.json" ]; then
    echo "✅ Vite manifest encontrado!"
else
    echo "❌ Vite manifest NÃO encontrado - isso pode causar erros!"
fi

echo "=== SCRIPT CONCLUÍDO ==="