.PHONY: help up down build shell migrate seed fresh logs ps restart

# Variables
DC = docker compose
PHP = $(DC) exec app php

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# ─── Cycle de vie ─────────────────────────────────────────────────────────────

setup: ## Premier démarrage : copie .env, build, démarre, migrate
	@[ -f .env ] || cp .env.docker .env
	$(DC) build
	$(DC) up -d
	@echo "Attente du démarrage de MySQL..."
	@sleep 5
	$(PHP) artisan key:generate
	$(PHP) artisan migrate --seed
	@echo "\n✓ Application disponible sur http://localhost:$$(grep APP_PORT .env | cut -d= -f2 || echo 8000)"

up: ## Démarre tous les services
	$(DC) up -d

up-dev: ## Démarre avec le serveur Vite (hot reload)
	$(DC) --profile dev up -d

down: ## Arrête tous les services
	$(DC) down

build: ## Rebuild les images
	$(DC) build --no-cache

restart: ## Redémarre un service (ex: make restart s=app)
	$(DC) restart $(s)

ps: ## Liste les services en cours
	$(DC) ps

logs: ## Affiche les logs (ex: make logs s=app)
	$(DC) logs -f $(s)

# ─── Laravel ──────────────────────────────────────────────────────────────────

shell: ## Ouvre un shell dans le conteneur app
	$(DC) exec app bash

migrate: ## Lance les migrations
	$(PHP) artisan migrate

seed: ## Lance les seeders
	$(PHP) artisan db:seed

fresh: ## Recrée la BDD et reseede
	$(PHP) artisan migrate:fresh --seed

artisan: ## Lance une commande artisan (ex: make artisan cmd="route:list")
	$(PHP) artisan $(cmd)

tinker: ## Ouvre Laravel Tinker
	$(PHP) artisan tinker

# ─── Tests ────────────────────────────────────────────────────────────────────

test: ## Lance les tests
	$(DC) exec app ./vendor/bin/pest

test-coverage: ## Lance les tests avec couverture
	$(DC) exec app ./vendor/bin/pest --coverage

# ─── Assets ───────────────────────────────────────────────────────────────────

npm-install: ## Installe les dépendances npm
	$(DC) run --rm vite npm install

npm-build: ## Build les assets pour la production
	$(DC) run --rm vite npm run build
