FROM php:8.2-apache

# MySQL driver for PDO
RUN docker-php-ext-install pdo pdo_mysql

COPY . /var/www/html/

# Render injects the port to listen on via the $PORT env var at container
# START time (not build time), so we rewrite Apache's config in an entrypoint
# script that runs every time the container boots.
RUN printf '#!/bin/sh\nset -e\nPORT="${PORT:-80}"\nsed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf\nsed -ri "s/:80/:${PORT}/" /etc/apache2/sites-enabled/000-default.conf\nexec apache2-foreground\n' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

EXPOSE 80
CMD ["/usr/local/bin/start.sh"]
