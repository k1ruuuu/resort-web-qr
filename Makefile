# ===================================================================
# Makefile for Docker-based Laravel Application
# Provides convenient commands for managing the application
# ===================================================================

.PHONY: help build up down restart logs shell mysql redis test deploy clean

# Default target
.DEFAULT_GOAL := help

# ===================================================================
# Help
# ===================================================================
help: ## Show this help message
	@echo "Laravel Resort Voucher System - Docker Commands"
	@echo "================================================"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# ===================================================================
# Docker Management
# ===================================================================
build: ## Build Docker images
	docker-compose build --no-cache

up: ## Start all containers
	docker-compose up -d
	@echo "Waiting for services to be healthy..."
	@sleep 10
	@echo "Application is running at http://localhost"

down: ## Stop all containers
	docker-compose down

restart: ## Restart all containers
	docker-compose restart

stop: ## Stop all containers (without removing)
	docker-compose stop

start: ## Start stopped containers
	docker-compose start

ps: ## Show running containers
	docker-compose ps

logs: ## Show logs for all services
	docker-compose logs -f

logs-app: ## Show logs for app service
	docker-compose logs -f app

logs-nginx: ## Show logs for nginx service
	docker-compose logs -f nginx

logs-mysql: ## Show logs for mysql service
	docker-compose logs -f mysql

logs-redis: ## Show logs for redis service
	docker-compose logs -f redis

# ===================================================================
# Application Access
# ===================================================================
shell: ## Access app container shell
	docker-compose exec app sh

shell-nginx: ## Access nginx container shell
	docker-compose exec nginx sh

mysql: ## Access MySQL CLI
	docker-compose exec mysql mysql -u resort_user -p resort_voucher

redis: ## Access Redis CLI
	docker-compose exec redis redis-cli -a $$(grep REDIS_PASSWORD .env | cut -d '=' -f2)

# ===================================================================
# Laravel Commands
# ===================================================================
artisan: ## Run artisan command (use: make artisan cmd="migrate")
	docker-compose exec app php artisan $(cmd)

migrate: ## Run database migrations
	docker-compose exec app php artisan migrate

migrate-fresh: ## Fresh migration with seed
	docker-compose exec app php artisan migrate:fresh --seed

seed: ## Seed database
	docker-compose exec app php artisan db:seed

tinker: ## Run Laravel Tinker
	docker-compose exec app php artisan tinker

cache-clear: ## Clear all caches
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

cache: ## Cache config, routes, and views
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache

optimize: ## Optimize application
	docker-compose exec app php artisan optimize

key-generate: ## Generate application key
	docker-compose exec app php artisan key:generate

storage-link: ## Create storage link
	docker-compose exec app php artisan storage:link

# ===================================================================
# Testing
# ===================================================================
test: ## Run PHPUnit tests
	docker-compose exec app php artisan test

test-coverage: ## Run tests with coverage
	docker-compose exec app php artisan test --coverage

# ===================================================================
# Composer
# ===================================================================
composer-install: ## Install composer dependencies
	docker-compose exec app composer install

composer-update: ## Update composer dependencies
	docker-compose exec app composer update

composer-dump: ## Dump autoload
	docker-compose exec app composer dump-autoload

# ===================================================================
# NPM/Node
# ===================================================================
npm-install: ## Install npm dependencies
	docker run --rm -v $$(pwd):/app -w /app node:20-alpine npm install

npm-dev: ## Run npm dev build
	docker run --rm -v $$(pwd):/app -w /app node:20-alpine npm run dev

npm-build: ## Run npm production build
	docker run --rm -v $$(pwd):/app -w /app node:20-alpine npm run build

npm-watch: ## Run npm watch
	docker run --rm -v $$(pwd):/app -w /app node:20-alpine npm run watch

# ===================================================================
# Database Management
# ===================================================================
db-backup: ## Backup database
	@mkdir -p backups
	docker-compose exec mysql mysqldump -u resort_user -p resort_voucher > backups/db-backup-$$(date +%Y%m%d-%H%M%S).sql
	@echo "Database backed up to backups/"

db-restore: ## Restore database (use: make db-restore file=backups/db-backup.sql)
	docker-compose exec -T mysql mysql -u resort_user -p resort_voucher < $(file)
	@echo "Database restored from $(file)"

# ===================================================================
# Deployment
# ===================================================================
deploy-prod: ## Deploy to production
	@echo "Building production images..."
	docker-compose build --no-cache
	@echo "Starting services..."
	docker-compose up -d
	@echo "Running migrations..."
	docker-compose exec app php artisan migrate --force
	@echo "Caching configuration..."
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache
	@echo "Deployment complete!"

fresh-install: ## Fresh installation
	@echo "Building images..."
	make build
	@echo "Starting services..."
	make up
	@echo "Waiting for services..."
	sleep 15
	@echo "Copying environment file..."
	cp .env.docker .env
	@echo "Generating application key..."
	make key-generate
	@echo "Running migrations..."
	make migrate
	@echo "Creating storage link..."
	make storage-link
	@echo "Fresh installation complete!"

# ===================================================================
# Cleanup
# ===================================================================
clean: ## Remove all containers, volumes, and images
	docker-compose down -v --rmi all --remove-orphans

clean-logs: ## Clean Laravel logs
	docker-compose exec app sh -c "rm -f storage/logs/*.log"
	@echo "Logs cleaned!"

prune: ## Prune Docker system
	docker system prune -af --volumes

# ===================================================================
# Security
# ===================================================================
security-check: ## Run security audit
	docker-compose exec app composer audit
	@echo "Security check complete!"

permissions-fix: ## Fix file permissions
	docker-compose exec app chmod -R 775 storage bootstrap/cache
	@echo "Permissions fixed!"

# ===================================================================
# Monitoring
# ===================================================================
status: ## Show container status and health
	@docker-compose ps
	@echo "\n=== Service Health ==="
	@docker inspect resort_app --format='{{.State.Health.Status}}' 2>/dev/null && echo "App: Healthy" || echo "App: Unhealthy"
	@docker inspect resort_mysql --format='{{.State.Health.Status}}' 2>/dev/null && echo "MySQL: Healthy" || echo "MySQL: Unhealthy"
	@docker inspect resort_redis --format='{{.State.Health.Status}}' 2>/dev/null && echo "Redis: Healthy" || echo "Redis: Unhealthy"

stats: ## Show container resource usage
	docker stats resort_app resort_nginx resort_mysql resort_redis

top: ## Show running processes in containers
	docker-compose top
