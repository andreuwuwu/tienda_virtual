# Imagen base con PHP 8.2 y Apache
FROM php:8.2-apache

# 1. Instala dependencias CRÍTICAS
# apt-get update para actualizar la lista de paquetes.
# apt-get install -y para instalar paquetes sin pedir confirmación.
#   libpq-dev: Necesario para la extensión PDO de PostgreSQL.
#   git: Para usar Git dentro del contenedor si fuera necesario (ej. para Composer).
#   unzip: Para descomprimir archivos (ej. si usas Composer para descargar dependencias).
# docker-php-ext-install: Habilita las extensiones PDO y pdo_pgsql en PHP.
# a2enmod rewrite: Habilita el módulo rewrite de Apache para URLs amigables (si tu .htaccess lo usa).
# rm -rf /var/lib/apt/lists/*: Limpia el caché de paquetes para reducir el tamaño de la imagen final.
RUN apt-get update && \
    apt-get install -y \
        libpq-dev \
        git \
        unzip && \
    docker-php-ext-install pdo pdo_pgsql && \
    a2enmod rewrite && \
    rm -rf /var/lib/apt/lists/*

# 2. Copia la aplicación
# Copia todo el contenido de tu directorio local (donde está el Dockerfile)
# al directorio web de Apache dentro del contenedor.
COPY . /var/www/html/

# 3. Configura permisos seguros
# chown: Cambia el propietario de los archivos a www-data (el usuario bajo el que corre Apache).
# find ... chmod: Establece permisos estándar: 755 para directorios y 644 para archivos,
# asegurando que Apache pueda leer y ejecutar lo necesario, pero sin dar permisos excesivos.
RUN chown -R www-data:www-data /var/www/html && \
    find /var/www/html -type d -exec chmod 755 {} \; && \
    find /var/www/html -type f -exec chmod 644 {} \;

# 4. Define el puerto que el contenedor expone
# Esto informa a Docker que el servicio dentro del contenedor escucha en el puerto 80.
# Render lo usará para enrutar el tráfico.
EXPOSE 80

# 5. Define variables de entorno (opcional para tu caso actual)
# Aunque tu 'conexion.php' tiene los datos harcodeados, es una buena práctica
# definir estas variables para futura externalización de la configuración.
# Render te permite sobrescribir estas en el dashboard.
ENV DB_HOST="ep-dry-shadow-ac9moc1g-pooler.sa-east-1.aws.neon.tech"
ENV DB_NAME="neondb"
ENV DB_USER="neondb_owner"
ENV DB_PASS="npg_cgvUQXe3CHJ1"
ENV DB_SSLMODE="require"

# Opcional: Configuración de Apache para asegurar que index.php o index.html son el archivo principal.
# Si tu index.html o index.php está en la raíz de /var/www/html, Apache por defecto lo reconocerá.
# Esto es más bien una medida de contingencia si Apache no carga el archivo principal esperado.
# RUN echo '<IfModule dir_module>\n    DirectoryIndex index.php index.html\n</IfModule>' > /etc/apache2/mods-enabled/dir.conf
