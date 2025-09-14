# Docker Setup Guide for Laravel Vue.js Application

## What is Docker?

Docker is a containerization platform that packages your application and its dependencies into lightweight, portable containers. Think of containers as isolated environments that run your application consistently across different machines.

## How Your Setup Works

1. **Dockerfile**: Builds your Laravel + Vue.js application image
2. **docker-compose.yml**: Orchestrates 3 services:
   - `app`: Your Laravel application (PHP-FPM)
   - `db`: MySQL database
   - `nginx`: Web server that serves your application
3. **nginx.conf**: Configuration for the web server

## Setup Instructions

### 1. Create .env file
Copy the example environment file:
```bash
cp .env.example .env
```

If you don't have .env.example, create a .env file with this content:
```
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=root
```

### 2. Generate Application Key
```bash
docker-compose run --rm app php artisan key:generate
```

### 3. Build and Start Services
```bash
# Build and start all services
docker-compose up --build

# Or run in background
docker-compose up --build -d
```

### 4. Run Database Migrations
```bash
# Run migrations
docker-compose exec app php artisan migrate

# Or if you need to refresh
docker-compose exec app php artisan migrate:fresh
```

### 5. Access Your Application
- **Web Application**: http://localhost:8080
- **Database**: localhost:3307 (username: root, password: root)
- **phpMyAdmin**: http://localhost:8081 (username: root, password: root)

## Common Commands

```bash
# Stop all services
docker-compose down

# Stop and remove volumes (WARNING: deletes database data)
docker-compose down -v

# View logs
docker-compose logs

# View logs for specific service
docker-compose logs app

# Execute commands in container
docker-compose exec app php artisan tinker
docker-compose exec app npm run dev

# Rebuild specific service
docker-compose up --build app
```

## Troubleshooting

### If containers won't start:
1. Check if ports 8080 and 3307 are available
2. Make sure Docker is running
3. Check logs: `docker-compose logs`

### If database connection fails:
1. Wait for database to fully start (can take 30-60 seconds)
2. Check if .env file has correct database settings
3. Verify database container is running: `docker-compose ps`

### If Vue.js assets don't load:
1. Rebuild the application: `docker-compose up --build`
2. Check if build directory exists in public folder
3. Run npm build manually: `docker-compose exec app npm run build`

## File Structure Explanation

- `docker/Dockerfile`: Multi-stage build for PHP + Node.js
- `docker/nginx.conf`: Web server configuration
- `docker-compose.yml`: Service orchestration
- `.env`: Environment variables (create this file)
