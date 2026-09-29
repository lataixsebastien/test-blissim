COMPOSE := docker compose

.DEFAULT_GOAL := help
.PHONY: help up down restart logs ps build test phpstan cs-check cs-fix check cache-clear vendor

help:
	@$(info make up           Demarrer et construire les conteneurs)
	@$(info make down         Arreter les conteneurs en conservant les donnees)
	@$(info make restart      Redemarrer les services)
	@$(info make logs         Suivre les logs)
	@$(info make ps           Afficher les services)
	@$(info make build        Reconstruire les images Docker)
	@$(info make test         Lancer les tests PHP)
	@$(info make phpstan      Analyse statique PHP niveau 6)
	@$(info make cs-check     Verifier le style PHP sans modifier les fichiers)
	@$(info make cs-fix       Corriger le style PHP)
	@$(info make check        Tests, analyse statique, style, Symfony et build React)
	@$(info make cache-clear  Vider le cache Symfony)
	@$(info make vendor       Copier vendor en local pour votre IDE)

up:
	$(COMPOSE) up -d --build

down:
	$(COMPOSE) down

restart:
	$(COMPOSE) restart

logs:
	$(COMPOSE) logs -f --tail=100

ps:
	$(COMPOSE) ps

build:
	$(COMPOSE) build

test:
	$(COMPOSE) exec --user www-data -T backend composer test

phpstan:
	$(COMPOSE) exec --user www-data -T backend composer phpstan

cs-check:
	$(COMPOSE) exec --user www-data -T backend composer cs-check

cs-fix:
	$(COMPOSE) exec --user www-data -T backend composer cs-fix

check: test phpstan cs-check
	$(COMPOSE) exec --user www-data -T backend php bin/console lint:container
	$(COMPOSE) exec --user www-data -T backend php bin/console lint:yaml config
	$(COMPOSE) exec -T frontend npm run build

cache-clear:
	$(COMPOSE) exec --user www-data -T backend php bin/console cache:clear

vendor:
	$(COMPOSE) cp backend:/app/vendor/. ./backend/vendor/
