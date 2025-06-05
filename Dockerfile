# Используем официальный образ PHP с Apache
FROM php:8.2-apache

# Устанавливаем расширения для PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql

# Копируем файлы сайта в контейнер
COPY ./public/ /var/www/html/

# Включаем mod_rewrite для Apache (если используете .htaccess)
RUN a2enmod rewrite

# Порт, который будет слушать Apache
EXPOSE 80

# Запускаем Apache в foreground-режиме
CMD ["apache2-foreground"]