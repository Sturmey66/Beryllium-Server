FROM php:fpm-alpine

RUN apk add --no-cache bash ffmpeg supervisor

COPY nginx/custom-php.ini /usr/local/etc/php/conf.d/custom-php.ini
COPY supervisor.d/ /etc/supervisor/conf.d/
COPY php/supervisord.conf /etc/supervisord.conf
COPY php/crontab /etc/crontabs/root

WORKDIR /IPTV

CMD ["sh", "-c", "crond && supervisord -c /etc/supervisord.conf"]
