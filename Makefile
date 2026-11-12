# Project tasks. Everything runs inside Laravel Sail (Docker).
# Run `make` or `make help` to list targets.

SAIL := ./vendor/bin/sail
export WWWUSER  ?= $(shell id -u)
export WWWGROUP ?= $(shell id -g)

.DEFAULT_GOAL := help
.PHONY: help start stop down restart ps logs shell install setup dev build test test-filter lint lint-fix migrate cache-clear artisan

help: ## Show this help
	@awk 'BEGIN {FS = ":.*##"} /^[a-zA-Z_-]+:.*##/ {printf "  \033[33m%-14s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

start: ## Start the containers in the background
	$(SAIL) up -d

stop: ## Stop the containers (keeps them, so start is fast)
	$(SAIL) stop

down: ## Stop and remove the containers and network
	$(SAIL) down

restart: stop start ## Restart the containers

ps: ## Show container status
	$(SAIL) ps

logs: ## Follow container logs
	$(SAIL) logs -f

shell: ## Open a shell in the app container
	$(SAIL) shell

install: ## Install Composer and npm dependencies
	$(SAIL) composer install
	$(SAIL) npm install

setup: install ## First-time setup: dependencies, .env, key, migrations, assets
	@test -f .env || cp .env.example .env
	$(SAIL) artisan key:generate
	$(SAIL) artisan migrate --force
	$(SAIL) npm run build

dev: ## Start the Vite dev server (hot reload)
	$(SAIL) npm run dev

build: ## Build production assets
	$(SAIL) npm run build

test: ## Run the test suite
	$(SAIL) artisan test

test-filter: ## Run matching tests: make test-filter F=PagesTest
	$(SAIL) artisan test --filter=$(F)

lint: ## Check code style (Pint, no changes)
	$(SAIL) bin pint --test

lint-fix: ## Fix code style with Pint
	$(SAIL) bin pint

migrate: ## Run database migrations
	$(SAIL) artisan migrate

cache-clear: ## Clear config, route, view and application caches
	$(SAIL) artisan optimize:clear

artisan: ## Run any artisan command: make artisan C="route:list"
	$(SAIL) artisan $(C)
