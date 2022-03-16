#!/bin/bash

set -x

systemctl start systemd-user-sessions.service

while ! /opt/rh/rh-mysql57/root/bin/mysql -hdb -uroot -p$MYSQL_ROOT_PASSWORD -e "show databases" >/dev/null; do
    echo "Wait for the db";
    sleep 1
done

TULEAP_INSTALL_TIME="false"
if [ ! -f /data/etc/tuleap/conf/local.inc ]; then
    TULEAP_INSTALL_TIME="true"
    set -e

    # If tuleap directory is not in data, assume it's first boot and move
    # everything in the mounted dir
    /usr/share/tuleap/tools/docker/tuleap-aio-dev/boot-install.sh
fi

# Fix path
/usr/share/tuleap/tools/docker/tuleap-aio-dev/boot-fixpath.sh

# Align data ownership with images uids/gids
/usr/share/tuleap/tools/docker/tuleap-aio/fix-owners.sh

# Update DB location
[ -f /etc/libnss-mysql.cfg ] && sed -i "s/^host.*/host db/" /etc/libnss-mysql.cfg
sed -i "s/^\$sys_dbhost.*/\$sys_dbhost=\"db\";/" /etc/tuleap/conf/database.inc

# Update LDAP location
sed -i "s/^\$sys_ldap_server.*/\$sys_ldap_server = \"ldap:\/\/ldap\";/" /etc/tuleap/plugins/ldap/etc/ldap.inc
sed -i "s/^\$sys_ldap_write_server.*/\$sys_ldap_write_server = \"ldap:\/\/ldap\";/" /etc/tuleap/plugins/ldap/etc/ldap.inc
[ -n "$LDAP_MANAGER_PASSWORD" ] && sed -i "s/^\$sys_ldap_write_password.*/\$sys_ldap_write_password = \"$LDAP_MANAGER_PASSWORD\";/" /etc/tuleap/plugins/ldap/etc/ldap.inc

# Allow configuration update at boot time
/usr/share/tuleap/tools/docker/tuleap-aio-dev/boot-update-config.sh

# Update Postfix config
perl -pi -e "s%^#myhostname = host.domain.tld%myhostname = ${VIRTUAL_HOST//_}%" /etc/postfix/main.cf
perl -pi -e "s%^alias_maps = hash:/etc/aliases%alias_maps = hash:/etc/aliases,hash:/etc/aliases.codendi%" /etc/postfix/main.cf
perl -pi -e "s%^alias_database = hash:/etc/aliases%alias_database = hash:/etc/aliases,hash:/etc/aliases.codendi%" /etc/postfix/main.cf
perl -pi -e "s%^#recipient_delimiter = %recipient_delimiter = %" /etc/postfix/main.cf
perl -pi -e "s%^inet_protocols = .*%inet_protocols = ipv4%" /etc/postfix/main.cf

# Email whitelist
/usr/share/tuleap/tools/docker/tuleap-aio-dev/whitelist_emails.sh
echo "transport_maps = hash:/etc/postfix/transport" >> /etc/postfix/main.cf

# Update nscd config
perl -pi -e "s%enable-cache[\t ]+group[\t ]+yes%enable-cache group no%" /etc/nscd.conf

if [ "$TULEAP_INSTALL_TIME" == "false" ]; then
    # DB upgrade (after config as we might depends on it)
    /usr/share/tuleap/tools/docker/tuleap-aio-dev/boot-upgrade.sh
fi

# Activate backend/crontab
systemctl start tuleap

if [ -n "$RUN_COMMAND" ]; then
    $RUN_COMMAND
else
    tuleap-cfg site-deploy:apache
    tuleap-cfg site-deploy:fpm --development --php-version=$PHP_VERSION
    tuleap-cfg site-deploy:nginx --development

    while [ ! -f /etc/pki/ca-trust/source/anchors/tuleap-realtime-cert.pem ]; do
        echo "Waiting for Tuleap Realtime certificate…"
        sleep 1
    done

    echo "Tuleap Realtime certificate has been found. Adding to the CA bundle."
    update-ca-trust enable
    update-ca-trust extract

    replacement=`echo $REALTIME_KEY | sed "s|/|\\\\\/|g"`
    echo "\$nodejs_server_jwt_private_key = '$replacement';" >> /etc/tuleap/conf/local.inc
    echo "\$nodejs_server = 'tuleap-web.tuleap-aio-dev.docker:443';" >> /etc/tuleap/conf/local.inc
    echo "\$nodejs_server_int = 'realtime';" >> /etc/tuleap/conf/local.inc

    if [ -f /usr/share/tuleap/.metrics_secret.key ]; then
        mkdir -p /etc/tuleap/plugins/prometheus_metrics/etc/
        cp /usr/share/tuleap/.metrics_secret.key /etc/tuleap/plugins/prometheus_metrics/etc/metrics_secret.key
        chown codendiadm:codendiadm /etc/tuleap/plugins/prometheus_metrics/etc/metrics_secret.key
    fi
fi

systemctl restart nginx
systemctl restart tuleap-php-fpm
systemctl start tuleap-process-system-events-default.timer
systemctl start tuleap-launch-system-check.timer
systemctl start tuleap-process-system-events-git.timer
systemctl start httpd
