#/bin/bash
docker build --pull --no-cache -t daedeloth/youtube-dl-web .
docker push daedeloth/youtube-dl-web
