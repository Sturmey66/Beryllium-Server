FROM alpine:latest

# Install all required packages
RUN apk add --no-cache bash curl nginx php-fpm php83-simplexml php-session php-json php-curl php-mbstring php-xml php-fileinfo supervisor ffmpeg
RUN adduser -S -G www-data www-data
RUN rm /etc/nginx/http.d/default.conf

# Set working directory
WORKDIR /IPTV

# Copy IPTV files and configs
COPY IPTV/ /IPTV/
COPY nginx/nginx.conf /etc/nginx/nginx.conf
COPY nginx/default.conf /etc/nginx/http.d/default.conf
COPY nginx/custom-php.ini /usr/local/etc/php/conf.d/custom-php.ini
COPY supervisor.d/ /etc/supervisor/conf.d/
COPY supervisor.d/supervisord.conf /etc/supervisord.conf
COPY php/crontab /etc/crontabs/root
COPY php/www.conf /etc/php83/php-fpm.d/www.conf

RUN chown 82:82 /IPTV/*.*
RUN chmod 660 /IPTV/*.*
RUN chown 82:82 /IPTV/live
RUN chmod 777 /IPTV/live


# Expose HTTP
EXPOSE 80

# Start cron + supervisord
CMD ["sh", "-c", "crond && supervisord -c /etc/supervisord.conf"]
