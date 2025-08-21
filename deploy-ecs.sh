#!/bin/bash

# Script de deploy para o ECS
# Atualiza a imagem e o serviço com as novas configurações

set -e

# Configurações
ECR_REPOSITORY="114687467876.dkr.ecr.us-east-1.amazonaws.com"
IMAGE_NAME="getpay"
CLUSTER_NAME="getpay-cluster"
SERVICE_NAME="getpay-service"
TASK_DEFINITION_FILE="ecs-task-definition.json"
REGION="us-east-1"

echo "🚀 Iniciando deploy para o ECS..."

# 1. Login no ECR
echo "📝 Fazendo login no ECR..."
aws ecr get-login-password --region $REGION | docker login --username AWS --password-stdin $ECR_REPOSITORY

# 2. Build da imagem
echo "🔨 Fazendo build da imagem..."
docker build -t $IMAGE_NAME:latest --target production .

# 3. Tag da imagem
echo "🏷️  Taggeando imagem..."
docker tag $IMAGE_NAME:latest $ECR_REPOSITORY/$IMAGE_NAME:latest

# 4. Push para o ECR
echo "⬆️  Fazendo push para o ECR..."
docker push $ECR_REPOSITORY/$IMAGE_NAME:latest

# 5. Registrar nova task definition
echo "📋 Registrando nova task definition..."
TASK_DEF_ARN=$(aws ecs register-task-definition \
    --cli-input-json file://$TASK_DEFINITION_FILE \
    --region $REGION \
    --query 'taskDefinition.taskDefinitionArn' \
    --output text)

echo "✅ Nova task definition registrada: $TASK_DEF_ARN"

# 6. Atualizar o serviço
echo "🔄 Atualizando o serviço..."
aws ecs update-service \
    --cluster $CLUSTER_NAME \
    --service $SERVICE_NAME \
    --task-definition $TASK_DEF_ARN \
    --region $REGION

echo "✅ Serviço atualizado com sucesso!"

# 7. Aguardar estabilização
echo "⏳ Aguardando estabilização do serviço..."
aws ecs wait services-stable \
    --cluster $CLUSTER_NAME \
    --services $SERVICE_NAME \
    --region $REGION

echo "🎉 Deploy concluído com sucesso!"

# 8. Mostrar status
echo "📊 Status atual do serviço:"
aws ecs describe-services \
    --cluster $CLUSTER_NAME \
    --services $SERVICE_NAME \
    --region $REGION \
    --query 'services[0].{Status:status,RunningCount:runningCount,DesiredCount:desiredCount,TaskDefinition:taskDefinition}' \
    --output table

echo "🔍 Para ver os logs em tempo real:"
echo "aws logs tail /ecs/getpay-app --follow --region $REGION"
