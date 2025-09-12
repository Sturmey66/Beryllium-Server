FROM alpine:latest

# Install all required packages
RUN apk add --no-cache bash curl nginx php-fpm php83-simplexml php-session php-json php-curl php-mbstring php-xml php-fileinfo php-cli supervisor ffmpeg imagemagick
RUN adduser -S -G www-data www-data
RUN rm /etc/nginx/http.d/default.conf

# Set working directory
WORKDIR /IPTV

# Copy IPTV files and configs
COPY IPTV/ /IPTV/
COPY LIVE/ /LIVE/
COPY nginx/nginx.conf /etc/nginx/nginx.conf
COPY nginx/default.conf /etc/nginx/http.d/default.conf
COPY nginx/custom-php.ini /usr/local/etc/php/conf.d/custom-php.ini
COPY supervisor.d/ /etc/supervisor/conf.d/
COPY supervisor.d/supervisord.conf /etc/supervisord.conf
COPY php/crontab /etc/crontabs/root
COPY php/www.conf /etc/php83/php-fpm.d/www.conf

RUN chown -R 82:82 /IPTV
RUN chown -R 82:82 /IPTV/includes
RUN chown -R 82:82 /IPTV/supervisor
RUN chmod -R 660 /IPTV/*.*
RUN chmod -R 777 /IPTV/includes/
RUN chmod -R 777 /IPTV/
RUN chmod -R 777 /IPTV/supervisor
RUN chown -R 82:82 /LIVE
RUN chmod -R 777 /LIVE

# Expose HTTP
EXPOSE 9000
EXPOSE 8080

# Start cron + supervisord
CMD ["sh", "-c", "crond && supervisord -c /etc/supervisord.conf"]
