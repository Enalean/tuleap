#!/usr/bin/env bash

set -euxo pipefail

export DISPLAY_ERRORS=true

if [ -z "$PHP_CLI" ]; then
    echo 'PHP_CLI environment variable must be specified' 1>&2
    exit 1
fi

setup_php() {
    php_ini_location="$($PHP_CLI -r 'echo php_ini_loaded_file();')"
    echo "zend.assertions = 1" >> "$php_ini_location"
    echo "assert.exception = 1" >> "$php_ini_location"
    echo "date.timezone = Europe/Paris" >> "$php_ini_location"
}

setup_database() {
    MYSQL_USER=tuleapadm
    MYSQL_PASSWORD=welcome0
    MYSQL_DBNAME=tuleap

    echo "Use remote db $DB_HOST"

    /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:mysql-init \
        --skip-sanity-check \
        --host="$DB_HOST" \
        --admin-user=root \
        --admin-password=welcome0 \
        --db-name="$MYSQL_DBNAME" \
        --app-user="$MYSQL_USER" \
        --app-password="$MYSQL_PASSWORD" \
        --tuleap-fqdn="localhost" \
        --site-admin-password="welcome0"

    TLP_SYSTEMCTL=docker /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:tuleap --force --tuleap-fqdn="localhost" --php-version=$PHP_VERSION
    echo '$sys_logger_level = "debug";' >> /etc/tuleap/conf/local.inc

    PHP="$PHP_CLI" /usr/share/tuleap/tests/integration/bin/setup-db.php
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
        timetracking \
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
        artidoc \
        pdftemplate \
        tracker_functions \
        crosstracker
}

seed_plugin_data() {
    for fixture_dir in $(find /usr/share/tuleap/plugins/*/tests/integration/_fixtures/* -maxdepth 1 -type d)
    do
        if [ -f "$fixture_dir/project.xml" ] && [ -f "$fixture_dir/users.xml" ]; then
            load_project "$fixture_dir"
        fi
    done
}

setup_php
setup_database
seed_data
seed_plugin_data
