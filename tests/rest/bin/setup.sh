#!/bin/bash

set -euxo pipefail

if [ -z "$MYSQL_DAEMON" ]; then
    MYSQL_DAEMON=mysqld
fi

if [ -z "$FPM_DAEMON" ]; then
    FPM_DAEMON='rh-php56-php-fpm'
fi

if [ -z "$PHP_CLI" ]; then
    PHP_CLI='/opt/rh/rh-php56/root/usr/bin/php'
fi

setup_tuleap() {
    echo "Setup Tuleap"
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
	-e 's#\$sys_rest_api_over_http =.*#\$sys_rest_api_over_http = 1;#' \
	-e 's#\$sys_logger_level =.*#\$sys_logger_level = "debug";#' \
	-e 's#/home/users##' \
	-e 's#/home/groups##' \
	> /etc/tuleap/conf/local.inc

	cp /usr/share/tuleap/src/utils/svn/Tuleap.pm /usr/share/perl5/vendor_perl/Apache/Tuleap.pm
	cp /usr/share/tuleap/src/utils/fileforge.pl /usr/lib/tuleap/bin/fileforge
}

setup_database() {
    MYSQL_HOST=localhost
    MYSQL_USER=tuleapadm
    MYSQL_PASSWORD=welcome0
    MYSQL_DBNAME=tuleap
    MYSQL="mysql -h$MYSQL_HOST -u$MYSQL_USER -p$MYSQL_PASSWORD"

    echo "Setup database $MYSQL_DAEMON"
    if [ "$MYSQL_DAEMON" = "rh-mysql56-mysqld" ]; then
        mkdir -p /tmp/mysql
        chown mysql:mysql /tmp/mysql
        cp /usr/share/tuleap/tests/rest/etc/mysql-server.cnf /etc/opt/rh/rh-mysql56/my.cnf.d/mysql-server.cnf
    fi

    service $MYSQL_DAEMON start
    mysql -e "GRANT ALL PRIVILEGES on *.* to '$MYSQL_USER'@'$MYSQL_HOST' identified by '$MYSQL_PASSWORD'"
    $MYSQL -e "DROP DATABASE IF EXISTS $MYSQL_DBNAME"
    $MYSQL -e "CREATE DATABASE $MYSQL_DBNAME CHARACTER SET utf8"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/database_structure.sql"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/database_initvalues.sql"
    $MYSQL $MYSQL_DBNAME -e "LOAD DATA LOCAL INFILE '/usr/share/tuleap/tests/rest/_fixtures/phpwiki/rest-test-wiki-group-list' INTO TABLE wiki_group_list CHARACTER SET ascii"
    $MYSQL $MYSQL_DBNAME -e "LOAD DATA LOCAL INFILE '/usr/share/tuleap/tests/rest/_fixtures/phpwiki/rest-test-wiki-page' INTO TABLE wiki_page CHARACTER SET ascii"
    $MYSQL $MYSQL_DBNAME -e "LOAD DATA LOCAL INFILE '/usr/share/tuleap/tests/rest/_fixtures/phpwiki/rest-test-wiki-nonempty' INTO TABLE wiki_nonempty CHARACTER SET ascii"
    $MYSQL $MYSQL_DBNAME -e "LOAD DATA LOCAL INFILE '/usr/share/tuleap/tests/rest/_fixtures/phpwiki/rest-test-wiki-version' INTO TABLE wiki_version CHARACTER SET ascii"
    $MYSQL $MYSQL_DBNAME -e "LOAD DATA LOCAL INFILE '/usr/share/tuleap/tests/rest/_fixtures/phpwiki/rest-test-wiki-recent' INTO TABLE wiki_recent CHARACTER SET ascii"

    mysql -e "GRANT SELECT ON $MYSQL_DBNAME.user to dbauthuser@'localhost' identified by '$MYSQL_PASSWORD';"
    mysql -e "GRANT SELECT ON $MYSQL_DBNAME.groups to dbauthuser@'localhost';"
    mysql -e "GRANT SELECT ON $MYSQL_DBNAME.user_group to dbauthuser@'localhost';"
    mysql -e "GRANT SELECT,UPDATE ON $MYSQL_DBNAME.svn_token to dbauthuser@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"

    echo "Execute additional setup scripts"
    for setup_script in $(find /usr/share/tuleap/plugins/*/tests/rest/setup_db.sh -maxdepth 1 -type f)
    do
        if [ -x "$setup_script" ]; then
            $setup_script "$MYSQL" "$MYSQL_DBNAME"
        fi
    done
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
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php tracker" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php cardwall" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php agiledashboard" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php frs" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php svn" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php git" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php crosstracker" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php create_test_env" -l codendiadm

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

    echo "Load initial data"
    "$PHP_CLI" -d include_path=/usr/share/tuleap/src/www/include:/usr/share/tuleap/src /usr/share/tuleap/tests/rest/bin/init_data.php

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
    "$PHP_CLI" -d include_path=/usr/share/tuleap/src/www/include:/usr/share/tuleap/src /usr/share/tuleap/tests/rest/bin/init_data_plugins.php
}

setup_tuleap
if [ "$FPM_DAEMON" == 'php72-php-fpm' ]; then
    echo "Deploy PHP FPM 7.2"
    "$PHP_CLI" /usr/share/tuleap/tools/utils/php72/run.php --modules=nginx,fpm
else
    echo "Deploy PHP FPM 5.6"
    "$PHP_CLI" /usr/share/tuleap/tools/utils/php56/run.php --modules=nginx,fpm
fi
service "$FPM_DAEMON" start
service nginx start
setup_database
seed_data
service "$FPM_DAEMON" restart
service nginx reload
