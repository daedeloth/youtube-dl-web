FROM daedeloth/php-apache-youtubedl-ffmpeg:latest

COPY www /var/www
WORKDIR /var/www

RUN /usr/loca/bin/yt-dlp -U
RUN composer install --no-dev
