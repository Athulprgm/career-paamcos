# Deployment & Production Operations Guide: MultiCred

This document outlines deployment architectures, system requirements, build steps, release procedures, and operational maintenance for the **MultiCred** application.

---

## 1. Supported Environments

1. **Local Development**:
   - Environment: Windows with XAMPP, Laravel Herd, Valet, or PHP built-in CLI server.
   - Status: **Verified in active workspace**.
2. **On-Premise Society Server / Dedicated VPS**:
   - OS: Ubuntu 22.04 LTS / Debian 12 / RHEL 9.
   - Web Server: Nginx (recommended) or Apache 2.4.
   - Status: Standard target architecture.
3. **Cloud Hosting**:
   - Platforms: Laravel Cloud, AWS (EC2/RDS), DigitalOcean, or Linode.
   - Status: Supported via standard Laravel Cloud configuration.

---

## 2. Infrastructure & System Prerequisites

- **PHP**: `^8.3` or `^8.4` (verified: PHP `8.4.25`).
  - Required PHP Extensions:
    - `bcmath` (critical for decimal-safe commission sums via `bcadd`).
    - `pdo_mysql` / `pdo` (database persistence).
    - `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`.
    - `gd` / `imagick` (for avatar image cropping via `ProfileImageService`).
    - `fileinfo` (MIME-type validation on uploads).
    - `curl` (outgoing HTTP/SMTP integration).
- **Database Engine**: MySQL `8.0+` or MariaDB `10.4+` (InnoDB engine).
- **Composer**: `^2.2` or later (verified: Composer `2.10.2`).
- **Node.js**: Node `^18.0` / `^20.0` / `^22.0` with `npm` (verified: Node `v22.23.2`, npm `10.9.8`).
- **Process Manager**: Supervisor or systemd for background queue workers.

---

## 3. Production Build & Release Pipeline

Follow this verified sequential release procedure when promoting code to staging or production:

### Step 1: Clone & Configure Environment
```bash
# Clone the repository
git clone https://github.com/pristine-agro/multicred-deposit-commission.git /var/www/multicred
cd /var/www/multicred

# Set up environment variables
cp .env.example .env
php artisan key:generate
```

Configure `.env` for production:
```env
APP_NAME="Pristine Agro MultiCred"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://multicred.pristineagro.org

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=multicred_production
DB_USERNAME=multicred_prod_user
DB_PASSWORD="STRONG_SECRET_PASSWORD"

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@pristineagro.org
MAIL_PASSWORD="SECRET_SMTP_PASSWORD"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@pristineagro.org"
MAIL_FROM_NAME="Pristine Agro MultiCred"
```

### Step 2: Install PHP & Frontend Dependencies
```bash
# Install optimized PHP dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Install frontend packages and compile production assets
npm ci
npm run build
```

### Step 3: Database Migrations & Initial Seeding
```bash
# Run database schema migrations
php artisan migrate --force

# Seed master roles, root Super Admin, and the 12 career progression ranks
php artisan db:seed --class=AgentRankSeeder --force
```

### Step 4: Storage Linking & File Permissions
```bash
# Link public storage directory for avatar images
php artisan storage:link

# Set correct permissions (Linux / Nginx)
chown -R www-data:www-data /var/www/multicred/storage /var/www/multicred/bootstrap/cache
chmod -R 775 /var/www/multicred/storage /var/www/multicred/bootstrap/cache
```

### Step 5: Optimization & Caching
```bash
# Cache configuration, routes, and views for maximum performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 4. Web Server Configuration (Nginx Example)

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name multicred.pristineagro.org;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name multicred.pristineagro.org;
    root /var/www/multicred/public;

    ssl_certificate /etc/letsencrypt/live/multicred.pristineagro.org/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/multicred.pristineagro.org/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 5. Background Queue Workers & Scheduled Tasks

### 5.1 Queue Worker (Supervisor Configuration)
Transactional emails (such as welcome credentials) are dispatched asynchronously:

`/etc/supervisor/conf.d/multicred-worker.conf`:
```ini
[program:multicred-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/multicred/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/multicred/storage/logs/worker.log
stopwaitsecs=3600
```

### 5.2 Cron Scheduled Tasks
Add the Laravel scheduler to `crontab -u www-data -e`:
```bash
* * * * * cd /var/www/multicred && php artisan schedule:run >> /dev/null 2>&1
```

---

## 6. Health Checks & Verification Procedures

After deployment, verify system health using these checks:
1. **HTTP Status**: Verify `curl -I https://multicred.pristineagro.org/login` returns HTTP `200`.
2. **Database Connectivity**: Verify with `php artisan tinker --execute 'echo DB::connection()->getPdo() ? "OK\n" : "FAIL\n";'`.
3. **Queue Health**: Inspect `jobs` and `failed_jobs` tables:
   ```bash
   php artisan queue:monitor default
   ```
4. **Storage Permissions**: Confirm avatar uploads can be viewed through `/storage/avatars/...`.
5. **SMTP Delivery**: Run `php artisan mail:test admin@pristineagro.org` to confirm live email dispatch.

---

## 7. Rollback & Recovery Procedures

If a critical issue occurs after a deployment:

1. **Revert Application Files**: Point symlink or git checkout to the previous stable release.
2. **Clear Caches Immediately**:
   ```bash
   php artisan optimize:clear
   ```
3. **Database Rollback (Caution)**:
   - For non-destructive rollback of the latest migration batch:
     ```bash
     php artisan migrate:rollback --step=1
     ```
   - *Never execute broad rollbacks in production without a verified database backup.*
4. **Database Restoration**:
   - Restore database from the pre-deployment snapshot:
     ```bash
     mysql -u multicred_prod_user -p multicred_production < /backup/multicred_pre_deploy.sql
     ```
