
# Configuration folder
CONFIG_FOLDER = .infrastructure

# ENV=dev|prod (default: dev)
ENV ?= dev

ifeq ($(ENV),prod)
	export COMPOSE_FILE = $(CONFIG_FOLDER)/compose.yaml:$(CONFIG_FOLDER)/compose.prod.yaml
else
	export COMPOSE_FILE = $(CONFIG_FOLDER)/compose.yaml:$(CONFIG_FOLDER)/compose.override.yaml
endif

# Executables (local)
COMPOSE_ENV_FILES = $(wildcard .env .env.local .env.$(ENV) .env.$(ENV).local)
DOCKER_COMP = docker compose $(foreach file,$(COMPOSE_ENV_FILES),--env-file $(file))

# Docker containers
PHP_CONT = $(DOCKER_COMP) exec php

# Executables
PHP      = $(PHP_CONT) php
COMPOSER = $(PHP_CONT) composer
SYMFONY  = $(PHP) bin/console

# Misc
.DEFAULT_GOAL = help
.PHONY        : help build up start stop down ps logs run sh composer vendor sf cc test coverage

## —— 🎵 🐳 The Workfolio Makefile 🐳 🎵 ——————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
build: ## Builds the Docker images
	$(DOCKER_COMP) build --pull --no-cache

up: ## Start the docker hub in detached mode (no logs)
	$(DOCKER_COMP) up --detach

start: build up ## Build and start the containers

stop: ## Stop containers without removing them or their volumes
	$(DOCKER_COMP) stop

run: ## Run a one-off command, pass container options and command with c=
	@$(eval c ?=php php bin/console about)
	$(DOCKER_COMP) run --rm --no-deps $(c)

down: ## Stop the docker hub
	$(DOCKER_COMP) down --remove-orphans

ps: ## Show container status
	$(DOCKER_COMP) ps

logs: ## Show live logs; override options with c='--tail=100'
	@$(eval c ?=--tail=0 --follow)
	$(DOCKER_COMP) logs $(c)

sh: ## Connect to the FrankenPHP container
	$(PHP_CONT) sh

bash: ## Connect to the FrankenPHP container via bash so up and down arrows go to previous commands
	$(PHP_CONT) bash

test: ## Start tests with phpunit, pass the parameter "c=" to add options to phpunit, example: make test c="--group e2e --stop-on-failure"
	@$(eval c ?=)
	@$(DOCKER_COMP) exec -e APP_ENV=test php bin/phpunit $(c)

coverage: ## Run tests and generate text, Clover and HTML coverage reports in var/coverage
	@$(DOCKER_COMP) exec php mkdir -p var/coverage
	@$(DOCKER_COMP) exec -e APP_ENV=test -e XDEBUG_MODE=coverage php bin/phpunit --coverage-text --coverage-clover var/coverage/clover.xml --coverage-html var/coverage/html
	@$(COMPOSER) coverage-ratchet


## —— Composer 🧙 ——————————————————————————————————————————————————————————————
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

vendor: ## Install vendors according to the current composer.lock file
vendor: c=install --prefer-dist --no-dev --no-progress --no-scripts --no-interaction
vendor: composer

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
sf: ## List all Symfony commands or pass the parameter "c=" to run a given command, example: make sf c=about
	@$(eval c ?=)
	@$(SYMFONY) $(c)

cc: c=c:c ## Clear the cache
cc: sf
