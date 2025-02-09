#!/bin/sh
echo "Preparing database for upgrades..."
php "$APP_BASE_DIR/artisan" upgrade:install

echo "Processing links from existing vaults..."
php "$APP_BASE_DIR/artisan" upgrade:links
