FROM dunglas/frankenphp

# Install the mysqli PHP extension
RUN docker-php-ext-install mysqli

# Copy application code into the web root
COPY . /app

# FrankenPHP runs on port 80 by default
EXPOSE 80

ENV SERVER_NAME=":80"