#!/usr/bin/env bash

set -euxo pipefail

export DISPLAY_ERRORS=true

if [ -z "$PHP_CLI" ]; then
    echo 'PHP_CLI environment variable must be specified' 1>&2
    exit 1
fi

setup_tuleap() {
    echo "Setup Tuleap"

	install -m 00755 -o codendiadm -g codendiadm /usr/share/tuleap/src/utils/tuleap /usr/bin/tuleap
	ln -s /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php /usr/bin/tuleap-cfg

	install -m 00755 -o codendiadm -g codendiadm -d /var/lib/tuleap/tracker
	install -m 00750 -o codendiadm -g codendiadm -d /etc/tuleap
	install -m 00750 -o codendiadm -g codendiadm -d /etc/tuleap/conf
}

setup_redis() {
    install -m 00640 -o codendiadm -g codendiadm /usr/share/tuleap/src/etc/redis.inc.dist /etc/tuleap/conf/redis.inc
}

setup_database() {
    MYSQL_USER=tuleapadm
    MYSQL_PASSWORD=welcome0
    MYSQL_DBNAME=tuleap
    MYSQL_CLI="/opt/rh/rh-mysql80/root/usr/bin/mysql"
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
        --site-admin-password="welcome0"

    TLP_SYSTEMCTL=docker-centos7 /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:tuleap --force --tuleap-fqdn="localhost" --php-version=$PHP_VERSION
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
    sudo -u codendiadm /usr/bin/tuleap plugin:install \
        tracker \
        docman \
        agiledashboard \
        statistics \
        git \
        pullrequest \
        oauth2_server \
        program_management \
        onlyoffice \
        mediawiki mediawiki_standalone \
        hudson_git \
        fts_db \
        fts_meilisearch \
        baseline \
        gitlab \
        roadmap \
        tracker_functions
}

seed_plugin_data() {
    for fixture_dir in $(find /usr/share/tuleap/plugins/*/tests/integration/_fixtures/* -maxdepth 1 -type d)
    do
        if [ -f "$fixture_dir/project.xml" ] && [ -f "$fixture_dir/users.xml" ]; then
            load_project "$fixture_dir"
        fi
    done
}

setup_tuleap
setup_redis
setup_database
seed_data
seed_plugin_data
