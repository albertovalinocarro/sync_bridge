# SyncBridge

A multi-client middleware platform built with Symfony 7.4 that receives Shopify webhooks, processes them asynchronously, and syncs order and inventory data to downstream ERP/WMS systems.

Built as a learning project mirroring real-world integration platform architecture.

---

## Tech Stack

- **PHP 8.2** / **Symfony 7.4 LTS**
- **Doctrine ORM** — entity mapping and migrations
- **Symfony Messenger** — async message handling and queue processing
- **MySQL 8** — primary data store
- **Redis** — queue transport and caching
- **Nginx** — web server / reverse proxy
- **Docker & Docker Compose** — fully containerised local environment

---

## Project Structure

```
sync_bridge/
├── app/                    # Symfony application
│   ├── src/
│   │   ├── Controller/     # HTTP controllers (webhook endpoints, REST API)
│   │   ├── Entity/         # Doctrine entities (database models)
│   │   ├── Repository/     # Doctrine repositories (query logic)
│   │   ├── Message/        # Messenger message classes
│   │   └── MessageHandler/ # Messenger handlers (async processing logic)
│   ├── config/             # Symfony configuration (services, routes, packages)
│   ├── migrations/         # Doctrine database migrations
│   └── tests/              # PHPUnit test suites
├── docker/
│   ├── php/
│   │   └── Dockerfile      # PHP 8.2-FPM + Composer + Symfony CLI
│   └── nginx/
│       └── default.conf    # Nginx virtual host config
└── docker-compose.yml      # Service orchestration
```

---

## Getting Started

### Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- [VS Code](https://code.visualstudio.com/) (recommended)

### 1. Clone the repository

```bash
git clone https://github.com/YOUR_USERNAME/sync_bridge.git
cd sync_bridge
```

### 2. Start the containers

```bash
docker-compose up -d --build
```

This starts four services:

| Service            | Description               | Port            |
| ------------------ | ------------------------- | --------------- |
| `syncbridge_php`   | PHP 8.2-FPM + Symfony CLI | 9000 (internal) |
| `syncbridge_nginx` | Nginx reverse proxy       | 8080            |
| `syncbridge_mysql` | MySQL 8 database          | 3306            |
| `syncbridge_redis` | Redis cache / queue       | 6379            |

### 3. Install dependencies

```bash
docker exec -it syncbridge_php bash
cd app
composer install
```

### 4. Configure environment

Copy the example env file and update as needed:

```bash
cp app/.env app/.env.local
```

The default `DATABASE_URL` is pre-configured for the Docker MySQL service:

```env
DATABASE_URL="mysql://syncbridge:syncbridge@mysql:3306/syncbridge?serverVersion=8.0&charset=utf8mb4"
```

### 5. Run database migrations

```bash
docker exec -it syncbridge_php bash
cd app
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate
```

### 6. Verify the setup

Visit [http://localhost:8080](http://localhost:8080) — you should see the Symfony welcome page.

---

## Running Commands

All Symfony and Composer commands run inside the PHP container:

```bash
docker exec -it syncbridge_php bash
cd app

# Clear cache
php bin/console cache:clear

# List all routes
php bin/console debug:router

# List all services
php bin/console debug:container

# Run tests
php bin/phpunit

# Create a new migration
php bin/console make:migration

# Run migrations
php bin/console doctrine:migrations:migrate
```

---

## API Endpoints

### Webhooks

| Method | Path             | Description                   |
| ------ | ---------------- | ----------------------------- |
| `POST` | `/webhook/order` | Receive Shopify order webhook |

#### Example request

```bash
curl -X POST http://localhost:8080/webhook/order \
  -H "Content-Type: application/json" \
  -d '{"event":"order/created","order_id":1001}'
```

#### Example response

```json
{
  "status": "received",
  "event": "order/created"
}
```

---

## Development

### Symfony Profiler

The Symfony Web Profiler is available in `dev` mode. After any request, visit:

```
http://localhost:8080/_profiler
```

This gives you full request/response details, query logs, timeline, and memory usage — equivalent to Laravel Telescope.

### Running Tests

```bash
docker exec -it syncbridge_php bash
cd app
php bin/phpunit
```

---

## Roadmap

- [x] Docker environment (PHP, Nginx, MySQL, Redis)
- [x] Symfony 7.4 application scaffold
- [x] Webhook endpoint — order received
- [x] Doctrine entity — persist webhook payloads
- [x]Symfony Messenger — async processing pipeline
- [x] Multi-client configuration layer
- [x] Outbound sync — mock WMS/ERP integration
- [x] Failure handling — retries and dead letter queue
- [x] REST API — sync status per client
- [ ] Idempotency — duplicate webhook handling
- [ ] PHPUnit test coverage

---

## License

MIT

---

## Done by

Alberto Valiño Carro.
