FROM daedeloth/php-apache-youtubedl-ffmpeg:latest

COPY www /var/www/html
WORKDIR /var/www/html

RUN composer install --no-dev
