FROM alpine:latest
LABEL org.opencontainers.image.version="1.4.1"

# Install all required packages
RUN apk add --no-cache bash curl busybox-suid nginx php83-fpm php83-simplexml php83-dom php83-session php83-json php83-curl php83-mbstring php83-xml php83-fileinfo php83-cli supervisor ffmpeg imagemagick
RUN apk add --no-cache dcron       # Alpine cron
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
COPY php/www.conf /etc/php83/php-fpm.d/www.conf
COPY php/uploads.ini /etc/php83/conf.d/uploads.ini

# permissions
RUN chown -R 82:82 /IPTV
RUN chown -R 82:82 /IPTV/includes
RUN chown -R 82:82 /IPTV/supervisor
RUN chmod -R 660 /IPTV/*.*
RUN chmod -R 777 /IPTV/includes/
RUN chmod -R 777 /IPTV/
RUN chmod -R 777 /IPTV/supervisor
RUN chown -R 82:82 /LIVE
RUN chmod -R 777 /LIVE
RUN chmod -R 777 /var/log


# Expose HTTP
EXPOSE 9000
EXPOSE 8080

# Start cron + supervisord
CMD ["sh", "-c", "crond && supervisord -c /etc/supervisord.conf"]

# Add crontab entry for restart-scheduler.php
RUN echo "* * * * * php /IPTV/restart-scheduler.php >> /var/log/restart-scheduler.log 2>&1" >> /etc/crontabs/root
RUN echo "* * * * * php /IPTV/includes/health-check.php >> /var/log/restart-scheduler.log 2>&1" >> /etc/crontabs/root



