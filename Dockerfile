FROM dunglas/frankenphp

RUN docker-php-ext-install mysqli

COPY php-production.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY Caddyfile /etc/caddy/Caddyfile

COPY . /app

EXPOSE 80

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
