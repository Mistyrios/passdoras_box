#Get arguments of a command
# Takes the first target as command
Command := $(firstword $(MAKECMDGOALS))
# Skips the first word
Arguments := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))

# Database Constants
DATABASE_PROD_FILE_NAME=prod_dump.sql
DATABASE_CONTAINER_FILE_REPO=/database/
DATABASE_DUMP_FILE_PATH=./docker/database/local_dump_$(shell date +'%Y-%m-%d_%H:%M:%S').sql
DATABASE_FILE_REPO=./docker/database/
DATABASE_CONTAINER_NAME=pb_pgsql
DATABASE_CONTAINER_USER=root
LOCAL_DATABASE_NAME=passdoras_box

# Other Constants
PHP_CONTAINER_NAME=pb_php
NGINX_CONTAINER_NAME=pb_nginx
SHELL_GREEN_COLOR=\033[0;32m
SHELL_RED_COLOR=\033[0;31m
SHELL_RESET_COLOR=\033[0m

# Targets
%::
	@true

# List of commands available in the Makefile (Always keep it as the first target of the Makefile)
help:
	@echo "\n\033[1mCommandes make disponibles :\033[0m"
	@awk -F ':.*?## ' '/^[a-zA-Z0-9_-]+:.*?##/ { printf "  \033[36m make %-16s\033[0m 	: %s\n", $$1, $$2 }' $(MAKEFILE_LIST) | sort

# Other targets

build-assets: ## Compile les assets
	@docker exec -it $(PHP_CONTAINER_NAME) php bin/console tailwind:build --minify

dump-database: ## Fait un dump de la base de données
	@echo "$(SHELL_GREEN_COLOR)Dump de la base de données ...$(SHELL_RESET_COLOR)"
	@docker exec -it $(DATABASE_CONTAINER_NAME) pg_dump -U $(DATABASE_CONTAINER_USER) -O -x  $(LOCAL_DATABASE_NAME) > $(DATABASE_DUMP_FILE_PATH)
	@echo "$(SHELL_GREEN_COLOR)Dump terminé avec succès dans le fichier $(DATABASE_DUMP_FILE_PATH)$(SHELL_RESET_COLOR)"

drop-database: ## Supprime la base de données
	@echo "$(SHELL_GREEN_COLOR)Suppression de la base de données ...$(SHELL_RESET_COLOR)"
	@docker exec -it $(DATABASE_CONTAINER_NAME) dropdb -U $(DATABASE_CONTAINER_USER) $(LOCAL_DATABASE_NAME)
	@echo "$(SHELL_GREEN_COLOR)Suppression terminée avec succès$(SHELL_RESET_COLOR)"
	@echo "$(SHELL_GREEN_COLOR)Création de la base de données vide ...$(SHELL_RESET_COLOR)"
	@docker exec -it $(DATABASE_CONTAINER_NAME) createdb -U $(DATABASE_CONTAINER_USER) $(LOCAL_DATABASE_NAME)
	@echo "$(SHELL_GREEN_COLOR)Création terminée avec succès$(SHELL_RESET_COLOR)"

maker: ## Utilse le maker bundle
	@docker exec -it $(PHP_CONTAINER_NAME) php bin/console make:$(Arguments)

init: ## Initialise l'environnement de développement
	@make start
	@make install-vendor

install-vendor: ## Installe les dépendances Composer du projet
	@docker exec -it $(PHP_CONTAINER_NAME) composer install

migration: ##Crée une migration
	@docker exec -it $(PHP_CONTAINER_NAME) php bin/console make:migration

migrate: ## Exécute les migrations
	@docker exec -it $(PHP_CONTAINER_NAME) php bin/console doctrine:migrations:migrate

php: ## Rentre dans le container PHP
	@docker compose exec -it $(PHP_CONTAINER_NAME) bash

phpcsfixer: ## Exécute php-cs-fixer
	@docker exec -it $(PHP_CONTAINER_NAME) vendor/bin/php-cs-fixer fix -vvv --show-progress=dots

phpstan: ## Exécute phpstan
	@docker exec -it $(PHP_CONTAINER_NAME) vendor/bin/phpstan analyse --memory-limit 512M

restart: ## Redémarre l'environnement de développement
	@make stop
	@make start

reset-database: ## Réinitialise la base de données
	@make drop-database

sf: ## Exécute une commande Symfony
	@docker exec -it $(PHP_CONTAINER_NAME) php bin/console $(Arguments)

start: ## Démarre l'environnement de développement
	@docker compose up -d
	@make watch-assets

start-prod: ## Démarre l'environnement de production
	@docker compose up -d

stop: ## Arrête l'environnement de développement
	@docker compose down

update-vendor: ## Met à jour les dépendances Composer du projet
	@docker exec -it $(PHP_CONTAINER_NAME) composer update

watch-assets: ## Exécute la commande pour compiler les assets en mode watch
	@docker exec -it $(PHP_CONTAINER_NAME) php bin/console tailwind:build --watch
