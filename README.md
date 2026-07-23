# Parcel Management System

Comprehensive setup and developer guide for the Laravel parcel management system.

## 1. Project Overview and Architecture

This project manages parcels for students, including parcel registration, shelf assignment, payment status, collection status, and student access to parcel information. It combines a Laravel web application with a MySQL database, a Node.js WebSocket broker, and a Raspberry Pi camera scanner.

### Main components

- **Laravel application**: PHP backend, authentication, admin dashboard, student portal, parcel workflows, migrations, seeders, and Telegram notification handling.
- **MySQL database**: Stores users, students, parcels, shelves, jobs, sessions, and related application data.
- **WebSocket service**: `ws-server.js` relays scan commands and results between the browser dashboard and the scanner device in real time.
- **Raspberry Pi scanner**: `py_script/parcel_scanner.py` uses a camera, barcode decoding, and Tesseract OCR to send tracking numbers and student phone numbers to the WebSocket service.
- **Docker Compose**: Runs the web app, database, WebSocket service, and Telegram listener as separate services.

### Request and scan flow

1. A contributor opens the Laravel dashboard at `http://localhost:6767`.
2. The dashboard connects to `ws://<dashboard-host>:8080`.
3. The dashboard sends a scan command through the `ws` service.
4. The Raspberry Pi connects as a WebSocket `sender`, scans the parcel or phone number, and sends the result back.
5. The dashboard receives the result and uses the Laravel application to complete the parcel workflow.

### Important directories and files

| Path | Purpose |
| --- | --- |
| `app/` | Laravel controllers, models, providers, and console commands |
| `resources/views/` | Blade pages, including `dashboard.blade.php` and login pages |
| `resources/js/` and `resources/css/` | Frontend source files built by Vite |
| `routes/web.php` | Browser routes, authentication, admin, and student portal routes |
| `routes/api.php` | Scanner and API endpoints |
| `database/migrations/` | Database schema changes |
| `database/seeders/DatabaseSeeder.php` | Local admin account seeding |
| `py_script/parcel_scanner.py` | Raspberry Pi camera scanner client |
| `docker-compose.yml` | Local service definitions |
| `Dockerfile` | Laravel application image definition |
| `docker-entrypoint.sh` | Container startup, key generation, migrations, and seeding |
| `ws-server.js` | WebSocket broker |

## 2. Prerequisites

Install the following before setting up the project:

- Docker Desktop with Docker Compose support, or Docker Engine with the Compose plugin.
- Git.
- A GitHub or GitLab account with access to this repository.
- A modern browser such as Chrome, Edge, or Firefox.
- For frontend development outside Docker: Node.js 20 or newer and npm.
- For Raspberry Pi scanning: Raspberry Pi OS, Python 3, a compatible camera, and network access to the computer running the WebSocket service.
- For barcode and OCR scanning on the Raspberry Pi: `opencv-python`, `pyzbar`, `pytesseract`, the system `zbar` library, and Tesseract OCR.

On Windows, Docker Desktop must be running before any `docker compose` command is executed. Git Bash, PowerShell, or Windows Terminal can be used for the commands below.

## 3. Initial Setup

### 3.1 Clone the repository

```bash
git clone <repository-url>
cd Laravel
```

Replace `<repository-url>` with the repository URL provided by the project owner. Confirm that the working directory contains `artisan`, `composer.json`, and `docker-compose.yml`.

### 3.2 Create the environment file

Copy the example environment file:

```bash
copy .env.example .env
```

On macOS, Linux, or Git Bash, use:

```bash
cp .env.example .env
```

The Docker entrypoint also creates `.env` automatically when it is missing, but creating it explicitly makes configuration changes visible and predictable for contributors.

### 3.3 Configure environment values

Review `.env` before starting the stack. The important Laravel keys are:

```dotenv
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:6767
```

For the Compose database, the values must match the `app` service in `docker-compose.yml`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=parcel_system
DB_USERNAME=root
DB_PASSWORD=secret
```

`DB_HOST` must be `db` when Laravel runs inside Docker. Do not change it to `localhost`; inside the `app` container, `localhost` refers to the app container itself.

Update these values when moving beyond local development:

- `APP_URL`: use the address contributors or users will actually open.
- `APP_DEBUG`: set to `false` in production.
- `APP_KEY`: leave blank initially so the entrypoint can generate it, or provide a securely managed key for an existing deployment.
- `DB_PASSWORD` and `MYSQL_ROOT_PASSWORD`: replace the development password and keep both sides consistent.
- `MAIL_*`: configure these if email notifications are enabled.
- `TELEGRAM_BOT_TOKEN`: configure a real token only through a secret-management method. The current Compose file contains a token value and should be treated as exposed; rotate it and avoid committing replacement secrets.

### 3.4 Configure network and Raspberry Pi values

There are no Raspberry Pi, hotspot SSID, or hotspot password keys in `.env.example` at present. The scanner connection is currently hard-coded in:

```text
py_script/parcel_scanner.py
```

Change the `WS_URL` constant from its current value, `ws://100.86.104.80:8080`, to the WebSocket address reachable from the Raspberry Pi:

```python
WS_URL = "ws://<computer-ip>:8080"
```

Use the laptop or host computer's IP address on the same Wi-Fi or hotspot network, not the Raspberry Pi's own IP address. Port `8080` must remain reachable. The browser dashboard uses `window.location.hostname`, so it normally connects to the same host from which the dashboard was opened.

Configure the hotspot SSID and password in the Raspberry Pi operating system's network settings, not in Laravel's `.env` file. When the network changes, update both the Pi's connection and `WS_URL` if the host computer's reachable IP changes. Do not commit Wi-Fi passwords, bot tokens, or other credentials.

## 4. Running the Application with Docker

### 4.1 Build and start the services

From the project root, run:

```bash
docker-compose up -d --build
```

The equivalent modern command is:

```bash
docker compose up -d --build
```

The Compose stack contains these services:

| Service | Purpose | Host address |
| --- | --- | --- |
| `app` | Laravel and Nginx/PHP-FPM web application | `http://localhost:6767` |
| `db` | MySQL 8.0 database | `localhost:3306` |
| `ws` | Node.js WebSocket broker | `ws://localhost:8080` |
| `telegram` | Laravel Telegram listener | No public port |

The `app` service waits for the MySQL healthcheck. `docker-entrypoint.sh` then generates the Laravel key if needed, runs migrations, seeds the database, and starts the web server.

Check service status:

```bash
docker-compose ps
```

Open the application at <http://localhost:6767>. The WebSocket service should be available at `ws://localhost:8080`.

### 4.2 Run Laravel commands inside Docker

Use the `app` container for Laravel and Composer commands:

```bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan migrate:status
```

For a fresh local database, this recreates tables and runs the seeder. This is destructive and should not be used against a shared or production database:

```bash
docker-compose exec app php artisan migrate:fresh --seed
```

Useful maintenance commands include:

```bash
docker-compose exec app php artisan optimize:clear
docker-compose exec app php artisan route:list
docker-compose exec app php artisan storage:link
```

### 4.3 Stop or reset the stack

Stop containers while keeping the MySQL volume:

```bash
docker-compose down
```

Stop containers and remove the database volume. This deletes local database data:

```bash
docker-compose down -v
```

The `db_data` volume is what persists MySQL data between normal `docker-compose down` and later starts.

### 4.4 Frontend assets

The `app` image installs PHP dependencies. If you are changing Vite or frontend assets, install Node dependencies on the host and run:

```bash
npm install
npm run dev
```

For a production-style asset build:

```bash
npm run build
```

The `ws` service installs the `ws` package at startup and launches `ws-server.js` automatically.

## 5. Network & Tailscale Configuration

Tailscale provides a private network between the developer's laptop and Raspberry Pi. Both devices must be connected to the developer's own Tailscale network, or tailnet. Sign the Pi into the same account used by the laptop; a device in another tailnet cannot use these addresses unless it has been explicitly shared.

### 5.1 Authenticate the Raspberry Pi

On the Raspberry Pi, force authentication to the developer's Tailscale account:

```bash
sudo tailscale up --force-reauth
```

Open the authentication URL shown by the command and authorize the Pi. Confirm its connection:

```bash
tailscale status
```

Find the Pi's new Tailscale IPv4 address:

```bash
tailscale ip -4
```

Record the returned `100.x.x.x` address. Also find the laptop's Tailscale IPv4 address, because the WebSocket server runs on the laptop.

### 5.2 Configure `.env` and the scanner address

The current `.env.example` does not contain a Raspberry Pi or Tailscale key. If you are adding environment-backed Pi configuration, add this key to `.env` after copying the example file:

```dotenv
RASPI_TAILSCALE_IP=100.x.x.x
```

Replace the placeholder with the Pi address returned by `tailscale ip -4`. This key is not currently read by the application, so it will have no effect until Laravel or another component is updated to use it.

For the current scanner workflow, update the `WS_URL` constant in `py_script/parcel_scanner.py` to use the **laptop's** Tailscale IP and Docker's published WebSocket port:

```python
WS_URL = "ws://<laptop-tailscale-ip>:8080"
```

For example:

```python
WS_URL = "ws://100.64.0.25:8080"
```

Do not put the Pi's IP in `WS_URL`: the Pi is the client and connects to the WebSocket server on the laptop. Do not commit `.env`, Tailscale credentials, Wi-Fi passwords, or private keys.

### 5.3 Docker and Tailscale communication

The `ws` service is published by `docker-compose.yml` on host port `8080`:

```yaml
ports:
	- "8080:8080"
```

The Raspberry Pi connects to `ws://<laptop-tailscale-ip>:8080`. Tailscale runs on the laptop host; Docker forwards that host port to the `ws` container. The Pi does not need to join the Docker network and Tailscale does not need to run inside a container.

If a future Laravel container needs to contact the Pi directly, use the Pi's `RASPI_TAILSCALE_IP` value rather than a Docker service name. The current scanner direction is Pi to laptop WebSocket.

When accessing the dashboard from another Tailscale device, open `http://<laptop-tailscale-ip>:6767`; the dashboard derives its WebSocket host from `window.location.hostname`.

### 5.4 Verify Tailscale connectivity

From the Raspberry Pi, test the laptop's Tailscale address and WebSocket port:

```bash
ping <laptop-tailscale-ip>
nc -vz <laptop-tailscale-ip> 8080
```

Then start Docker and the scanner:

```bash
docker-compose up -d --build
python3 py_script/parcel_scanner.py
```

Check the broker logs with `docker-compose logs -f ws` and look for `WebSocket identified as sender` in the scanner output.

## 6. Raspberry Pi Setup and Credentials

### 5.1 Connect the Raspberry Pi

1. Connect the Raspberry Pi and the host computer to the same hotspot or LAN.
2. On the Pi, verify its network address with `hostname -I`.
3. Verify that the host computer is reachable from the Pi, for example with `ping <computer-ip>`.
4. Set `WS_URL` in `py_script/parcel_scanner.py` to `ws://<computer-ip>:8080`.
5. Install the scanner dependencies and connect the camera.
6. Start the scanner from the project directory:

```bash
python3 py_script/parcel_scanner.py
```

The scanner identifies itself as a WebSocket `sender`. The browser dashboard is the `dashboard` client. Scan commands include `trigger-add`, `trigger-phone-scan`, and `cancel-scan`; scan results include `tracking_number` and `student_phone`.

### 5.2 Default application login

The database seeder creates or updates this local admin account:

- **Email:** `admin@gmail.com`
- **Initial password:** `admin123`

Change this password immediately for any shared, hosted, or production environment. The seeder is in `database/seeders/DatabaseSeeder.php`; changing the seeded credentials requires updating that file and rerunning the seeder. Student accounts are created through the admin interface, and their initial password is their IC number according to `routes/web.php`.

The Telegram bot token currently present in `docker-compose.yml` is also a credential. Rotate it before using the project outside a private local environment.

## 7. Troubleshooting and Common Issues

### Containers do not start

Check status and logs:

```bash
docker-compose ps
docker-compose logs --tail=100 app
docker-compose logs --tail=100 db
docker-compose logs --tail=100 ws
docker-compose logs --tail=100 telegram
```

Follow a service log while reproducing the issue:

```bash
docker-compose logs -f app
```

If MySQL is still starting, wait for the `db` healthcheck before running migrations manually. If the database is disposable local data, `docker-compose down -v` followed by `docker-compose up -d --build` can clear an invalid local volume.

### Cannot open the Laravel application

- Confirm Docker Desktop is running.
- Run `docker-compose ps` and confirm `app` is running.
- Check whether another process already uses port `6767`.
- Browse to <http://localhost:6767>, not the container port directly.
- Review `APP_URL` and the `app` logs.

### Database connection errors

- In Docker, use `DB_HOST=db`, `DB_PORT=3306`, database `parcel_system`, username `root`, and the password configured in Compose.
- Do not use `127.0.0.1` or `localhost` as `DB_HOST` from inside the `app` container.
- Confirm `db` is healthy with `docker-compose ps` and inspect `docker-compose logs db`.
- If credentials were changed, update both the `db` environment and the `app` environment, then recreate the containers.

### Dashboard cannot connect to the WebSocket service

- Confirm `ws` is running: `docker-compose logs -f ws`.
- Confirm port `8080` is not blocked or already in use.
- Open the dashboard using the host address that the scanner can reach; the dashboard constructs its URL from `window.location.hostname`.
- If the dashboard is opened from another computer, use `http://<computer-ip>:6767` instead of `localhost`.
- Allow inbound TCP ports `6767` and `8080` through the host firewall when required.

### Raspberry Pi cannot connect

- Confirm the Pi and host computer are connected to the same hotspot or LAN, unless a routed or VPN connection is intentionally configured.
- Test the host from the Pi: `ping <computer-ip>`.
- Test the WebSocket port from the Pi with an available network utility, such as `nc -vz <computer-ip> 8080`.
- Confirm `WS_URL` in `py_script/parcel_scanner.py` uses the host computer's current IP and port `8080`.
- Check the host firewall and `docker-compose logs ws`.
- If the hotspot assigns a new host IP after reconnecting, update `WS_URL` and restart the scanner.

### Scanner starts but does not scan

- Confirm a camera is detected and accessible by the Pi user.
- Confirm Python packages, Tesseract OCR, and the barcode `zbar` dependency are installed.
- Start the scanner from the project root so the script and dependencies resolve consistently.
- Look for `WebSocket identified as sender` in the scanner output.
- Start a scan from the dashboard and watch both the scanner output and `ws` logs.

### Changes are not visible

- The Compose `app` service mounts the repository into `/var/www/html`, so PHP and Blade changes should be visible after refreshing.
- Clear Laravel caches with `docker-compose exec app php artisan optimize:clear`.
- Rebuild frontend assets with `npm run build` or run `npm run dev` during frontend development.
- Rebuild the image after changing `Dockerfile` or other image-build dependencies: `docker-compose up -d --build`.

## Developer Workflow

Before opening a pull request:

1. Start the required Docker services.
2. Run migrations and seed a disposable local database when schema or seed data changes.
3. Exercise admin login, student login, parcel add/collect flows, and scanner connectivity when those areas change.
4. Run `npm run build` after frontend changes.
5. Do not commit `.env`, Wi-Fi credentials, Raspberry Pi private keys, Telegram tokens, or other secrets.
6. Keep migrations, controllers, models, Blade views, and route changes focused and document behavior changes in `docs/` when useful.

Additional focused documentation is available in:

- [`docs/docker-usage.md`](docs/docker-usage.md)
- [`docs/python-usage.md`](docs/python-usage.md)
- [`docs/websocket-usage.md`](docs/websocket-usage.md)
- [`docs/ws-sender-integration.md`](docs/ws-sender-integration.md)
