#!/usr/bin/env bash

set -ex

if [ -z "$MYSQL_DAEMON" ]; then
    MYSQL_DAEMON=mysqld
fi

if [ -z "$HTTPD_DAEMON" ]; then
    HTTPD_DAEMON=httpd
fi

setup_apache() {
    echo "Setup $HTTPD_DAEMON"

    sed -i -e "s/User apache/User codendiadm/" \
	    -e "s/Group apache/Group codendiadm/" /etc/httpd/conf/httpd.conf

    cp /usr/share/tuleap/tests/soap/etc/soap-tests.conf /etc/httpd/conf.d/soap-tests.conf

    service $HTTPD_DAEMON restart
}

setup_tuleap() {
    echo "Setup Tuleap"
    mkdir -p \
        /etc/tuleap/conf \
        /etc/tuleap/plugins \
        /home/users \
        /var/log/tuleap \
        /var/tmp/codendi_cache
    touch /var/log/tuleap/codendi_syslog
    chgrp codendiadm /var/log/tuleap/codendi_syslog
    chmod g+w /var/log/tuleap/codendi_syslog
    chown codendiadm:codendiadm /var/tmp/codendi_cache

    cat /usr/share/tuleap/src/etc/database.inc.dist | \
        sed \
	     -e "s/%sys_dbname%/tuleap/" \
	     -e "s/%sys_dbuser%/tuleapadm/" \
	     -e "s/%sys_dbpasswd%/welcome0/" > /etc/tuleap/conf/database.inc
    chgrp runner /etc/tuleap/conf/database.inc

    cat /usr/share/tuleap/src/etc/local.inc.dist | \
	sed \
	-e "s#/etc/codendi#/etc/tuleap#g" \
	-e "s#/usr/share/codendi#/usr/share/tuleap#g" \
	-e "s#/var/log/codendi#/var/log/tuleap#g" \
	-e "s#/var/lib/codendi/ftp/codendi#/var/lib/tuleap/ftp/tuleap#g" \
	-e "s#/var/lib/codendi#/var/lib/tuleap#g" \
	-e "s#/usr/lib/codendi#/usr/lib/tuleap#g" \
	-e "s#/var/tmp/codendi_cache#/var/tmp/tuleap_cache#g" \
	-e "s#%sys_default_domain%#localhost#g" \
	-e "s#%sys_fullname%#localhost#g" \
	-e "s#%sys_dbauth_passwd%#welcome0#g" \
	-e "s#%sys_org_name%#Tuleap#g" \
	-e "s#%sys_long_org_name%#Tuleap#g" \
	-e 's#\$sys_https_host =.*#\$sys_https_host = "";#' \
	> /etc/tuleap/conf/local.inc
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
    #$MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/plugins/docman/db/install.sql"

    mysql -e "FLUSH PRIVILEGES;"
}

load_project() {
    base_dir=$1

    /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/src/utils/import_project_xml.php \
        -u admin \
        -i $base_dir \
        -m $base_dir/user_map.csv
}

seed_data() {
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php docman" -l codendiadm

    load_project /usr/share/tuleap/tests/soap/_fixtures/01-project

    echo "Load initial data"
    php -d include_path=/usr/share/tuleap/src/www/include:/usr/share/tuleap/src /usr/share/tuleap/tests/lib/soap/init_data.php
}



setup_tuleap
setup_apache
setup_database
seed_data
