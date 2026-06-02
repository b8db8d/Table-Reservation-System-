CONTAINER_ENGINE = docker

setup:
	cp .env.docker.example .env.docker
	$(CONTAINER_ENGINE) compose up --build -d
	$(CONTAINER_ENGINE) exec reservations-app php artisan migrate:fresh --seed --force
	$(CONTAINER_ENGINE) exec reservations-app php artisan cache:clear

up:
	$(CONTAINER_ENGINE) compose up -d

down: 
	$(CONTAINER_ENGINE) compose down

status:
	$(CONTAINER_ENGINE) compose ps 
