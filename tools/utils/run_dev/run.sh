#!/usr/bin/env bash

set -ex

if [ -z "$PHP_VERSION" ]; then
    (>&2 echo "PHP_VERSION env variable must be set")
    exit 1
fi

/opt/remi/php"$PHP_VERSION"/root/usr/bin/php /usr/share/tuleap/tools/utils/php"$PHP_VERSION"/run.php --development

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
