#!/usr/bin/env bash

set -ex

if [ -z "$PHP_VERSION" ]; then
    (>&2 echo "PHP_VERSION env variable must be set")
    exit 1
fi

tuleap-cfg site-deploy:apache
tuleap-cfg site-deploy:fpm --development --php-version=$PHP_VERSION
tuleap-cfg site-deploy:nginx --development

# Workaround that at the time of switch to php 7.4 enalean/tuleap-aio-dev image will not contain the php 7.4 service
# (because it need the commit where all the php 7.4 changes are included).
# At a later point the following code can be removed.
if [ "$PHP_VERSION" = "php74" ]; then
    /bin/cp -f /usr/share/tuleap/src/utils/systemd/tuleap-php-fpm.service /lib/systemd/system/tuleap-php-fpm.service
    systemctl daemon-reload
    systemctl restart tuleap-php-fpm
fi

while [ ! -f /etc/pki/ca-trust/source/anchors/tuleap-realtime-cert.pem ]; do
    echo "Waiting for Tuleap Realtime certificate…"
    sleep 1
done

echo "Tuleap Realtime certificate has been found. Adding to the CA bundle."
update-ca-trust enable
update-ca-trust extract

replacement=`echo $REALTIME_KEY | sed "s|/|\\\\\/|g"`
sed -e "s/\$nodejs_server_jwt_private_key = '';/\$nodejs_server_jwt_private_key = '$replacement';/" \
    -e "s/\$nodejs_server = '';/\$nodejs_server = 'tuleap-web.tuleap-aio-dev.docker:443';/" \
    -e "s/\$nodejs_server_int = '';/\$nodejs_server_int = 'realtime';/" \
    -i /etc/tuleap/conf/local.inc

if [ -f /usr/share/tuleap/.metrics_secret.key ]; then
    mkdir -p /etc/tuleap/plugins/prometheus_metrics/etc/
    cp /usr/share/tuleap/.metrics_secret.key /etc/tuleap/plugins/prometheus_metrics/etc/metrics_secret.key
    chown codendiadm:codendiadm /etc/tuleap/plugins/prometheus_metrics/etc/metrics_secret.key
fi
