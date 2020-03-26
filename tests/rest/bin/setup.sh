#!/bin/bash

set -euxo pipefail

if [ -z "${PHP_FPM:-}" ]; then
    echo 'PHP_FPM environment variable must be specified' 1>&2
    exit 1
fi

if [ -z "${PHP_CLI:-}" ]; then
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

    /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:mysql-init \
        --host="$DB_HOST" \
        --admin-user=root \
        --admin-password=welcome0 \
        --db-name="$MYSQL_DBNAME" \
        --app-user="$MYSQL_USER@%" \
        --app-password="$MYSQL_PASSWORD"

    $MYSQLROOT $MYSQL_DBNAME < /usr/share/tuleap/src/db/mysql/database_structure.sql
    $MYSQLROOT $MYSQL_DBNAME < /usr/share/tuleap/src/db/mysql/database_initvalues.sql

    $MYSQL $MYSQL_DBNAME -e "LOAD DATA LOCAL INFILE '/usr/share/tuleap/tests/rest/_fixtures/phpwiki/rest-test-wiki-group-list' INTO TABLE wiki_group_list CHARACTER SET ascii"
    $MYSQL $MYSQL_DBNAME -e "LOAD DATA LOCAL INFILE '/usr/share/tuleap/tests/rest/_fixtures/phpwiki/rest-test-wiki-page' INTO TABLE wiki_page CHARACTER SET ascii"
    $MYSQL $MYSQL_DBNAME -e "LOAD DATA LOCAL INFILE '/usr/share/tuleap/tests/rest/_fixtures/phpwiki/rest-test-wiki-nonempty' INTO TABLE wiki_nonempty CHARACTER SET ascii"
    $MYSQL $MYSQL_DBNAME -e "LOAD DATA LOCAL INFILE '/usr/share/tuleap/tests/rest/_fixtures/phpwiki/rest-test-wiki-version' INTO TABLE wiki_version CHARACTER SET ascii"
    $MYSQL $MYSQL_DBNAME -e "LOAD DATA LOCAL INFILE '/usr/share/tuleap/tests/rest/_fixtures/phpwiki/rest-test-wiki-recent' INTO TABLE wiki_recent CHARACTER SET ascii"

    echo "Execute additional setup scripts"
    for setup_script in $(find /usr/share/tuleap/plugins/*/tests/rest/setup_db.sh -maxdepth 1 -type f)
    do
        if [ -x "$setup_script" ]; then
            $setup_script "$MYSQL" "$MYSQL_DBNAME"
        fi
    done
}

tuleap_db_config() {
    PHP="$PHP_CLI" /usr/share/tuleap/src/utils/tuleap --platform-access-control restricted
}

load_project() {
    base_dir=$1

    user_mapping="-m $base_dir/user_map.csv"
    if [ ! -f $base_dir/user_map.csv ]; then
        user_mapping="--automap=no-email,create:A"
    fi
    PHP="$PHP_CLI" /usr/share/tuleap/src/utils/tuleap import-project-xml \
        -u admin \
        -i $base_dir \
        $user_mapping
}

seed_data() {
    su -c "PHP='$PHP_CLI' DISPLAY_ERRORS=true /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php tracker" -l codendiadm
    su -c "PHP='$PHP_CLI' DISPLAY_ERRORS=true /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php cardwall" -l codendiadm
    su -c "PHP='$PHP_CLI' DISPLAY_ERRORS=true /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php agiledashboard" -l codendiadm
    su -c "PHP='$PHP_CLI' DISPLAY_ERRORS=true /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php frs" -l codendiadm
    su -c "PHP='$PHP_CLI' DISPLAY_ERRORS=true /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php svn" -l codendiadm
    su -c "PHP='$PHP_CLI' DISPLAY_ERRORS=true /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php git" -l codendiadm
    su -c "PHP='$PHP_CLI' DISPLAY_ERRORS=true /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php crosstracker" -l codendiadm
    su -c "PHP='$PHP_CLI' DISPLAY_ERRORS=true /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php create_test_env" -l codendiadm
    su -c "PHP='$PHP_CLI' DISPLAY_ERRORS=true /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php docman" -l codendiadm
    su -c "PHP='$PHP_CLI' DISPLAY_ERRORS=true /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php hudson" -l codendiadm
    su -c "PHP='$PHP_CLI' DISPLAY_ERRORS=true /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php hudson_git" -l codendiadm

    load_project /usr/share/tuleap/tests/rest/_fixtures/01-private-member
    load_project /usr/share/tuleap/tests/rest/_fixtures/02-private
    load_project /usr/share/tuleap/tests/rest/_fixtures/03-public
    load_project /usr/share/tuleap/tests/rest/_fixtures/04-public-member
    load_project /usr/share/tuleap/tests/rest/_fixtures/05-pbi
    load_project /usr/share/tuleap/tests/rest/_fixtures/06-dragndrop
    load_project /usr/share/tuleap/tests/rest/_fixtures/07-computedfield
    load_project /usr/share/tuleap/tests/rest/_fixtures/08-public-including-restricted
    load_project /usr/share/tuleap/tests/rest/_fixtures/09-burndown-cache-generation
    load_project /usr/share/tuleap/tests/rest/_fixtures/10-permissions-on-artifacts
    load_project /usr/share/tuleap/tests/rest/_fixtures/11-delegated-rest-project-managers
    load_project /usr/share/tuleap/tests/rest/_fixtures/12-suspended-project
    load_project /usr/share/tuleap/tests/rest/_fixtures/13-project-services
    load_project /usr/share/tuleap/tests/rest/_fixtures/14-public-sync-project-member
    load_project /usr/share/tuleap/tests/rest/_fixtures/15-future-releases

    echo "Load initial data"
    "$PHP_CLI" /usr/share/tuleap/tests/rest/bin/init_data.php

    seed_plugin_data
}

seed_plugin_data() {
    echo "Execute additional setup scripts"
    for setup_script in $(find /usr/share/tuleap/plugins/*/tests/rest/setup.sh -maxdepth 1 -type f)
    do
        if [ -x "$setup_script" ]; then
            $setup_script
        fi
    done

    for fixture_dir in $(find /usr/share/tuleap/plugins/*/tests/rest/_fixtures/* -maxdepth 1 -type d)
    do
        if [ -f "$fixture_dir/project.xml" ] && [ -f "$fixture_dir/users.xml" ]  && [ -f "$fixture_dir/user_map.csv" ]; then
            load_project "$fixture_dir"
        fi
    done

    echo "Load plugins initial data"
    "$PHP_CLI" /usr/share/tuleap/tests/rest/bin/init_data_plugins.php
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
setup_database
tuleap_db_config
seed_data
$PHP_FPM --daemonize
nginx
