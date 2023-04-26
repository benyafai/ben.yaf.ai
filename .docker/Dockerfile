FROM php:apache

COPY . /src
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf

RUN rm -rf /var/www/html && mv /src /var/www/html &&\
    chown -R www-data:www-data /var/www/html/ &&\
    chmod -R 664 /var/www/html/ &&\ 
    find /var/www/html/ -type d -exec chmod 755 {} \;
    
RUN a2enmod rewrite headers\
    && echo "ServerTokens Prod\n" >> /etc/apache2/apache2.conf \
    && echo "ServerSignature Off\n" >> /etc/apache2/apache2.conf \
    && echo "ServerName ben.yaf.ai\n" >> /etc/apache2/apache2.conf \
    && echo "php_admin_flag display_errors off\n" >> /etc/apache2/apache2.conf \
    && echo "php_admin_flag expose_php off\n" >> /etc/apache2/apache2.conf
RUN service apache2 restart