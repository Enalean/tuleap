#!/usr/bin/env bash

set -euxo pipefail

export DISPLAY_ERRORS=true

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

	install -m 00755 -o codendiadm -g codendiadm /usr/share/tuleap/src/utils/tuleap /usr/bin/tuleap
	ln -s /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php /usr/bin/tuleap-cfg

	install -m 00755 -o codendiadm -g codendiadm -d /var/lib/tuleap/tracker
	install -m 00755 -o codendiadm -g codendiadm -d /var/lib/tuleap/gitolite/admin
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

    echo "Use remote db $DB_HOST"

    # runner should have access to Tuleap conf, esp. database.inc because some tests pre-cond changes values directly
    # into the db (@see \Test\Rest\TuleapConfig)
    usermod -a -G codendiadm runner

    /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:mysql-init \
        --host="$DB_HOST" \
        --admin-user=root \
        --admin-password=welcome0 \
        --db-name="$MYSQL_DBNAME" \
        --app-user="$MYSQL_USER" \
        --app-password="$MYSQL_PASSWORD" \
        --tuleap-fqdn="localhost.localdomain" \
        --site-admin-password="$MYSQL_PASSWORD"

    TLP_SYSTEMCTL=docker-centos7 /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:tuleap --force --tuleap-fqdn="localhost" --php-version=$PHP_VERSION
    echo '$sys_logger_level = "debug";' >> /etc/tuleap/conf/local.inc
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
    sudo -u codendiadm PHP="$PHP_CLI" /usr/share/tuleap/src/utils/tuleap plugin:install \
        tracker \
        cardwall \
        agiledashboard \
        graphontrackersv5 \
        frs \
        svn \
        git \
        crosstracker \
        create_test_env \
        docman \
        hudson \
        hudson_git \
        gitlab \
        timetracking \
        testmanagement \
        testplan \
        taskboard \
        roadmap \
        program_management \
        mediawiki_standalone

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
    load_project /usr/share/tuleap/tests/rest/_fixtures/16-public-template
    load_project /usr/share/tuleap/tests/rest/_fixtures/17-private-template

    echo "Load initial data"
    PHP="$PHP_CLI" "$PHP_CLI" /usr/share/tuleap/tests/rest/bin/init_data.php

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
    PHP="$PHP_CLI" "$PHP_CLI" /usr/share/tuleap/tests/rest/bin/init_data_plugins.php
}

setup_tuleap
setup_redis
setup_database
tuleap_db_config
sudo -u codendiadm PHP="$PHP_CLI" /usr/bin/tuleap worker:supervisor --quiet start &
seed_data
$PHP_FPM --daemonize
nginx
