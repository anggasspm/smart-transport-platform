COMPOSE = docker compose
COMPOSE_DEV = docker compose -f docker-compose.yml -f docker-compose.dev.yml
NAMESPACE = smarttransport

.PHONY: help up down dev build logs ps restart clean seed \
        k8s-apply k8s-delete k8s-status k8s-logs

help: 
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

up: 
	$(COMPOSE) up -d --build

dev: 
	$(COMPOSE_DEV) up --build

down: 
	$(COMPOSE) down

build: 
	$(COMPOSE) build --no-cache

logs: 
	$(COMPOSE) logs -f

ps: 
	$(COMPOSE) ps

restart: 
	$(COMPOSE) restart

clean: 
	$(COMPOSE) down -v --remove-orphans

seed: 
	docker exec -i smarttransport-mysql mysql -uroot -p$${MYSQL_ROOT_PASSWORD:-rootpass} smarttransport < database/seed.sql

health: 
	@curl -s http://localhost:9000/health | jq . || echo "Gateway belum jalan"
	@curl -s http://localhost:3002/health | jq . || echo "OAuth belum jalan"
	@curl -s http://localhost:8000/health | jq . || echo "Passenger Service belum jalan"
	@curl -s http://localhost:8001/health | jq . || echo "Fleet Service belum jalan"
	@curl -s http://localhost:8002/health | jq . || echo "Stop Service belum jalan"
	@curl -s http://localhost:5000/health | jq . || echo "ML Service belum jalan"

# --- Kubernetes ---
k8s-apply:
	kubectl apply -f k8s/00-namespace.yaml
	kubectl apply -f k8s/.

k8s-delete: 
	kubectl delete -f k8s/. --ignore-not-found

k8s-status:
	kubectl get pods,svc,hpa,ingress -n $(NAMESPACE)

k8s-logs:
	kubectl logs -f -n $(NAMESPACE) deployment/$(APP)