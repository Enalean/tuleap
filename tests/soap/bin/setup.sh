#!/usr/bin/env bash

set -ex

setup_tuleap() {
    echo "Setup Tuleap"

    install -m 00755 -o codendiadm -g codendiadm /usr/share/tuleap/src/utils/tuleap /usr/bin/tuleap

    mkdir -p \
        /etc/tuleap/conf \
        /etc/tuleap/plugins \
        /var/log/tuleap \
        /var/tmp/tuleap_cache
    touch /var/log/tuleap/codendi_syslog
    chgrp codendiadm /var/log/tuleap/codendi_syslog
    chmod g+w /var/log/tuleap/codendi_syslog
    chown -R codendiadm:codendiadm /var/tmp/tuleap_cache /etc/tuleap/plugins

    mkdir -p /etc/tuleap/plugins/docman/etc
	cat /usr/share/tuleap/plugins/docman/etc/docman.inc.dist | \
	sed \
	-e "s#codendi#tuleap#g"\
	> /etc/tuleap/plugins/docman/etc/docman.inc

    mkdir -p /usr/lib/tuleap/bin \
        /var/lib/tuleap/ftp/pub \
        /var/lib/tuleap/ftp/incoming \
        /var/lib/tuleap/ftp/tuleap \
        /var/lib/tuleap/docman \
        /home/groups

    chown -R codendiadm:codendiadm /var/lib/tuleap/ftp
    chown -R codendiadm:codendiadm /var/lib/tuleap/docman

    mkdir -p /etc/sudoers.d/
    install -m 00440 -o root -g root /usr/share/tuleap/src/utils/sudoers.d/tuleap_fileforge /etc/sudoers.d/tuleap_fileforge
}

setup_database() {
    MYSQL_USER=tuleapadm
    MYSQL_PASSWORD=welcome0
    MYSQL_DBNAME=tuleap

    MYSQLROOT="/opt/rh/rh-mysql57/root/usr/bin/mysql -h$DB_HOST -uroot -pwelcome0"

    /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:mysql-init \
        --host="${DB_HOST}" \
        --admin-user="root" \
        --admin-password="welcome0" \
        --db-name="${MYSQL_DBNAME}" \
        --app-user="${MYSQL_USER}" \
        --app-password="${MYSQL_PASSWORD}" \
        --tuleap-fqdn="localhost" \
        --site-admin-password="welcome0"

    TLP_SYSTEMCTL=docker-centos7 /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:tuleap --force --tuleap-fqdn="localhost"
    echo '$sys_logger_level = "debug";' >> /etc/tuleap/conf/local.inc
    echo '$sys_use_unsecure_ssl_certificate = true;' >> /etc/tuleap/conf/local.inc

    $MYSQLROOT $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/trackerv3structure.sql"
    $MYSQLROOT $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/trackerv3values.sql"

    /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:forgeupgrade
}

load_project() {
    base_dir=$1

    PHP="$PHP_CLI" /usr/share/tuleap/src/utils/tuleap import-project-xml \
        -u admin \
        -i $base_dir \
        -m $base_dir/user_map.csv
}

seed_data() {
    sudo -u codendiadm PHP="$PHP_CLI" /usr/share/tuleap/src/utils/tuleap plugin:install docman
    load_project /usr/share/tuleap/tests/soap/_fixtures/01-project

    # Import done after so that TV3 can be created ...
    sudo -u codendiadm PHP="$PHP_CLI" /usr/share/tuleap/src/utils/tuleap plugin:install tracker

    echo "Load initial data"
    PHP="$PHP_CLI" "$PHP_CLI" /usr/share/tuleap/tests/soap/bin/init_data.php
}

setup_tuleap
setup_database
case "$PHP_FPM" in
    '/opt/remi/php80/root/usr/sbin/php-fpm')
    echo "Deploy PHP FPM 80"
    /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php site-deploy --php-version=php80
    ;;
esac
"$PHP_FPM" --daemonize
nginx
seed_data
