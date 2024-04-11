build:
	docker build \
	--build-arg="UID=$(shell id -u)" \
	--build-arg="GID=$(shell id -g)" \
	. -t zweist_image -f Dockerfile

composer-install:
	docker run -w /app -v $(shell pwd):/app zweist_image composer install

composer-update:
	docker run -w /app -v $(shell pwd):/app zweist_image composer update

phpunit:
	docker run -w /app -v $(shell pwd):/app zweist_image vendor/bin/phpunit

coverage:
	docker run -w /app -v $(shell pwd):/app zweist_image vendor/bin/phpunit --coverage-html temp/coverage

phpstan:
	docker run -w /app -v $(shell pwd):/app zweist_image vendor/bin/phpstan

ecs:
	docker run -w /app -v $(shell pwd):/app zweist_image vendor/bin/ecs

ecs-fix:
	docker run -w /app -v $(shell pwd):/app zweist_image vendor/bin/ecs --fix