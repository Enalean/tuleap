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
# Install plugin
    $CAT <<EOF | $MYSQL -u$APP_USER codendi -p$APP_PWD
INSERT INTO plugin (name, available) VALUES ('codereview', '1');
EOF
##############################################
# Installing the ReviewBoard Site
#

# rb-site install --options $APP_DIR/ --console
##############################################
# Updating conf
#
# httpd.conf append with wsgi conf
##############################################
# Updating ownership
#
$CHOWN -R $APP_USER:$APP_USER $APP_DIR/htdocs/media/uploaded
$CHOWN -R $APP_USER:$APP_USER $APP_DIR/data
db_install
