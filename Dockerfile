FROM dunglas/frankenphp

# Install the mysqli PHP extension
RUN docker-php-ext-install mysqli

# Copy application code into the web root
COPY . /app

# Configure FrankenPHP to listen on HTTP port 8080 for Railway's proxy
COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 8080
