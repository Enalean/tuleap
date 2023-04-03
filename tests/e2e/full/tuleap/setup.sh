#!/usr/bin/env bash

set -euxo pipefail

export DISPLAY_ERRORS=true

setup_lhs() {
    touch /etc/aliases.codendi

    cat /etc/passwd

    mkdir -p /etc/tuleap/conf \
        /etc/tuleap/plugins \
        /var/tmp/tuleap_cache/lang \
        /var/tmp/tuleap_cache/combined \
        /var/tmp/tuleap_cache/restler \
        /var/log/tuleap \
        /usr/lib/tuleap/bin \
        /var/lib/tuleap/ftp/pub \
        /var/lib/tuleap/ftp/incoming \
        /var/lib/tuleap/ftp/tuleap \
        /var/lib/tuleap/gitolite/admin \
        /var/lib/tuleap/docman

    chown -R codendiadm:codendiadm /etc/tuleap \
        /var/tmp/tuleap_cache \
        /var/lib/tuleap \
        /var/log/tuleap
}

setup_tuleap() {
    echo "Setup Tuleap"

    install -m 00755 -o codendiadm -g codendiadm /usr/share/tuleap/src/utils/tuleap /usr/bin/tuleap
    cp /usr/share/tuleap/src/utils/fileforge.pl /usr/lib/tuleap/bin/fileforge
}

setup_redis() {
    install -m 00640 -o codendiadm -g codendiadm /usr/share/tuleap/src/etc/redis.inc.dist /etc/tuleap/conf/redis.inc
}

setup_database() {
    MYSQL_USER=tuleapadm
    MYSQL_PASSWORD=welcome0
    MYSQL_DBNAME=tuleap

    MYSQLROOT="/usr/bin/mysql -h$DB_HOST -uroot -pwelcome0"

    /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:mysql-init \
        --host="$DB_HOST" \
        --admin-user=root \
        --admin-password=welcome0 \
        --db-name="$MYSQL_DBNAME" \
        --app-user="$MYSQL_USER" \
        --app-password="$MYSQL_PASSWORD" \
        --mediawiki="per-project" \
        --tuleap-fqdn="tuleap" \
        --site-admin-password="welcome0"

    TLP_SYSTEMCTL=docker-centos7 /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:tuleap --force --tuleap-fqdn="tuleap"
    echo '$sys_logger_level = "debug";' >> /etc/tuleap/conf/local.inc

    $MYSQLROOT -e "DELETE FROM tuleap.password_configuration"
    $MYSQLROOT -e "INSERT INTO tuleap.password_configuration values (0)"

    /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:forgeupgrade

    enable_plugins

    /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php configure apache
    /usr/bin/tuleap setup:svn

    $MYSQLROOT $MYSQL_DBNAME < "/usr/share/tuleap/tests/e2e/full/tuleap/cypress_database_init_values.sql"
}

load_project() {
    base_dir=$1

    user_mapping="-m $base_dir/user_map.csv"
    if [ ! -f $base_dir/user_map.csv ]; then
        user_mapping="--automap=no-email,create:A"
    fi
    /usr/share/tuleap/src/utils/tuleap import-project-xml \
        --use-lame-password \
        -u admin \
        -i $base_dir \
        $user_mapping
}

enable_plugins() {
    sudo -u codendiadm /usr/bin/tuleap plugin:install \
        tracker \
        cardwall \
        agiledashboard \
        graphontrackersv5 \
        svn \
        git \
        docman \
        mediawiki \
        taskboard \
        crosstracker \
        timetracking \
        oauth2_server \
        projectmilestones  \
        testmanagement  \
        testplan  \
        program_management  \
        frs \
        statistics
}

seed_data() {
    sed -i -e 's#/var/lib/codendi#/var/lib/tuleap#g' /etc/tuleap/plugins/docman/etc/docman.inc

    for project in $(find /usr/share/tuleap/tests/e2e/full/_fixtures/ -maxdepth 1 -mindepth 1 -type d) ; do
        load_project "$project"
    done

    for project in $(find /usr/share/tuleap/plugins/*/tests/e2e/cypress/_fixtures/ -maxdepth 1 -mindepth 1 -type d) ; do
        load_project "$project"
    done

    chown -R codendiadm:codendiadm /var/log/tuleap
}

setup_system_configuration() {
    sudo -u codendiadm /usr/bin/tuleap config-set sys_project_approval 0
    sudo -u codendiadm /usr/bin/tuleap config-set project_admin_can_choose_visibility 1

    # Email are relayed to mailhog catch all
    echo "relayhost = mailhog:1025" >> /etc/postfix/main.cf
}

setup_lhs
setup_tuleap
setup_redis
setup_database
sudo -u codendiadm /usr/bin/tuleap worker:supervisor --quiet start &
/usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php site-deploy
seed_data
setup_system_configuration

sed -i 's/inet_interfaces = localhost/inet_interfaces = 127.0.0.1/' /etc/postfix/main.cf
/usr/sbin/postfix -c /etc/postfix start

/usr/share/tuleap/tools/docker/tuleap-aio-c7/supervisor.d/start-tuleap-realtime.sh &

/opt/remi/php81/root/usr/sbin/php-fpm --daemonize
nginx
/usr/sbin/httpd

exec tail -f /var/log/nginx/error.log
