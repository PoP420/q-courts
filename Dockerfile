FROM php:8.5-cli

# PostgreSQL driver (compiled from the bundled source — no network needed).
RUN apt-get update -qq \
    && apt-get install -y -qq --no-install-recommends libpq-dev git unzip \
    && docker-php-ext-install pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Bake the application (including the already-installed vendor/) into the image
# so the container is self-contained. `composer install` can't run here (no
# Packagist access), so we copy the host's vendor/ which was installed earlier.
COPY . .

EXPOSE 8000

CMD ["sh", "-c", "php artisan migrate --force && php artisan db:seed --class=CourtSeeder --force && php artisan serve --host=0.0.0.0 --port=8000"]
