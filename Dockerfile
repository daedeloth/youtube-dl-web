FROM daedeloth/php-apache-youtubedl-ffmpeg:latest

COPY www /var/www
WORKDIR /var/www

RUN composer install --no-dev
