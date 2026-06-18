FROM dunglas/frankenphp

# Install the mysqli PHP extension
RUN docker-php-ext-install mysqli

# Copy application code into the web root
COPY . /app

EXPOSE 443
