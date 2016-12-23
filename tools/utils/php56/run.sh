#!/usr/bin/env bash

set -ex

## Configure Nginx
mkdir /etc/opt/rh/rh-nginx18/nginx/conf.d/tuleap-plugins
cp /usr/share/tuleap/src/etc/nginx18/tuleap.conf /etc/opt/rh/rh-nginx18/nginx/conf.d/tuleap.conf

for plugin in `/bin/ls /usr/share/tuleap/plugins`; do
    if [ -d /usr/share/tuleap/plugins/$plugin/www ]; then
        cat /usr/share/tuleap/src/etc/nginx18/plugin.conf.tmpl | \
            sed -e "s/%name%/$plugin/g" > /etc/opt/rh/rh-nginx18/nginx/conf.d/tuleap-plugins/$plugin.conf;
    fi
done


## Configure FPM
sed -i \
    -e 's/^php_value\[session\.save_path\].*//' \
    -e 's/^php_value\[soap\.wsdl_cache_dir\].*//' \
    /etc/opt/rh/rh-php56/php-fpm.d/www.conf
cat /usr/share/tuleap/src/etc/fpm.conf.dist >> /etc/opt/rh/rh-php56/php-fpm.d/www.conf

install -d -o codendiadm -g codendiadm -m 0700 /var/tmp/tuleap_cache/php/session
install -d -o codendiadm -g codendiadm -m 0700 /var/tmp/tuleap_cache/php/wsdlcache
