#!/usr/bin/env bash

set -ex

if [ -z "$MYSQL_DAEMON" ]; then
    MYSQL_DAEMON=mysqld
fi

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

    install -m 04755 /usr/share/tuleap/src/utils/fileforge.pl /usr/lib/tuleap/bin/fileforge
}

setup_database() {
    MYSQL_HOST=localhost
    MYSQL_USER=tuleapadm
    MYSQL_PASSWORD=welcome0
    MYSQL_DBNAME=tuleap
    MYSQL="mysql -h$MYSQL_HOST -u$MYSQL_USER -p$MYSQL_PASSWORD"

    echo "Setup database $MYSQL_DAEMON"
    service $MYSQL_DAEMON start
    mysql -e "GRANT ALL PRIVILEGES on *.* to '$MYSQL_USER'@'$MYSQL_HOST' identified by '$MYSQL_PASSWORD'"
    $MYSQL -e "DROP DATABASE IF EXISTS $MYSQL_DBNAME"
    $MYSQL -e "CREATE DATABASE $MYSQL_DBNAME CHARACTER SET utf8"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/database_structure.sql"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/database_initvalues.sql"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/trackerv3structure.sql"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/trackerv3values.sql"

    mysql -e "FLUSH PRIVILEGES;"
}

load_project() {
    base_dir=$1

    PHP=/opt/remi/php"$PHP_VERSION"/root/usr/bin/php /usr/share/tuleap/src/utils/tuleap import-project-xml \
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
    /opt/remi/php"$PHP_VERSION"/root/usr/bin/php /usr/share/tuleap/tests/soap/bin/init_data.php
}

setup_tuleap
/usr/share/tuleap/tools/utils/php"$PHP_VERSION"/run.php --modules=nginx,fpm
service php"$PHP_VERSION"-php-fpm start
service nginx start
setup_database
seed_data
