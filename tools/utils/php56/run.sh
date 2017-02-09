#!/usr/bin/env bash

set -ex

## Configure Nginx
if [ ! -f /etc/opt/rh/rh-nginx18/nginx/conf.d/tuleap.conf ]; then
    sed -e "s/%sys_default_domain%/tuleap-web.tuleap-aio-dev.docker/g" /usr/share/tuleap/src/etc/nginx18/tuleap.conf.dist > /etc/opt/rh/rh-nginx18/nginx/conf.d/tuleap.conf
fi
/usr/share/tuleap/src/etc/nginx18/redeploy.sh

## Configure FPM
sed -i \
    -e 's/^php_value\[session\.save_path\].*//' \
    -e 's/^php_value\[soap\.wsdl_cache_dir\].*//' \
    -e 's/^;\(pm\.max_requests.*\)/\1/' \
    /etc/opt/rh/rh-php56/php-fpm.d/www.conf
cat /usr/share/tuleap/src/etc/fpm.conf.dist >> /etc/opt/rh/rh-php56/php-fpm.d/www.conf

install -d -o codendiadm -g codendiadm -m 0700 /var/tmp/tuleap_cache/php/session
install -d -o codendiadm -g codendiadm -m 0700 /var/tmp/tuleap_cache/php/wsdlcache
