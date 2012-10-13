#!/bin/sh

scriptdir=`dirname $0`

db_conf_file=/etc/codendi/conf/database.inc
forgeupgrade_conf_file=/etc/codendi/forgeupgrade/config.ini
forgeupgrade_db_structure=/usr/share/forgeupgrade/db/install-mysql.sql
srcdir=/usr/share/codendi

read_db_conf() {
    echo "<?php echo \$$1; ?>" | php -d auto_prepend_file=$db_conf_file
}

dbhost=`read_db_conf sys_dbhost`
dbname=`read_db_conf sys_dbname`
dbuser=`read_db_conf sys_dbuser`
dbpasswd=`read_db_conf sys_dbpasswd`

mysqlcmd="mysql -u$dbuser -p$dbpasswd -h$dbhost $dbname"

cd $srcdir/src/updates/
sh upgrade.sh 016_docman_lock && sleep 1
sh upgrade.sh 017_approval_table_modify_index  && sleep 1
sh upgrade.sh 018_wiki_fix_textformattingrules && sleep 1
sh upgrade.sh 024_svn_index && sleep 1
sh upgrade.sh 025_docman_approval_table_index && sleep 1
sh upgrade.sh 027_reset_artifact_permission && sleep 1
cd -

$mysqlcmd < $forgeupgrade_db_structure

/usr/lib/forgeupgrade/bin/forgeupgrade --config=$forgeupgrade_conf_file update

$mysqlcmd < $scriptdir/orange2reference.sql 

$mysqlcmd < $scriptdir/clean_old_plugins.sql 

$mysqlcmd < $srcdir/plugins/ldap/db/install.sql

# Domain name change
$mysqlcmd < $scriptdir/domain_name.sql 
(cd /usr/lib/mailman && bin/withlist -a -l -r fix_url)
