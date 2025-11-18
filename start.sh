#!/usr/bin/env bash
echo "Running composer"


composer install --no-dev --working-dir=/var/www/html


echo "Caching config..."
php artisan config:cache


echo "Caching routes..."
php artisan route:cache


echo "Publishing cloudinary provider..."
php artisan vendor:publish --provider="CloudinaryLabs\CloudinaryLaravel\CloudinaryServiceProvider" --tag="cloudinary-laravel-config"


echo "Running migrations..."
php artisan migrate --force


echo "Clearing and caching config..."
php artisan config:clear
php artisan config:cache


echo "Clearing and caching routes..."
php artisan route:clear
php artisan route:cache


echo "Clearing and caching views..."
php artisan view:clear
php artisan view:cache


echo "Optimizing application..."
php artisan optimize


echo "Starting queue worker in background..."
php artisan queue:work --verbose --tries=3 --timeout=90 --sleep=3 --max-jobs=1000 &


echo "Starting application..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
