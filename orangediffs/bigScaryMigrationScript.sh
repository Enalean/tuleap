#!/bin/sh
pushd /usr/share/codendi/src/updates/
sh upgrade.sh 016_docman_lock
sh upgrade.sh 017_approval_table_modify_index
sh upgrade.sh 018_wiki_fix_textformattingrules
sh upgrade.sh 024_svn_index
sh upgrade.sh 025_docman_approval_table_index
sh upgrade.sh 027_reset_artifact_permission
popd

yum install forgeupgrade
# config???!!!!
# create tables *_bucket *_log ???

/usr/lib/forgeupgrade/bin/forgeupgrade --config=/etc/codendi/forgeupgrade/config.ini update

mysql -uroot -p????? -h????? -B < orange2reference.sql 
