# Port Activity App / MSW integration

## Description
CLI job for polling timestamps from Maritime Single Window service

## Configuring container
Copy .env.template to .env and fill values

## Configuring local development environment
Copy src/lib/init_local.php.sample.php to src/lib/init_local.php and fill values

## Polling and saving VIS notifications and messages

### With docker compose
Configure container environment and
- `docker-compose build` Build container
- `docker-compose up` Start container. Will execute one polling run.
- `docker-compose stop` Stop container

### Locally
Configure development environment and
```make run```