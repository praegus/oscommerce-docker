#!/bin/bash

if [ ! -f app/etc/local.xml ]; then

    echo "CHECKING DB CONNECTION"
    RET=1
    while [ $RET -ne 0 ]; do
        mysql -h $OSCOMMERCE_DB_HOST -u $OSCOMMERCE_DB_USER -p$OSCOMMERCE_DB_PASSWORD -e "status" > /dev/null 2>&1
        RET=$?
        if [ $RET -ne 0 ]; then
            echo "Waiting for confirmation of MySQL service startup";
            sleep 5
        fi
    done
fi

echo "CONNECTED";

mysql -h $OSCOMMERCE_DB_HOST -u $OSCOMMERCE_DB_USER -p$OSCOMMERCE_DB_PASSWORD $OSCOMMERCE_DB_NAME < /var/www/html/install/oscommerce.sql
mysql -h $OSCOMMERCE_DB_HOST -u $OSCOMMERCE_DB_USER -p$OSCOMMERCE_DB_PASSWORD $OSCOMMERCE_DB_NAME < /var/www/html/admin/includes/admin_user.sql
mysql -h $OSCOMMERCE_DB_HOST -u $OSCOMMERCE_DB_USER -p$OSCOMMERCE_DB_PASSWORD $OSCOMMERCE_DB_NAME < /var/www/html/admin/includes/languages.sql

exec "$@"
