FROM daedeloth/php-apache-youtubedl-ffmpeg:latest

COPY www /var/www
WORKDIR /var/www

RUN /usr/local/bin/yt-dlp -U
RUN composer install --no-dev
