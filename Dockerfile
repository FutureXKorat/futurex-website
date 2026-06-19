FROM dunglas/frankenphp

# Install the mysqli PHP extension
RUN docker-php-ext-install mysqli

# Copy Caddyfile
COPY Caddyfile /etc/caddy/Caddyfile

# Copy application code into the web root
COPY . /app

EXPOSE 80

ENV SERVER_NAME=":80"