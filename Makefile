up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

shell:
	docker compose exec app bash

test:
	docker compose exec app php artisan test

test-filter:
	docker compose exec app php artisan test --filter=$(filter)

migrate:
	docker compose exec app php artisan migrate

fresh:
	docker compose exec app php artisan migrate:fresh --seed

tinker:
	docker compose exec app php artisan tinker

logs:
	docker compose logs -f