FROM dunglas/frankenphp

RUN docker-php-ext-install mysqli

COPY Caddyfile /etc/caddy/Caddyfile

COPY . /app

EXPOSE 80

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
