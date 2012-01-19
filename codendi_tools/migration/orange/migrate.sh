#!/bin/sh

db_conf_file=/etc/codendi/conf/database.inc
forgeupgrade_conf_file=/etc/codendi/forgeupgrade/config.ini

function read_db_conf() {
    echo "<?php echo \$$1; ?>" | php -d auto_prepend_file=$db_conf_file
}

dbhost=`read_db_conf sys_dbhost`
dbname=`read_db_conf sys_dbname`
dbuser=`read_db_conf sys_dbuser`
dbpasswd=`read_db_conf sys_dbpasswd`

cd /usr/share/codendi/src/updates/
sh upgrade.sh 016_docman_lock && sleep 1
sh upgrade.sh 017_approval_table_modify_index  && sleep 1
sh upgrade.sh 018_wiki_fix_textformattingrules && sleep 1
sh upgrade.sh 024_svn_index && sleep 1
sh upgrade.sh 025_docman_approval_table_index && sleep 1
sh upgrade.sh 027_reset_artifact_permission && sleep 1
cd -

/usr/lib/forgeupgrade/bin/forgeupgrade --config=$forgeupgrade_conf_file update

mysql -u$dbuser -p$dbpasswd -h$dbhost $dbname < orange2reference.sql 
