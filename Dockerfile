# Use official PHP image
FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    zip \
    nodejs \
    npm \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PM2 globally
RUN npm install -g pm2

# Set working directory
WORKDIR /app

# Copy project files
COPY . .

# Create logs directory
RUN mkdir -p logs

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist

# Install Node dependencies
RUN npm install

# Expose dev server port
EXPOSE 8000

# Default command
CMD ["php", "stack", "start"]
