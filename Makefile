SAIL=./vendor/bin/sail

up:
	$(SAIL) up -d

down:
	$(SAIL) down

rebuild:
	$(SAIL) build --no-cache
	$(SAIL) up -d

logs:
	$(SAIL) logs -f

artisan:
	$(SAIL) artisan $(cmd)

npm:
	$(SAIL) npm $(cmd)

composer:
	$(SAIL) composer $(cmd)

dev:
	$(SAIL) npm run dev -- --host 0.0.0.0 --port $${VITE_PORT:-5173}

build:
	$(SAIL) npm run build
