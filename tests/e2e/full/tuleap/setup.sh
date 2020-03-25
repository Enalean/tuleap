#!/bin/bash

set -euxo pipefail

DB_HOST="mysql57"

setup_lhs() {
    touch /etc/aliases.codendi

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

    cat /usr/share/tuleap/src/etc/database.inc.dist | \
        sed \
         -e "s/localhost/$DB_HOST/" \
         -e "s/%sys_dbname%/tuleap/" \
         -e "s/%sys_dbuser%/tuleapadm/" \
         -e "s/%sys_dbpasswd%/welcome0/" > /etc/tuleap/conf/database.inc

    cat /usr/share/tuleap/src/etc/local.inc.dist | \
    sed \
    -e "s#/var/lib/tuleap/ftp/codendi#/var/lib/tuleap/ftp/tuleap#g" \
    -e "s#%sys_default_domain%#tuleap#g" \
    -e "s#%sys_fullname%#tuleap#g" \
    -e "s#%sys_dbauth_passwd%#welcome0#g" \
    -e "s#%sys_org_name%#Tuleap#g" \
    -e "s#%sys_long_org_name%#Tuleap#g" \
    -e 's#\$sys_https_host =.*#\$sys_https_host = "tuleap";#' \
    -e 's#\$sys_logger_level =.*#\$sys_logger_level = "debug";#' \
    -e 's#/home/users##' \
    -e 's#/home/groups##' \
    > /etc/tuleap/conf/local.inc

    echo '$disable_forge_upgrade_warnings=1;' >> /etc/tuleap/conf/local.inc

    cp /usr/share/tuleap/src/utils/svn/Tuleap.pm /usr/share/perl5/vendor_perl/Apache/Tuleap.pm
    cp /usr/share/tuleap/src/utils/fileforge.pl /usr/lib/tuleap/bin/fileforge
}

setup_database() {
    MYSQL_USER=tuleapadm
    MYSQL_PASSWORD=welcome0
    MYSQL_DBNAME=tuleap

    MYSQLROOT="mysql -h$DB_HOST -uroot -pwelcome0"

    /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:mysql-init \
        --host="$DB_HOST" \
        --admin-user=root \
        --admin-password=welcome0 \
        --db-name="$MYSQL_DBNAME" \
        --app-user="$MYSQL_USER@%" \
        --app-password="$MYSQL_PASSWORD" \
        --mediawiki="per-project"

    /usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php setup:mysql \
        --host="$DB_HOST" \
        --user="$MYSQL_USER" \
        --dbname="$MYSQL_DBNAME" \
        --password="$MYSQL_PASSWORD" \
        welcome0 \
        localhost

    $MYSQLROOT -e "DELETE FROM tuleap.password_configuration"
    $MYSQLROOT -e "INSERT INTO tuleap.password_configuration values (0)"

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

seed_data() {
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php tracker" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php cardwall" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php agiledashboard" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php svn" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php git" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php docman" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php mediawiki" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php document" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php taskboard" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php crosstracker" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php timetracking" -l codendiadm
    sed -i -e 's#/var/lib/codendi#/var/lib/tuleap#g' /etc/tuleap/plugins/docman/etc/docman.inc

    for project in $(find /usr/share/tuleap/tests/e2e/_fixtures/ -maxdepth 1 -mindepth 1 -type d) ; do
        load_project "$project"
    done

    chown -R codendiadm:codendiadm /var/log/tuleap
}

setup_lhs
setup_tuleap
/usr/share/tuleap/tools/utils/php73/run.php --modules=nginx,fpm
setup_database
seed_data

/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/src/utils/tuleap.php config-set sys_project_approval 0
/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/src/utils/tuleap.php config-set project_admin_can_choose_visibility 1

service php73-php-fpm start
service nginx start

exec tail -f /var/log/nginx/error.log
