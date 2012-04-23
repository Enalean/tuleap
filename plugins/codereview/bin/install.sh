#!/bin/bash
#
# Copyright (c) STMicroelectronics, 2011,2012
#
# This file is licensed under the GNU General Public License version 2. See the file COPYING.
#
#
MYSQL='/usr/bin/mysql'
CAT='/bin/cat'
CHOWN='/bin/chown'
APP_USER='codendiadm'
APP_DIR='/usr/share/codendi/plugins/codereview/reviewboard'
APP_PWD=`/bin/grep sys_dbpasswd /etc/codendi/conf/database.inc | cut -d\" -f2`

conf_value() {
echo $(/bin/grep $1 /etc/codendi/plugins/codereview/etc/codereview.inc | cut -d\" -f2)
}

##############################################
# Installing the ReviewBoard database
#
db_install() {
# Get the mysql password from the install
    $CAT <<EOF | $MYSQL -u$APP_USER -p$APP_PWD
CREATE DATABASE IF NOT EXISTS reviewboard;
GRANT ALL PRIVILEGES on reviewboard.* to '$APP_USER'@'localhost' identified by '$APP_PWD';
FLUSH PRIVILEGES;
EOF
}
db_install

# Install plugin
#    $CAT <<EOF | $MYSQL -u$APP_USER codendi -p$APP_PWD
#INSERT INTO plugin (name, available) VALUES ('plugin_codereview', '1');
#EOF
##############################################
# Installing the ReviewBoard Site
#

rb-site install --noinput --domain-name=$(conf_value domain_name) --site-root=$(conf_value site_root) --media-url=$(conf_value media_url) --db-type=mysql --db-name=reviewboard --db-user=$APP_USER --db-pass=$APP_PWD --cache-type=memcached --cache-info=$(conf_value cache_info) --web-server-type=apache --python-loader=wsgi --admin-user=$(conf_value admin_user) --admin-password=$(conf_value admin_pwd) --admin-email=$(conf_value admin_mail) $APP_DIR/ --console

##############################################
# Updating ownership
#
$CHOWN -R $APP_USER:$APP_USER $APP_DIR/htdocs/media/uploaded
$CHOWN -R $APP_USER:$APP_USER $APP_DIR/data

##############################################
# Updating conf
#
# Add wsgi module to httpd.conf 
sed -i '/^LoadModule version_module modules\/mod_version.so/i\
#LoadModule wsgi_module modules\/mod_wsgi.so'  /etc/httpd/conf/httpd.conf
# httpd.conf append with wsgi conf
cat $APP_DIR/conf/apache-wsgi.conf >> /etc/httpd/conf/httpd.conf
