FROM php:8.5-apache

ENV LC_ALL=C.UTF-8 LANG=C.UTF-8 LANGUAGE=en_US:en

# Production ini: log errors instead of printing them into download responses.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# ffmpeg for clipping, python3 + ffmpeg-normalize for audio normalization,
# git/zip/unzip for composer.
RUN apt-get update && apt-get install -y --no-install-recommends \
        ffmpeg zip unzip git python3 python3-pip \
    && pip3 install --no-cache-dir --break-system-packages ffmpeg-normalize \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install the standalone yt-dlp binary (bundles its own Python).
RUN curl -fsSL https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp_linux -o /usr/local/bin/yt-dlp \
    && chmod a+rx /usr/local/bin/yt-dlp \
    && /usr/local/bin/yt-dlp --version

# yt-dlp needs a JavaScript runtime (deno) for YouTube extraction.
RUN curl -fsSL https://github.com/denoland/deno/releases/latest/download/deno-x86_64-unknown-linux-gnu.zip -o /tmp/deno.zip \
    && unzip -o /tmp/deno.zip -d /usr/local/bin \
    && rm /tmp/deno.zip \
    && deno --version

COPY www /var/www
WORKDIR /var/www

RUN composer install --no-dev
