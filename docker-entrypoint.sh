#!/bin/bash
set -e

if [ ! -f /var/www/html/include/th-config.php ]; then
    cp /var/www/html/include/th-config.sample.php /var/www/html/include/th-config.php
    chown www-data:www-data /var/www/html/include/th-config.php
fi

exec apache2-foreground
