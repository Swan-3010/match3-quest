#!/bin/bash
# Match Quest — Manual Deploy Script for Beget
# Usage: ./deploy.sh [production|staging]

ENV=${1:-production}
REMOTE_HOST="your_server.beget.com"
REMOTE_USER="your_user"
REMOTE_PATH="/home/your_user/lab.iig.ru"

echo "🚀 Deploying Match Quest to ${ENV}..."

# 1. Tests
echo "🧪 Running tests..."
php artisan test
if [ $? -ne 0 ]; then
    echo "❌ Tests failed! Aborting."
    exit 1
fi

# 2. Build assets
echo "🔨 Building assets..."
npm ci
npm run build

# 3. Install dependencies (no-dev)
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# 4. Prepare package
echo "📋 Preparing package..."
rm -rf .git tests node_modules .github

# 5. Deploy via rsync
echo "🚀 Uploading to server..."
rsync -avz --delete     --exclude=.env     --exclude=storage/logs/*     --exclude=storage/framework/cache/*     --exclude=storage/framework/sessions/*     ./ ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/

# 6. Post-deploy
echo "🔧 Running post-deploy commands..."
ssh ${REMOTE_USER}@${REMOTE_HOST} "cd ${REMOTE_PATH} && \
    php artisan migrate --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan cache:clear && \
    echo '✅ Deploy completed!'"

echo "🎉 Deploy finished!"
