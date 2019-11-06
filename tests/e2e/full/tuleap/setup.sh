#!/bin/bash

set -euxo pipefail

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
    MYSQL_HOST=localhost
    MYSQL_USER=tuleapadm
    MYSQL_PASSWORD=welcome0
    MYSQL_DBNAME=tuleap
    MYSQL="mysql -h$MYSQL_HOST -u$MYSQL_USER -p$MYSQL_PASSWORD"

    echo "Setup database"
    mkdir -p /tmp/mysql
    chown mysql:mysql /tmp/mysql

    service mysqld start
    mysql -e "GRANT ALL PRIVILEGES on *.* to '$MYSQL_USER'@'$MYSQL_HOST' identified by '$MYSQL_PASSWORD'"
    $MYSQL -e "DROP DATABASE IF EXISTS $MYSQL_DBNAME"
    $MYSQL -e "CREATE DATABASE $MYSQL_DBNAME CHARACTER SET utf8"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/database_structure.sql"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/database_initvalues.sql"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/tests/e2e/full/tuleap/cypress_database_init_values.sql"

    mysql -e "GRANT SELECT ON $MYSQL_DBNAME.user to dbauthuser@'localhost' identified by '$MYSQL_PASSWORD';"
    mysql -e "GRANT SELECT ON $MYSQL_DBNAME.groups to dbauthuser@'localhost';"
    mysql -e "GRANT SELECT ON $MYSQL_DBNAME.user_group to dbauthuser@'localhost';"
    mysql -e "GRANT SELECT,UPDATE ON $MYSQL_DBNAME.svn_token to dbauthuser@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"
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
    mysql -e "DELETE FROM tuleap.password_configuration;"
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php tracker" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php cardwall" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php agiledashboard" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php svn" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php git" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php docman" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php mediawiki" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php document" -l codendiadm
    sed -i -e 's#/var/lib/codendi#/var/lib/tuleap#g' /etc/tuleap/plugins/docman/etc/docman.inc

    load_project /usr/share/tuleap/tests/e2e/_fixtures/permission_project
    load_project /usr/share/tuleap/tests/e2e/_fixtures/docman_project
    load_project /usr/share/tuleap/tests/e2e/_fixtures/document_project
    load_project /usr/share/tuleap/tests/e2e/_fixtures/git_project
    load_project /usr/share/tuleap/tests/e2e/_fixtures/frs_project
    load_project /usr/share/tuleap/tests/e2e/_fixtures/project_administration
    load_project /usr/share/tuleap/tests/e2e/_fixtures/mediawiki_public_project
    load_project /usr/share/tuleap/tests/e2e/_fixtures/platform_allows_anonymous
    load_project /usr/share/tuleap/tests/e2e/_fixtures/platform_allows_restricted
    load_project /usr/share/tuleap/tests/e2e/_fixtures/tracker_project

    chown -R codendiadm:codendiadm /var/log/tuleap
}

setup_lhs
setup_tuleap
/usr/share/tuleap/tools/utils/php73/run.php --modules=nginx,fpm
setup_database
seed_data

/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/src/utils/tuleap.php config-set sys_project_approval 0
/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/src/utils/tuleap.php config-set project_admin_can_choose_visibility 1
/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/src/utils/tuleap.php set-user-password admin welcome0

service php73-php-fpm start
service nginx start

exec tail -f /var/log/nginx/error.log
