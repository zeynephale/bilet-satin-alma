FROM php:8.2-apache

# Set labels for metadata
LABEL maintainer="ticketproject" \
      description="Secure ticket purchasing system with PHP 8.2 and SQLite"

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3 \
    && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

# Enable Apache modules
RUN a2enmod rewrite headers

# Create non-root user for application (NOT www-data)
# Using UID/GID 1001 to differentiate from www-data (33)
RUN groupadd -g 1001 appuser && \
    useradd -r -u 1001 -g appuser -m -s /bin/bash appuser

# Set working directory
WORKDIR /var/www/html

# Copy application files (as root first for proper setup)
COPY . /var/www/html

# Create data directory for SQLite database
# Only appuser has write access to this directory
RUN mkdir -p /var/www/data && \
    chown -R appuser:appuser /var/www/data && \
    chmod 750 /var/www/data

# Set proper permissions for application files
# Application code: read-only for appuser
RUN chown -R root:root /var/www/html && \
    chmod -R 755 /var/www/html && \
    # Public directory needs to be readable by Apache
    chmod -R 755 /var/www/html/public

# Update Apache configuration to point to public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configure Apache to run as appuser
ENV APACHE_RUN_USER=appuser
ENV APACHE_RUN_GROUP=appuser

# Update Apache to listen on port 80 (will be mapped to 8080 in docker-compose)
# This allows standard port inside container
RUN echo "ServerTokens Prod" >> /etc/apache2/apache2.conf && \
    echo "ServerSignature Off" >> /etc/apache2/apache2.conf && \
    echo "TraceEnable Off" >> /etc/apache2/apache2.conf

# Security: Disable directory listing
RUN sed -i 's/Options Indexes FollowSymLinks/Options -Indexes +FollowSymLinks/' /etc/apache2/apache2.conf

# Healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Switch to non-root user for runtime
USER appuser

# Expose port 80 (mapped to 8080 by docker-compose)
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]

