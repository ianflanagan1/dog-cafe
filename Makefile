PHP_DIRECTORY 							:= app
DEPLOY_DIRECTORY						:= deploy

IMAGE_NAMESPACE 						:= ianflanagan1

PHP_DOCKERFILE							:= $(DEPLOY_DIRECTORY)/php/Dockerfile
PHP_PROD_IMAGE 							:= $(IMAGE_NAMESPACE)/dog-cafe-php-prod
PHP_DEV_IMAGE 							:= $(IMAGE_NAMESPACE)/dog-cafe-php-dev

NGINX_DOCKERFILE						:= $(DEPLOY_DIRECTORY)/nginx/Dockerfile
NGINX_PROD_IMAGE						:= $(IMAGE_NAMESPACE)/dog-cafe-nginx-prod
NGINX_DEV_IMAGE							:= $(IMAGE_NAMESPACE)/dog-cafe-nginx-dev

COMPOSE											:= docker compose -f ./compose.yaml --env-file ./$(PHP_DIRECTORY)/.env

PHP_FPM_NGINX_USER					:= 65532

# Arguments
A ?=
NC ?=
ROOT ?=

# --no-cache flag
ifdef NC
	NO_CACHE_STRING = --no-cache
else
	NO_CACHE_STRING =
endif

# --user=root:root flag
ifdef ROOT
	USER_STRING_PHP_FPM_NGINX = --user root:root
	USER_STRING_OTHER					= --user root:root
else
	USER_STRING_PHP_FPM_NGINX = --user=$(PHP_FPM_NGINX_USER):$(shell id -g)
	USER_STRING_OTHER					= 
endif

arg-check:
	@if [ -z "$(A)" ]; then \
		echo "Error: Needs argument"; \
		exit 1; \
	fi


##### DOCKER COMPOSE #####

up:
	$(COMPOSE) up --build

up-detach:
	$(COMPOSE) up --build --detach

down:
	$(COMPOSE) down

down-delete:
	$(COMPOSE) down --volumes --remove-orphans

prod-up:
	docker compose -f ./compose.production.yaml --env-file ./$(PHP_DIRECTORY)/.env.production up --build

prod-down:
	docker compose -f ./compose.production.yaml down


##### TESTING #####

# Example: make test tests/Unit/ContainerTest.php
test:
	$(COMPOSE) exec \
		$(USER_STRING_PHP_FPM_NGINX) \
		php \
			php ./vendor/bin/phpunit --configuration phpunit.xml --testdox $(filter-out $@,$(MAKECMDGOALS))

phpstan:
	cd $(PHP_DIRECTORY) && \
		php ./vendor/bin/phpstan analyse --no-progress --memory-limit=1G --configuration=phpstan.neon $(filter-out $@,$(MAKECMDGOALS))

pint:
	cd $(PHP_DIRECTORY) && \
  	php ./vendor/bin/pint -v --config=pint.json
	cd $(PHP_DIRECTORY) && \
  	php ./vendor/bin/pint -v --config=pint-test.json tests/


##### PHP CONTAINER #####

# Example: make php-exec A="id"
exec-php: arg-check
	$(COMPOSE) exec \
		$(USER_STRING_PHP_FPM_NGINX) \
		php \
			sh -c "umask 0002 && $(A)"

shell-php: A=/bin/sh
shell-php: exec-php

define composer-cmd
	$(COMPOSE) exec \
		$(USER_STRING_PHP_FPM_NGINX) \
		-e XDEBUG_MODE=off \
		-e COMPOSER_CACHE_DIR=/tmp/composer-cache \
		php \
			sh -c "umask 0002 && /usr/bin/composer $(1) --no-interaction --no-progress"
endef

composer-install:
	$(call composer-cmd,install)
composer-update:
	$(call composer-cmd,update)

# Example: make composer-require A="--dev laravel/pint"
composer-require: arg-check
	$(call composer-cmd,require $(A))

# Example: make composer-remove A="--dev laravel/pint"
composer-remove: arg-check
	$(call composer-cmd,remove $(A))


##### NGINX CONTAINER #####

exec-nginx: arg-check
	$(COMPOSE) exec \
		$(USER_STRING_PHP_FPM_NGINX) \
		nginx \
			sh -c "umask 0002 && $(A)"

shell-nginx: A=/bin/sh
shell-nginx: exec-nginx


##### OTHER CONTAINERS #####

define exec-cmd
	$(COMPOSE) exec $(USER_STRING_OTHER) $(1)
endef

exec-postgres: arg-check
	$(call exec-cmd,postgres $(A))
exec-redis: arg-check
	$(call exec-cmd,redis $(A))

shell-postgres: A=/bin/sh
shell-postgres: exec-postgres
shell-redis: A=/bin/sh
shell-redis: exec-redis


###### BUILD ######

build-php-dev:
	docker build --progress=plain $(NO_CACHE_STRING) -f $(PHP_DOCKERFILE) --target dev -t $(PHP_DEV_IMAGE) ./app
build-php-prod:
	docker build --progress=plain $(NO_CACHE_STRING) -f $(PHP_DOCKERFILE) --target prod -t $(PHP_PROD_IMAGE) ./app
build-nginx-dev:
	docker build --progress=plain $(NO_CACHE_STRING) -f $(NGINX_DOCKERFILE) --target dev -t $(NGINX_DEV_IMAGE) .
build-nginx-prod:
	docker build --progress=plain $(NO_CACHE_STRING) -f $(NGINX_DOCKERFILE) --target prod -t $(NGINX_PROD_IMAGE) .



.PHONY: up down phpstan test
%:
	@:
