#!/bin/bash
set -e

# Set PHP error reporting
PHP_ERROR_REPORTING=${PHP_ERROR_REPORTING:-"E_ALL"}
HOST_DOMAIN="host.docker.internal"
sed -ri 's/^display_errors\s*=\s*Off/display_errors = On/g' /etc/php/7.3/apache2/php.ini
sed -ri 's/^display_errors\s*=\s*Off/display_errors = On/g' /etc/php/7.3/cli/php.ini
sed -ri "s/^error_reporting\s*=.*$//g" /etc/php/7.3/apache2/php.ini
sed -ri "s/^error_reporting\s*=.*$//g" /etc/php/7.3/cli/php.ini
sed -ri "s/^upload_max_filesize\s*=.*$/upload_max_filesize = 10M/" /etc/php/7.3/apache2/php.ini
grep -qxF "error_reporting = $PHP_ERROR_REPORTING" /etc/php/7.3/apache2/php.ini || echo "error_reporting = $PHP_ERROR_REPORTING" >> /etc/php/7.3/apache2/php.ini
grep -qxF "error_reporting = $PHP_ERROR_REPORTING" /etc/php/7.3/cli/php.ini || echo "error_reporting = $PHP_ERROR_REPORTING" >> /etc/php/7.3/cli/php.ini

# Setup XDebug
grep -qxF "[XDebug]" /etc/php/7.3/apache2/php.ini || echo "[XDebug]" >> /etc/php/7.3/apache2/php.ini
grep -qxF "xdebug.remote_enable = 1" /etc/php/7.3/apache2/php.ini || echo "xdebug.remote_enable = 1" >> /etc/php/7.3/apache2/php.ini
grep -qxF "xdebug.remote_autostart = 1" /etc/php/7.3/apache2/php.ini || echo "xdebug.remote_autostart = 1" >> /etc/php/7.3/apache2/php.ini
grep -qxF "xdebug.remote_host = $HOST_DOMAIN" /etc/php/7.3/apache2/php.ini || echo "xdebug.remote_host = $HOST_DOMAIN" >> /etc/php/7.3/apache2/php.ini

# Configure sendmail, set container ID in hosts file, needed by sendmail
echo "127.0.0.1 localhost localhost.localdomain $(hostname)" >> /etc/hosts
yes Y | /usr/sbin/sendmailconfig

# Remove pre-existing PID files
rm -f /var/run/apache2/apache2.pid

# Enable Apache modules we need
echo "Enabling apache modules"
a2enmod rewrite
a2enmod headers

# Restart apache
echo "Restarting apache"
service apache2 restart

# To avoid closing the process and killing docker-compose, run this indefinitely
/bin/sh
