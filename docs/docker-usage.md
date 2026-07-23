# Docker Usage in This Project

This project uses Docker to run the Laravel application, MySQL database, and WebSocket server together in a local development environment.

## Components

- `Dockerfile`
  - Builds the PHP/Laravel web application container.
  - Based on `richarvey/nginx-php-fpm:3.1.6`.
  - Copies the project files into `/var/www/html`.
  - Installs Composer dependencies.
  - Sets ownership on Laravel storage/cache folders.
  - Exposes port `6767`.
  - Uses `docker-entrypoint.sh` to initialize the app before starting the server.

- `docker-compose.yml`
  - Defines three services: `db`, `app`, and `ws`.

### `db`

- Runs MySQL 8.0.
- Publishes port `3306` on the host.
- Uses environment variables:
  - `MYSQL_ROOT_PASSWORD=secret`
  - `MYSQL_DATABASE=parcel_system`
- Persists database data to the `db_data` volume.
- Includes a healthcheck so the app waits until MySQL is ready.

### `app`

- Builds from the project `Dockerfile`.
- Publishes port `6767` to the host.
- Mounts the project directory into the container so code changes are available immediately.
- Uses environment variables for the Laravel app and database connection:
  - `APP_ENV=local`
  - `APP_DEBUG=true`
  - `APP_URL=http://localhost:6767`
  - `DB_CONNECTION=mysql`
  - `DB_HOST=db`
  - `DB_PORT=3306`
  - `DB_DATABASE=parcel_system`
  - `DB_USERNAME=root`
  - `DB_PASSWORD=secret`
- Depends on `db`, and waits until the database service is healthy.

### `ws`

- Runs a Node.js container for the WebSocket server.
- Uses `node:20-alpine`.
- Mounts the project directory into `/app`.
- Publishes port `8080`.
- Installs the `ws` package and starts `ws-server.js`.
- Depends on the `app` service starting first.

## How to Run

From the project root, run:

```bash
docker compose up --build
```

This starts:

- the MySQL database (`db`)
- the Laravel application server (`app`)
- the WebSocket server (`ws`)

Access the application at:

- `http://localhost:6767`

## Notes

- The `app` service mounts the project folder, so file edits are reflected immediately.
- The web app container uses the database host name `db` to connect to MySQL.
- The entrypoint script handles bootstrapping before the web server starts.
- The WebSocket server is kept separate from the PHP container and listens on port `8080`.
