#!/bin/sh
if ! [ $# -eq 4 ]; then
  echo "this.sh <dbpass> <dbhost> <dbname> <full-path-to-forge-upgrade-config>"
  exit 1
fi
pass=$1
dbhost=$2
dbname=$3
configfile=$4
cd /usr/share/codendi/src/updates/
sh upgrade.sh 016_docman_lock && sleep 1
sh upgrade.sh 017_approval_table_modify_index  && sleep 1
sh upgrade.sh 018_wiki_fix_textformattingrules && sleep 1
sh upgrade.sh 024_svn_index && sleep 1
sh upgrade.sh 025_docman_approval_table_index && sleep 1
sh upgrade.sh 027_reset_artifact_permission && sleep 1
cd -

sudo yum install forgeupgrade
# config???!!!!
# create tables *_bucket *_log ???
mysql -ucodendiadm -p$pass -h$dbhost $dbname < forgeupgrade_tables.sql 

/usr/lib/forgeupgrade/bin/forgeupgrade --config=$configfile update

mysql -ucodendiadm -p$pass -h$dbhost $dbname < orange2reference.sql 
