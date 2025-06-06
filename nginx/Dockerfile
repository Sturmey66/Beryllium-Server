# ./nginx/Dockerfile
FROM nginx:alpine

COPY supervisor.d/ /etc/supervisor/conf.d/
COPY nginx/custom-php.ini /usr/local/etc/php/conf.d/custom-php.ini

COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf

RUN apk add --no-cache bash git curl zip vim unzip ffmpeg



