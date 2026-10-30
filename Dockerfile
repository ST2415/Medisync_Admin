FROM php:8.2-apache

# ติดตั้ง Extension สำหรับเชื่อมต่อ MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# เปิดใช้งาน mod_rewrite ของ Apache (สำหรับ Route ในภายหลัง)
RUN a2enmod rewrite