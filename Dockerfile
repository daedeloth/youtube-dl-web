FROM daedeloth/php-apache-youtubedl-ffmpeg:latest

COPY www /var/www
WORKDIR /var/www

# Install the standalone yt-dlp binary (bundles its own Python); the base
# image only has Python 3.9, which modern yt-dlp releases no longer support.
RUN curl -fsSL https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp_linux -o /usr/local/bin/yt-dlp \
    && chmod +x /usr/local/bin/yt-dlp \
    && /usr/local/bin/yt-dlp --version

# yt-dlp needs a JavaScript runtime (deno) for YouTube extraction.
RUN apt-get update && apt-get install -y --no-install-recommends unzip \
    && curl -fsSL https://github.com/denoland/deno/releases/latest/download/deno-x86_64-unknown-linux-gnu.zip -o /tmp/deno.zip \
    && unzip -o /tmp/deno.zip -d /usr/local/bin \
    && rm /tmp/deno.zip \
    && rm -rf /var/lib/apt/lists/* \
    && deno --version
RUN composer install --no-dev
