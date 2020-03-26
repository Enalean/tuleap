#!/usr/bin/env bash

set -ex

setup_tuleap() {
    echo "Setup Tuleap"
    mkdir -p \
        /etc/tuleap/conf \
        /etc/tuleap/plugins \
        /var/log/tuleap \
        /var/tmp/tuleap_cache
    touch /var/log/tuleap/codendi_syslog
    chgrp codendiadm /var/log/tuleap/codendi_syslog
    chmod g+w /var/log/tuleap/codendi_syslog
    chown -R codendiadm:codendiadm /var/tmp/tuleap_cache /etc/tuleap/plugins

    cat /usr/share/tuleap/src/etc/database.inc.dist | \
        sed \
         -e "s/localhost/$DB_HOST/" \
	     -e "s/%sys_dbname%/tuleap/" \
	     -e "s/%sys_dbuser%/tuleapadm/" \
	     -e "s/%sys_dbpasswd%/welcome0/" > /etc/tuleap/conf/database.inc
     chgrp runner /etc/tuleap/conf/database.inc

    cat /usr/share/tuleap/src/etc/local.inc.dist | \
	sed \
	-e "s#/var/lib/tuleap/ftp/codendi#/var/lib/tuleap/ftp/tuleap#g" \
	-e "s#%sys_default_domain%#localhost#g" \
	-e "s#%sys_fullname%#localhost#g" \
	-e "s#%sys_dbauth_passwd%#welcome0#g" \
	-e "s#%sys_org_name%#Tuleap#g" \
	-e "s#%sys_long_org_name%#Tuleap#g" \
	-e 's#\$sys_https_host =.*#\$sys_https_host = "localhost";#' \
	-e 's#\$sys_logger_level =.*#\$sys_logger_level = "debug";#' \
	-e 's#\$sys_use_unsecure_ssl_certificate =.*#\$sys_use_unsecure_ssl_certificate = true;#' \
	-e 's#/home/users##' \
	-e 's#/home/groups##' \
	> /etc/tuleap/conf/local.inc

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

    MYSQLROOT="mysql -h$DB_HOST -uroot -pwelcome0"

    /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:mysql-init \
        --host="${DB_HOST}" \
        --admin-user="root" \
        --admin-password="welcome0" \
        --db-name="${MYSQL_DBNAME}" \
        --app-user="${MYSQL_USER}@%" \
        --app-password="${MYSQL_PASSWORD}"

    /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:mysql \
        --host="$DB_HOST" \
        --user="$MYSQL_USER" \
        --dbname="$MYSQL_DBNAME" \
        --password="$MYSQL_PASSWORD" \
        welcome0 \
        localhost

    $MYSQLROOT $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/trackerv3structure.sql"
    $MYSQLROOT $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/trackerv3values.sql"
}

load_project() {
    base_dir=$1

    PHP="$PHP_CLI" /usr/share/tuleap/src/utils/tuleap import-project-xml \
        -u admin \
        -i $base_dir \
        -m $base_dir/user_map.csv
}

seed_data() {
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php docman" -l codendiadm
    load_project /usr/share/tuleap/tests/soap/_fixtures/01-project

    # Import done after so that TV3 can be created ...
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php tracker" -l codendiadm

    echo "Load initial data"
    "$PHP_CLI" /usr/share/tuleap/tests/soap/bin/init_data.php
}

setup_tuleap
case "$PHP_FPM" in
    '/opt/remi/php73/root/usr/sbin/php-fpm')
    echo "Deploy PHP FPM 7.3"
    "$PHP_CLI" /usr/share/tuleap/tools/utils/php73/run.php --modules=nginx,fpm
    ;;
    '/opt/remi/php74/root/usr/sbin/php-fpm')
    echo "Deploy PHP FPM 7.4"
    "$PHP_CLI" /usr/share/tuleap/tools/utils/php74/run.php --modules=nginx,fpm
    ;;
esac
"$PHP_FPM" --daemonize
nginx
setup_database
seed_data
