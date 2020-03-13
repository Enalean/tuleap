#!/bin/bash

set -euxo pipefail

if [ -z "$PHP_CLI" ]; then
    echo 'PHP_CLI environment variable must be specified' 1>&2
    exit 1
fi

setup_tuleap() {
    echo "Setup Tuleap"
    cat /usr/share/tuleap/src/etc/database.inc.dist | \
        sed \
        -e "s/localhost/$DB_HOST/" \
        -e "s/%sys_dbname%/tuleap/" \
        -e "s/%sys_dbuser%/tuleapadm/" \
        -e "s/%sys_dbpasswd%/welcome0/" > /etc/tuleap/conf/database.inc
    chgrp codendiadm /etc/tuleap/conf/database.inc

    cat /usr/share/tuleap/src/etc/local.inc.dist | \
	sed \
	-e "s#/var/lib/tuleap/ftp/codendi#/var/lib/tuleap/ftp/tuleap#g" \
	-e "s#%sys_default_domain%#localhost#g" \
	-e "s#%sys_fullname%#localhost#g" \
	-e "s#%sys_dbauth_passwd%#welcome0#g" \
	-e "s#%sys_org_name%#Tuleap#g" \
	-e "s#%sys_long_org_name%#Tuleap#g" \
	-e 's#\$sys_https_host =.*#\$sys_https_host = "localhost";#' \
	-e 's#\$sys_rest_api_over_http =.*#\$sys_rest_api_over_http = 1;#' \
	-e 's#\$sys_logger_level =.*#\$sys_logger_level = "debug";#' \
	-e 's#/home/users##' \
	-e 's#/home/groups##' \
	> /etc/tuleap/conf/local.inc

	install -m 00755 -o codendiadm -g codendiadm /usr/share/tuleap/src/utils/tuleap /usr/bin/tuleap
	ln -s /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php /usr/bin/tuleap-cfg

	install -m 00755 -o codendiadm -g codendiadm -d /var/lib/tuleap/tracker
}

setup_database() {
    MYSQL_USER=tuleapadm
    MYSQL_PASSWORD=welcome0
    MYSQL_DBNAME=tuleap
    MYSQL="mysql -h$DB_HOST -u$MYSQL_USER -p$MYSQL_PASSWORD"

    MYSQLROOT="mysql -h$DB_HOST -uroot -pwelcome0"
    echo "Use remote db $DB_HOST"
    while ! $MYSQLROOT -e "show databases" >/dev/null; do
        echo "Wait for the db";
        sleep 1
    done

    $MYSQLROOT -e "GRANT ALL PRIVILEGES on *.* to '$MYSQL_USER'@'%' identified by '$MYSQL_PASSWORD'"
    $MYSQL -e "DROP DATABASE IF EXISTS $MYSQL_DBNAME"
    $MYSQL -e "CREATE DATABASE $MYSQL_DBNAME CHARACTER SET utf8"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/database_structure.sql"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/database_initvalues.sql"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/trackerv3structure.sql"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/trackerv3values.sql"
    # Need the raw import (instead of std activate of plugin) because we need to load
    # example.sql for Tv3->Tv5 migration tests
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/plugins/tracker_date_reminder/db/install.sql"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/plugins/tracker_date_reminder/db/examples.sql"

    $MYSQLROOT -e "GRANT SELECT ON $MYSQL_DBNAME.user to dbauthuser@'localhost' identified by '$MYSQL_PASSWORD';"
    $MYSQLROOT -e "GRANT SELECT ON $MYSQL_DBNAME.groups to dbauthuser@'localhost';"
    $MYSQLROOT -e "GRANT SELECT ON $MYSQL_DBNAME.user_group to dbauthuser@'localhost';"
    $MYSQLROOT -e "GRANT SELECT,UPDATE ON $MYSQL_DBNAME.svn_token to dbauthuser@'localhost';"
    $MYSQLROOT -e "FLUSH PRIVILEGES;"
}

seed_data() {
    su -c "PHP='$PHP_CLI' DISPLAY_ERRORS=true /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php tracker" -l codendiadm
    su -c "PHP='$PHP_CLI' DISPLAY_ERRORS=true /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php statistics" -l codendiadm
    su -c "PHP='$PHP_CLI' DISPLAY_ERRORS=true /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php git" -l codendiadm
    su -c "PHP='$PHP_CLI' DISPLAY_ERRORS=true /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php pullrequest" -l codendiadm
    su -c "PHP='$PHP_CLI' DISPLAY_ERRORS=true /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php oauth2_server" -l codendiadm
}

setup_tuleap
setup_database
seed_data
