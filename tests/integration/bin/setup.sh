#!/bin/bash

set -euxo pipefail

if [ -z "$PHP_CLI" ]; then
    echo 'PHP_CLI environment variable must be specified' 1>&2
    exit 1
fi

setup_tuleap() {
    echo "Setup Tuleap"

	install -m 00755 -o codendiadm -g codendiadm /usr/share/tuleap/src/utils/tuleap /usr/bin/tuleap
	ln -s /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php /usr/bin/tuleap-cfg

	install -m 00755 -o codendiadm -g codendiadm -d /var/lib/tuleap/tracker
}

setup_database() {
    MYSQL_USER=tuleapadm
    MYSQL_PASSWORD=welcome0
    MYSQL_DBNAME=tuleap
    MYSQL_CLI="/opt/rh/rh-mysql57/root/usr/bin/mysql"
    MYSQL="$MYSQL_CLI -h$DB_HOST -u$MYSQL_USER -p$MYSQL_PASSWORD"

    echo "Use remote db $DB_HOST"

    /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:mysql-init \
        --host="$DB_HOST" \
        --admin-user=root \
        --admin-password=welcome0 \
        --db-name="$MYSQL_DBNAME" \
        --app-user="$MYSQL_USER" \
        --app-password="$MYSQL_PASSWORD" \
        --tuleap-fqdn="localhost" \
        --site-admin-password="welcome0" \
        --nss-password="welcome0"

    TLP_SYSTEMCTL=docker-centos7 /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:tuleap --force --tuleap-fqdn="localhost"
    echo '$sys_logger_level = "debug";' >> /etc/tuleap/conf/local.inc

    # Allow all privileges on DB starting with 'testdb_' so we can create and drop database during the tests
    $MYSQL_CLI -h"$DB_HOST" -uroot -pwelcome0 -e 'GRANT ALL PRIVILEGES ON `testdb_%` . * TO "'$MYSQL_USER'"@"%";'

    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/trackerv3structure.sql"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/trackerv3values.sql"
    # Need the raw import (instead of std activate of plugin) because we need to load
    # example.sql for Tv3->Tv5 migration tests
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/plugins/tracker_date_reminder/db/install.sql"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/plugins/tracker_date_reminder/db/examples.sql"
}

seed_data() {
    sudo -u codendiadm /usr/bin/tuleap plugin:install \
        tracker \
        agiledashboard \
        statistics \
        git \
        pullrequest \
        oauth2_server \
        program_management
}

setup_tuleap
setup_database
seed_data
