#!/bin/sh

set -e

if [ -z "$MYSQL_DAEMON" ]; then
    MYSQL_DAEMON=mysqld
fi

if [ -z "$HTTPD_DAEMON" ]; then
    HTTPD_DAEMON=httpd
fi

setup_apache() {
    echo "Setup $HTTPD_DAEMON"
    case "$HTTPD_DAEMON" in
	httpd24-httpd)
	    CONF_DIR=/opt/rh/httpd24/root/etc/httpd
	    TAG=httpd24
	    ;;
	rh-nginx18-nginx)
	    type=nginx
	    CONF_DIR=/etc/opt/rh/rh-nginx18/nginx/
	    TAG=nginx18
	    ;;
	*)
	    CONF_DIR=/etc/httpd
	    TAG=httpd22
    esac
    if [ "$TAG" == "nginx18" ]; then
	cp /usr/share/tuleap/tests/rest/etc/rest-tests.$TAG.conf $CONF_DIR/nginx.conf
    else
	cp /usr/share/tuleap/src/etc/combined.conf.dist $CONF_DIR/conf.d/combined.conf
	sed -i -e "s/User apache/User codendiadm/" \
	    -e "s/Group apache/Group codendiadm/" $CONF_DIR/conf/httpd.conf
	cp /usr/share/tuleap/tests/rest/etc/rest-tests.$TAG.conf $CONF_DIR/conf.d/rest-tests.conf
    fi
    service $HTTPD_DAEMON restart
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
	-e 's#\$sys_rest_api_over_http =.*#\$sys_rest_api_over_http = 1;#' \
	> /etc/tuleap/conf/local.inc
}

setup_fpm() {
    if [ -n "$FPM_DAEMON" ]; then
        echo "Setup FPM $FPM_DAEMON"
        sed -i -e "s/user = apache/user = codendiadm/" \
            -e "s/group = apache/group = codendiadm/" \
            /etc/opt/rh/rh-php56/php-fpm.d/www.conf
        cat /usr/share/tuleap/src/etc/fpm.conf.dist >> /etc/opt/rh/rh-php56/php-fpm.d/www.conf
        service $FPM_DAEMON restart
    fi
}

setup_database() {
    MYSQL_HOST=localhost
    MYSQL_USER=tuleapadm
    MYSQL_PASSWORD=welcome0
    MYSQL_DBNAME=tuleap
    MYSQL="mysql -h$MYSQL_HOST -u$MYSQL_USER -p$MYSQL_PASSWORD"

    echo "Setup database $MYSQL_DAEMON"
    service $MYSQL_DAEMON restart
    mysql -e "GRANT ALL PRIVILEGES on *.* to '$MYSQL_USER'@'$MYSQL_HOST' identified by '$MYSQL_PASSWORD'"
    $MYSQL -e "DROP DATABASE IF EXISTS $MYSQL_DBNAME"
    $MYSQL -e "CREATE DATABASE $MYSQL_DBNAME CHARACTER SET utf8"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/database_structure.sql"
    $MYSQL $MYSQL_DBNAME < "/usr/share/tuleap/src/db/mysql/database_initvalues.sql"
    $MYSQL $MYSQL_DBNAME -e "LOAD DATA LOCAL INFILE '/usr/share/tuleap/tests/rest/_fixtures/phpwiki/rest-test-wiki-group-list' INTO TABLE wiki_group_list"
    $MYSQL $MYSQL_DBNAME -e "LOAD DATA LOCAL INFILE '/usr/share/tuleap/tests/rest/_fixtures/phpwiki/rest-test-wiki-page' INTO TABLE wiki_page"
    $MYSQL $MYSQL_DBNAME -e "LOAD DATA LOCAL INFILE '/usr/share/tuleap/tests/rest/_fixtures/phpwiki/rest-test-wiki-nonempty' INTO TABLE wiki_nonempty"
    $MYSQL $MYSQL_DBNAME -e "LOAD DATA LOCAL INFILE '/usr/share/tuleap/tests/rest/_fixtures/phpwiki/rest-test-wiki-version' INTO TABLE wiki_version"
    $MYSQL $MYSQL_DBNAME -e "LOAD DATA LOCAL INFILE '/usr/share/tuleap/tests/rest/_fixtures/phpwiki/rest-test-wiki-recent' INTO TABLE wiki_recent"
}

load_project() {
    base_dir=$1

    /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/src/utils/import_project_xml.php \
        -u admin \
        -i $base_dir \
        -m $base_dir/user_map.csv

}

seed_data() {
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php tracker" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php cardwall" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php agiledashboard" -l codendiadm

    load_project /usr/share/tuleap/tests/rest/_fixtures/01-private-member
    load_project /usr/share/tuleap/tests/rest/_fixtures/02-private
    load_project /usr/share/tuleap/tests/rest/_fixtures/03-public
    load_project /usr/share/tuleap/tests/rest/_fixtures/04-public-member
    load_project /usr/share/tuleap/tests/rest/_fixtures/05-pbi
    load_project /usr/share/tuleap/tests/rest/_fixtures/06-dragndrop

    echo "Load initial data"
    php -d include_path=/usr/share/tuleap/src/www/include:/usr/share/tuleap/src /usr/share/tuleap/tests/lib/rest/init_data.php ng
}

setup_tuleap
setup_fpm
setup_apache
setup_database
seed_data
