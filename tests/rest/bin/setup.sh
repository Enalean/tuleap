#!/bin/bash

set -e

if [ -z "$MYSQL_DAEMON" ]; then
    MYSQL_DAEMON=mysqld
fi

if [ -z "$HTTPD_DAEMON" ]; then
    HTTPD_DAEMON=httpd
fi

setup_nss() {
    echo "Installing NSS configuration files..."
    cat /usr/share/tuleap/src/etc/libnss-mysql.cfg.dist | \
	sed \
            -e "s/%sys_dbhost%/localhost/" \
            -e "s/%sys_dbname%/tuleap/" \
            -e "s/%sys_dbuser%/tuleapadm/" \
            -e "s/%sys_dbauth_passwd%/welcome0/" \
        > /etc/libnss-mysql.cfg

    cat /usr/share/tuleap/src/etc/libnss-mysql-root.cfg.dist | \
	sed \
            -e "s/%sys_dbauth_passwd%/welcome0/" \
        > /etc/libnss-mysql-root.cfg


    chown root:root /etc/libnss-mysql.cfg /etc/libnss-mysql-root.cfg
    chmod 644 /etc/libnss-mysql.cfg
    chmod 600 /etc/libnss-mysql-root.cfg

    # Update nsswitch.conf to use libnss-mysql
    if [ -f "/etc/nsswitch.conf" ]; then
	# passwd
	grep ^passwd  /etc/nsswitch.conf | grep -q mysql
	if [ $? -ne 0 ]; then
	    perl -i'.orig' -p -e "s/^passwd(.*)/passwd\1 mysql/g" /etc/nsswitch.conf
	fi

	# shadow
	grep ^shadow  /etc/nsswitch.conf | grep -q mysql
	if [ $? -ne 0 ]; then
	    perl -i -p -e "s/^shadow(.*)/shadow\1 mysql/g" /etc/nsswitch.conf
	fi

	# group
	grep ^group  /etc/nsswitch.conf | grep -q mysql
	if [ $? -ne 0 ]; then
	    perl -i -p -e "s/^group(.*)/group\1 mysql/g" /etc/nsswitch.conf
	fi
    else
	echo '/etc/nsswitch.conf does not exist. Cannot use MySQL authentication!'
    fi

    service nscd restart
}

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

    mysql -e "GRANT SELECT ON $MYSQL_DBNAME.user to dbauthuser@'localhost' identified by '$MYSQL_PASSWORD';"
    mysql -e "GRANT SELECT ON $MYSQL_DBNAME.groups to dbauthuser@'localhost';"
    mysql -e "GRANT SELECT ON $MYSQL_DBNAME.user_group to dbauthuser@'localhost';"
    mysql -e "GRANT SELECT,UPDATE ON $MYSQL_DBNAME.svn_token to dbauthuser@'localhost';"
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
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php tracker" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php cardwall" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php agiledashboard" -l codendiadm
    su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php frs" -l codendiadm

    load_project /usr/share/tuleap/tests/rest/_fixtures/01-private-member
    load_project /usr/share/tuleap/tests/rest/_fixtures/02-private
    load_project /usr/share/tuleap/tests/rest/_fixtures/03-public
    load_project /usr/share/tuleap/tests/rest/_fixtures/04-public-member
    load_project /usr/share/tuleap/tests/rest/_fixtures/05-pbi
    load_project /usr/share/tuleap/tests/rest/_fixtures/06-dragndrop
    load_project /usr/share/tuleap/tests/rest/_fixtures/07-computedfield
    load_project /usr/share/tuleap/tests/rest/_fixtures/08-public-including-restricted
    load_project /usr/share/tuleap/tests/rest/_fixtures/09-burndown-cache-generation

    echo "Load initial data"
    php -d include_path=/usr/share/tuleap/src/www/include:/usr/share/tuleap/src /usr/share/tuleap/tests/lib/rest/init_data.php ng

    seed_plugin_data
}

seed_plugin_data() {
    for fixture_dir in $(find /usr/share/tuleap/plugins/*/tests/rest/_fixtures/* -maxdepth 1 -type d)
    do
        if [ -f "$fixture_dir/project.xml" ] && [ -f "$fixture_dir/users.xml" ]  && [ -f "$fixture_dir/user_map.csv" ]; then
            load_project "$fixture_dir"
        fi
    done

    echo "Load plugins initial data"
    php -d include_path=/usr/share/tuleap/src/www/include:/usr/share/tuleap/src /usr/share/tuleap/tests/lib/rest/init_data_plugins.php ng
}

setup_tuleap
setup_fpm
setup_apache
setup_database
setup_nss
seed_data
