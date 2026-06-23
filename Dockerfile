FROM dunglas/frankenphp

RUN docker-php-ext-install mysqli

COPY Caddyfile /etc/caddy/Caddyfile

COPY . /app

EXPOSE 80
