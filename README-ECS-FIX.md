# Correções para Problemas do ECS

## Problemas Identificados

Os logs mostravam que o ECS estava recebendo sinais `SIGTERM` e os processos estavam sendo encerrados de forma abrupta:

```
2025-08-19 01:08:15,349 WARN received SIGTERM indicating exit request
2025-08-19 01:08:15,349 INFO waiting for php-fpm, laravel-queue, laravel-scheduler to die
```

## Soluções Implementadas

### 1. Configuração do Supervisor Melhorada (`docker/supervisor.prod.conf`)

-   **Graceful Shutdown**: Configurado `killasgroup=true` e `stopasgroup=true`
-   **Timeouts**: Aumentado `stopwaitsecs` para dar tempo dos processos terminarem
-   **Retry Logic**: Adicionado `startretries` e `startsecs` para melhor estabilidade
-   **Process Management**: Melhor controle sobre o ciclo de vida dos processos

### 2. Script de Entrada Inteligente (`docker/entrypoint.sh`)

-   **Signal Handling**: Captura `SIGTERM` e `SIGINT` para graceful shutdown
-   **Dependency Check**: Aguarda banco de dados e Redis estarem disponíveis
-   **Health Check**: Verifica dependências antes de iniciar
-   **Permission Management**: Configura permissões automaticamente

### 3. Configuração Nginx Otimizada (`docker/nginx.ecs.conf`)

-   **Health Check Duplo**:
    -   `/health` - Para verificação geral da aplicação
    -   `/ecs-health` - Para o Load Balancer do ECS
-   **ECS Optimizations**: Headers específicos para ECS
-   **Performance**: Configurações otimizadas para containers

### 4. Task Definition Otimizada (`ecs-task-definition.json`)

-   **Health Check**: Usa `/health` endpoint
-   **Secrets**: Configurado para usar AWS Secrets Manager
-   **Resource Limits**: Configurações adequadas de CPU/Memory
-   **Logging**: Configuração para CloudWatch Logs com buffer não-bloqueante

### 5. Script de Deploy Automatizado (`deploy-ecs.sh`)

-   **Build e Push**: Automatiza o processo de deploy
-   **Task Definition**: Registra nova versão automaticamente
-   **Service Update**: Atualiza o serviço ECS
-   **Monitoring**: Aguarda estabilização e mostra status

## Como Aplicar as Correções

### Passo 1: Build da Nova Imagem

```bash
# Build com target de produção
docker build -t getpay-app:latest --target production .
```

### Passo 2: Teste Local (Opcional)

```bash
# Teste local com docker-compose
docker-compose up --build
```

### Passo 3: Deploy para ECS

```bash
# Execute o script de deploy
./deploy-ecs.sh
```

### Passo 4: Verificação

```bash
# Verifique os logs
aws logs tail /ecs/getpay-app --follow --region us-east-1

# Verifique o status do serviço
aws ecs describe-services --cluster getpay-cluster --services getpay-service
```

## Configurações Importantes

### Variáveis de Ambiente

```bash
APP_ENV=production
APP_DEBUG=false
LOG_CHANNEL=stderr
RUN_MIGRATIONS=false
```

### Health Check

-   **Endpoint**: `/health`
-   **Interval**: 30 segundos
-   **Timeout**: 5 segundos
-   **Retries**: 3
-   **Start Period**: 60 segundos

### Graceful Shutdown

-   **Stop Timeout**: 120 segundos
-   **Signal Handling**: SIGTERM capturado
-   **Process Management**: Supervisor controla shutdown

## Monitoramento

### CloudWatch Logs

-   **Log Group**: `/ecs/getpay-app`
-   **Stream Prefix**: `ecs`
-   **Retention**: Configurável
-   **Buffer**: Não-bloqueante com tamanho máximo de 25MB

### Métricas ECS

-   **CPU Utilization**
-   **Memory Utilization**
-   **Network I/O**
-   **Task Health Status**

## Troubleshooting

### Se o Health Check Falhar

1. Verifique se o container está rodando
2. Teste o endpoint `/ecs-health` localmente
3. Verifique os logs do container
4. Confirme se as dependências estão acessíveis

### Se o Graceful Shutdown Não Funcionar

1. Verifique se o `stopTimeout` está configurado
2. Confirme se o supervisor está capturando sinais
3. Verifique os logs para sinais de SIGTERM
4. Teste localmente com `docker stop`

### Se os Processos Não Iniciarem

1. Verifique as permissões dos arquivos
2. Confirme se as dependências estão disponíveis
3. Verifique a configuração do supervisor
4. Teste os comandos manualmente no container

## Benefícios das Correções

1. **Estabilidade**: Melhor gerenciamento do ciclo de vida dos processos
2. **Reliability**: Health checks mais robustos
3. **Performance**: Configurações otimizadas para ECS
4. **Monitoring**: Melhor observabilidade e logging
5. **Maintenance**: Deploy automatizado e confiável

## Próximos Passos

1. **Implementar**: Aplique as correções em ambiente de teste
2. **Validar**: Teste o graceful shutdown e health checks
3. **Monitorar**: Acompanhe os logs e métricas
4. **Produção**: Deploy em produção após validação
5. **Otimizar**: Ajuste configurações baseado no uso real
