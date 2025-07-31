# Imagen base con PHP 8.2 y Apache
FROM php:8.2-apache

# 1. Instala dependencias CRÍTICAS
RUN apt-get update && \
    apt-get install -y \
    libpq-dev \       # Para PostgreSQL
    git \             # Para composer (opcional)
    unzip && \        # Para descomprimir paquetes
    docker-php-ext-install pdo pdo_pgsql && \
    a2enmod rewrite && \  # Para URLs amigables
    rm -rf /var/lib/apt/lists/*

# 2. Copia la aplicación (excluyendo lo innecesario)
COPY . /var/www/html/

# 3. Permisos SEGUROS (evita el error 403)
RUN chown -R www-data:www-data /var/www/html && \
    find /var/www/html -type d -exec chmod 755 {} \; && \
    find /var/www/html -type f -exec chmod 644 {} \;

# 4. Puerto y variables de entorno (las configurarás en Render/Neon)
EXPOSE 80
ENV DB_HOST="ep-dry-shadow-ac9moc1g-pooler.sa-east-1.aws.neon.tech"
ENV DB_NAME="neondb"
ENV DB_USER="neondb_owner"
ENV DB_PASS="npg_cgvUQXe3CHJ1"
ENV DB_SSLMODE="require"
