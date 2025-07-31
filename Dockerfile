# Usa una imagen oficial de PHP con Apache (versión 8.2 es una buena opción)
FROM php:8.2-apache

# Instala las librerías necesarias para PostgreSQL y la extensión PDO_PGSQL
# 'libpq-dev' es necesario para compilar pdo_pgsql
RUN apt-get update && \
    apt-get install -y libpq-dev && \
    docker-php-ext-install pdo pdo_pgsql && \
    rm -rf /var/lib/apt/lists/*

# Copia todos los archivos de tu aplicación (la carpeta donde está tu Dockerfile)
# al directorio raíz de Apache dentro del contenedor.
COPY . /var/www/html/

# Establece los permisos correctos para los archivos de la aplicación
# Esto es importante para que Apache pueda leer y servir los archivos.
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Opcional: Si usas archivos .htaccess (por ejemplo, para URLs amigables),
# descomenta la siguiente línea para habilitar el módulo de reescritura de Apache.
# RUN a2enmod rewrite

# Expone el puerto 80, que es el puerto predeterminado de Apache.
EXPOSE 80

# El comando CMD ya viene configurado en la imagen base php:apache para iniciar Apache,
# así que no necesitas especificar un "Start Command" en Render si lo dejas así.
