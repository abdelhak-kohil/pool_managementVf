# Pool Management — High-Performance Production Deployment Guide

This guide details how to deploy the Pool Management application using Docker with maximum performance and resource utilization.

## 📦 Directory Structure

```
docker-export/
├── pool-management-images.tar   # All 3 Docker images (app + nginx + postgres)
├── docker-compose.yml           # Optimized container orchestration
├── .env                         # Environment configuration
├── docker/
│   ├── nginx.conf               # High-performance Nginx config
│   ├── php-fpm.conf             # Tuned PHP-FPM config
│   └── init-db.sh               # Database initialization script
└── README.md                    # This document
```

---

## 🚀 1. Server Preparation (OS Level)

For maximum performance, apply these system tweaks to the host server before starting Docker:

### Increase File Limits
Add to `/etc/sysctl.conf`:
```bash
fs.file-max = 2097152
net.core.somaxconn = 65535
net.ipv4.ip_local_port_range = 1024 65535
net.ipv4.tcp_max_syn_backlog = 8192
net.ipv4.tcp_tw_reuse = 1
```
Then run `sudo sysctl -p`.

### Install Docker
Ensure you are using the latest Docker Engine and Docker Compose Plugin:
```bash
sudo apt-get update
sudo apt-get install -y docker.io docker-compose-plugin
sudo usermod -aG docker $USER
```

---

## 🛠️ 2. Deployment Steps

### A. Load Images
```bash
docker load -i pool-management-images.tar
```

### B. Configure Environment
Edit the `.env` file. Ensure `DB_HOST=db` and set your production values:
```bash
nano .env
```

### C. Launch Services
```bash
docker compose up -d
```

### D. Application Optimization
Run these commands inside the app container to cache configuration and routes:
```bash
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app php artisan storage:link
docker compose exec app php artisan migrate --force
```

---

## 🏎️ 3. Performance Optimizations Included

### PHP-FPM & OPcache
- **Tuned Process Manager**: Set to `dynamic` with `max_children = 50` for handling high concurrency.
- **OPcache Maxed**: `revalidate_freq=0` (no filesystem checks), `jit=tracing`, and large buffers.

### Nginx
- **High Connections**: Configured to handle up to 65k connections.
- **Aggressive Caching**: Static assets (CSS/JS/Images) have 365-day cache headers.
- **Worker Auto-scaling**: Automatically matches CPU core count.

### PostgreSQL
- **Resource Reservation**: Reserved 1GB of RAM for the database engine to ensure stability.
- **PostgreSQL 16**: Using the latest stable version with performance improvements.

### Docker Compose
- **Resource Reservations**: Configured CPU/Memory reservations to prioritize these services on the server.
- **Networking**: `somaxconn` tweaked at the container level.

---

## 📊 4. Monitoring & Maintenance

| Goal | Command |
|---|---|
| Check Resource Usage | `docker stats` |
| Live Logs | `docker compose logs -f` |
| Health Status | `docker compose ps` |
| Performance Test | `docker compose exec app php artisan benchmark` (if available) |

---

## 🔒 Security Notes
- `expose_php=off` is set in PHP config.
- `server_tokens off` is set in Nginx.
- Ensure the server firewall only allows ports 8080 (app) and 22 (SSH).
