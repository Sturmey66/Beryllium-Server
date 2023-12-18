FROM nginx:alpine
ADD nginx/default.conf /etc/nginx/conf.d
ADD nginx/php.ini /usr/local/etc/php
RUN apt-get update && apt-get install -y {xmlstarlet}