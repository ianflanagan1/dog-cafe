PHP_DIRECTORY 							:= app
DOCKER_DIRECTORY						:= docker

IMAGE_NAMESPACE 						:= ianflanagan1

PHP_DOCKERFILE							:= $(DOCKER_DIRECTORY)/php.Dockerfile
PHP_PROD_IMAGE 							:= $(IMAGE_NAMESPACE)/dog-cafe-php-prod
PHP_DEV_IMAGE 							:= $(IMAGE_NAMESPACE)/dog-cafe-php-dev

NGINX_DOCKERFILE						:= $(DOCKER_DIRECTORY)/nginx.Dockerfile
NGINX_PROD_IMAGE						:= $(IMAGE_NAMESPACE)/dog-cafe-nginx-prod
NGINX_DEV_IMAGE							:= $(IMAGE_NAMESPACE)/dog-cafe-nginx-dev

COMPOSE_PROJECT							:= dog-cafe
COMPOSE_PHP_CONTAINER 			:= $(COMPOSE_PROJECT)-php
COMPOSE_NGINX_CONTAINER 		:= $(COMPOSE_PROJECT)-nginx
COMPOSE_POSTGRES_CONTAINER 	:= $(COMPOSE_PROJECT)-postgres
COMPOSE_REDIS_CONTAINER 		:= $(COMPOSE_PROJECT)-redis

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
	docker compose -f ./compose.yaml --env-file ./$(PHP_DIRECTORY)/.env up --build

up-detach:
	docker compose -f ./compose.yaml --env-file ./$(PHP_DIRECTORY)/.env up --build --detach

down:
	docker compose -f ./compose.yaml down

down-delete:
	docker compose -f ./compose.yaml down --volumes --remove-orphans

prod-up:
	docker compose -f ./compose.production.yaml --env-file ./$(PHP_DIRECTORY)/.env.production up --build

prod-down:
	docker compose -f ./compose.production.yaml down


##### TESTING #####

# Example: make test tests/Unit/ContainerTest.php
test:
	docker exec -it \
		$(USER_STRING_PHP_FPM_NGINX) \
		$(COMPOSE_PHP_CONTAINER) \
			php ./vendor/bin/phpunit --configuration phpunit.xml --testdox $(filter-out $@,$(MAKECMDGOALS))

phpstan:
	cd $(PHP_DIRECTORY) && \
		php ./vendor/bin/phpstan analyse --no-progress --memory-limit=1G --configuration=phpstan.neon $(filter-out $@,$(MAKECMDGOALS))

pint:
	cd $(PHP_DIRECTORY) && \
  	php ./vendor/bin/pint -v --config=pint.json
	cd $(PHP_DIRECTORY) && \
  	php ./vendor/bin/pint -v --config=pint-tests.json tests/


##### PHP CONTAINER #####

# Example: make php-exec A="id"
exec-php: arg-check
	docker exec -it \
		$(USER_STRING_PHP_FPM_NGINX) \
		$(COMPOSE_PHP_CONTAINER) \
			sh -c "umask 0002 && $(A)"

shell-php: A=/bin/sh
shell-php: exec-php

define composer-cmd
	docker exec -it \
		$(USER_STRING_PHP_FPM_NGINX) \
		-e XDEBUG_MODE=off \
		-e COMPOSER_CACHE_DIR=/tmp/composer-cache \
		$(COMPOSE_PHP_CONTAINER) \
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
	docker exec -it \
		$(USER_STRING_PHP_FPM_NGINX) \
		$(COMPOSE_NGINX_CONTAINER) \
			sh -c "umask 0002 && $(A)"

shell-nginx: A=/bin/sh
shell-nginx: exec-nginx


##### OTHER CONTAINERS #####

define exec-cmd
	docker exec -it $(USER_STRING_OTHER) $(1)
endef

exec-postgres: arg-check
	$(call exec-cmd,$(COMPOSE_POSTGRES_CONTAINER) $(A))
exec-redis: arg-check
	$(call exec-cmd,$(COMPOSE_REDIS_CONTAINER) $(A))

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
