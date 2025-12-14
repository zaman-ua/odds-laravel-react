SAIL=./vendor/bin/sail

init:
	@test -f .env || (cp .env.example .env && echo "Created .env from .env.example")
	composer install
	$(SAIL) build #--no-cache
	$(SAIL) up -d
	@echo "Fixing permissions..."
	#sudo rm -f storage/logs/laravel.log || true
	#sudo mkdir -p storage/logs bootstrap/cache
	#sudo chown -R $$(id -u):$$(id -g) storage bootstrap/cache
	#sudo chmod -R ug+rwX storage bootstrap/cache
	#touch storage/logs/laravel.log
	#chmod 664 storage/logs/laravel.log
	$(SAIL) artisan key:generate
	$(SAIL) artisan migrate
	$(SAIL) npm -v
	$(SAIL) npm install
	$(SAIL) npm run build
	@echo "Open: http://localhost:8088/lines"
	@echo "Open redis: http://localhost:5540"


up:
	$(SAIL) up -d
	@echo "Open: http://localhost:8088/lines"
	@echo "Open redis: http://localhost:5540"

down:
	$(SAIL) down

rebuild:
	$(SAIL) build --no-cache
	$(SAIL) up -d

build:
	$(SAIL) up -d --build

artisan:
	$(SAIL) artisan $(cmd)

fresh:
	$(SAIL) artisan migrate:fresh --seed



composer:
	$(SAIL) composer $(cmd)

npm:
	$(SAIL) npm $(cmd)

npm-dev:
	$(SAIL) npm run dev -- --host 0.0.0.0 --port $${VITE_PORT:-5173}

npm-build:
	$(SAIL) npm run build


ps:
	$(SAIL) ps

logs:
	$(SAIL) logs -f --tail=200

logs-web:
	$(SAIL) logs -f laravel.test

logs-queue:
	$(SAIL) logs -f queue

logs-scheduler:
	$(SAIL) logs -f scheduler

logs-redis:
	$(SAIL) logs -f redis

logs-mysql:
	$(SAIL) logs -f mysql
