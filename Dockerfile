# 1. Temel imaj olarak PHP 8.0 ve Apache
FROM php:8.0-apache

# 2. Gerekli sistem bağımlılıklarını ve en önemlisi SQLite için PHP eklentilerini kuruyoruz.
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite zip

# 3. Composer'ı (PHP paket yöneticisi) global olarak kuruyoruz.
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Apache'nin "pretty URLs" (örn: /login) için mod_rewrite modülünü etkinleştiriyoruz.
RUN a2enmod rewrite

# 5. Apache'nin varsayılan DocumentRoot'unu Laravel'in public dizinine yönlendiriyoruz.
# (Projenizde bir 'public' klasörü ve içinde 'index.php' olduğu için bu adım hala gerekli)
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# 6. Uygulama için çalışma dizinini ayarlıyoruz.
WORKDIR /var/www/html

# 7. Önce sadece bağımlılık dosyalarını kopyalayıp kuruyoruz.
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-dev --optimize-autoloader

# 8. Geri kalan tüm proje dosyalarını konteynere kopyalıyoruz.
COPY . .

# 9. SQLite veritabanı dosyasına Apache kullanıcısının (www-data) yazabilmesi için 
# 'database' klasörüne (veya veritabanı dosyanız neredeyse oraya) izin veriyoruz.
# Projenizde 'database' klasörü olduğunu varsayıyorum.
RUN mkdir -p /var/www/html/database && \
    chown -R www-data:www-data /var/www/html/database

# 10. Konteynerin dışarıya açacağı portu belirtiyoruz (Apache varsayılanı).
EXPOSE 80
