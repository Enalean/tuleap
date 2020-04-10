#!/bin/bash

set -euxo pipefail

if [ -z "$PHP_CLI" ]; then
    echo 'PHP_CLI environment variable must be specified' 1>&2
    exit 1
fi

setup_tuleap() {
    echo "Setup Tuleap"

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

    echo "Use remote db $DB_HOST"

    /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:mysql-init \
        --host="$DB_HOST" \
        --admin-user=root \
        --admin-password=welcome0 \
        --db-name="$MYSQL_DBNAME" \
        --app-user="$MYSQL_USER@%" \
        --app-password="$MYSQL_PASSWORD"

    /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:mysql \
        --host="$DB_HOST" \
        --user="$MYSQL_USER" \
        --dbname="$MYSQL_DBNAME" \
        --password="$MYSQL_PASSWORD" \
        welcome0 \
        localhost

    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/trackerv3structure.sql"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/trackerv3values.sql"
    # Need the raw import (instead of std activate of plugin) because we need to load
    # example.sql for Tv3->Tv5 migration tests
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/plugins/tracker_date_reminder/db/install.sql"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/plugins/tracker_date_reminder/db/examples.sql"
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
