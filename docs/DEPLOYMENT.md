# Deployment Guide

## Production Deployment

### Prerequisites

1. **Server Requirements:**
   - Ubuntu 22.04 or similar
   - Docker 24.0+
   - Docker Compose 2.0+
   - 4GB+ RAM
   - 20GB+ disk space

2. **External Services:**
   - OpenAI API account with credits
   - Stripe account (for billing)
   - Domain name with SSL certificate

### Step 1: Server Setup

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo apt install docker-compose-plugin

# Create app directory
sudo mkdir -p /var/www/saasbot
cd /var/www/saasbot
```

### Step 2: Clone Repository

```bash
git clone https://github.com/Y4xx/B2B-bots.git .
```

### Step 3: Environment Configuration

```bash
# Copy environment files
cp .env.example .env
cp backend-laravel/.env.example backend-laravel/.env

# Edit .env files with production values
nano .env
nano backend-laravel/.env
```

**Required variables:**
```env
# Backend
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_KEY=base64:... # Generate with: php artisan key:generate

# Database
DB_PASSWORD=secure_random_password

# Stripe
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# OpenAI
OPENAI_API_KEY=sk-...

# Qdrant
QDRANT_HOST=qdrant
QDRANT_PORT=6333
```

### Step 4: Build and Start Services

```bash
# Build containers
docker-compose build

# Start services
docker-compose up -d

# Check status
docker-compose ps
```

### Step 5: Initialize Database

```bash
# Run migrations
docker-compose exec laravel-app php artisan migrate --force

# Clear caches
docker-compose exec laravel-app php artisan config:cache
docker-compose exec laravel-app php artisan route:cache
docker-compose exec laravel-app php artisan view:cache
```

### Step 6: SSL Configuration

```bash
# Install Certbot
sudo apt install certbot

# Get SSL certificate
sudo certbot certonly --standalone -d yourdomain.com

# Update nginx config with SSL
# Edit infra/nginx/nginx.conf to add SSL configuration

# Restart nginx
docker-compose restart nginx
```

### Step 7: Build Frontend for Production

```bash
# Build React frontend
cd frontend-react
npm install
npm run build

# Build widget
cd ../widget
npm install
npm run build
```

### Step 8: Configure Stripe Webhooks

1. Go to Stripe Dashboard → Webhooks
2. Add endpoint: `https://yourdomain.com/api/webhooks/stripe`
3. Select events:
   - checkout.session.completed
   - customer.subscription.updated
   - customer.subscription.created
   - customer.subscription.deleted
   - invoice.payment_succeeded
   - invoice.payment_failed
4. Copy webhook signing secret to .env

### Step 9: Monitor Logs

```bash
# View all logs
docker-compose logs -f

# View specific service
docker-compose logs -f laravel-app
docker-compose logs -f python-scraper
```

## Docker Production Configuration

Create `docker-compose.prod.yml`:

```yaml
version: '3.8'

services:
  laravel-app:
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    restart: always

  nginx:
    volumes:
      - ./ssl:/etc/nginx/ssl
    restart: always

  mysql:
    volumes:
      - mysql-prod-data:/var/lib/mysql
    restart: always

  redis:
    restart: always

  qdrant:
    restart: always

volumes:
  mysql-prod-data:
```

## Scaling

### Horizontal Scaling

```bash
# Scale Laravel workers
docker-compose up -d --scale laravel-horizon=3

# Scale Python services
docker-compose up -d --scale python-scraper=2
docker-compose up -d --scale python-rag=3
```

### Load Balancing

Use nginx upstream configuration:

```nginx
upstream laravel_backend {
    least_conn;
    server laravel-app-1:8000;
    server laravel-app-2:8000;
    server laravel-app-3:8000;
}
```

## Monitoring

### Health Checks

```bash
# Check Laravel
curl http://localhost:8000/api/health

# Check Python services
curl http://localhost:8001/health
curl http://localhost:8002/health
curl http://localhost:8003/health

# Check Qdrant
curl http://localhost:6333/health
```

### Log Aggregation

Consider using:
- ELK Stack (Elasticsearch, Logstash, Kibana)
- Grafana + Loki
- DataDog
- New Relic

### Application Monitoring

```bash
# Install Laravel Telescope (development only)
cd backend-laravel
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

## Backup Strategy

### Database Backup

```bash
# Create backup script
cat > /usr/local/bin/backup-saasbot.sh << 'EOF'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
docker-compose exec -T mysql mysqldump -u root -p$DB_PASSWORD saasbot > /backups/saasbot_$DATE.sql
find /backups -name "saasbot_*.sql" -mtime +7 -delete
EOF

chmod +x /usr/local/bin/backup-saasbot.sh

# Add to crontab (daily at 2 AM)
crontab -e
0 2 * * * /usr/local/bin/backup-saasbot.sh
```

### Qdrant Backup

```bash
# Backup Qdrant snapshots
docker-compose exec qdrant ./qdrant --snapshot create
```

## Updates and Maintenance

### Update Application

```bash
# Pull latest code
git pull origin main

# Rebuild containers
docker-compose build

# Update dependencies
docker-compose exec laravel-app composer install --no-dev

# Run migrations
docker-compose exec laravel-app php artisan migrate --force

# Restart services
docker-compose restart
```

### Zero-Downtime Deployment

1. Use blue-green deployment
2. Or use rolling updates with Kubernetes
3. Or use Docker Swarm mode

## Troubleshooting

### Service Won't Start

```bash
# Check logs
docker-compose logs laravel-app

# Check container status
docker-compose ps

# Restart service
docker-compose restart laravel-app
```

### Database Connection Issues

```bash
# Check MySQL is running
docker-compose exec mysql mysql -u root -p

# Verify credentials in .env
cat backend-laravel/.env | grep DB_
```

### Queue Not Processing

```bash
# Check Horizon status
docker-compose exec laravel-app php artisan horizon:status

# Restart Horizon
docker-compose restart laravel-horizon
```

### Python Services Errors

```bash
# Check Python service logs
docker-compose logs python-scraper
docker-compose logs python-indexer
docker-compose logs python-rag

# Restart service
docker-compose restart python-scraper
```

## Security Checklist

- [ ] Change all default passwords
- [ ] Configure firewall (UFW)
- [ ] Enable HTTPS only
- [ ] Set up fail2ban
- [ ] Configure security headers
- [ ] Enable database encryption
- [ ] Rotate API keys regularly
- [ ] Set up intrusion detection
- [ ] Enable audit logging
- [ ] Configure backup strategy
- [ ] Set up monitoring alerts

## Performance Optimization

1. **Laravel:**
   - Enable OPcache
   - Use Redis for cache and sessions
   - Queue long-running tasks
   - Optimize database queries

2. **Frontend:**
   - Enable CDN for static assets
   - Compress JavaScript/CSS
   - Use lazy loading
   - Implement service workers

3. **Database:**
   - Add indexes on frequently queried columns
   - Optimize slow queries
   - Use connection pooling
   - Regular maintenance

4. **Vector Database:**
   - Tune Qdrant configuration
   - Use appropriate vector dimensions
   - Implement caching for frequent queries
