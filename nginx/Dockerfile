FROM nginx:alpine
ADD nginx/default.conf /etc/nginx/conf.d
ADD nginx/php.ini /usr/local/etc/php
RUN apk add git curl zip vim unzip ffmpeg xmlstarlet