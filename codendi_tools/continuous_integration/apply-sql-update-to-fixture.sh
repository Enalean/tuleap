#!/bin/sh
if [ $# -ne 5 ] ; then
  echo "Usage : % mysqlhost sshuser mysqluser mysqlpass \"mysqlcmd\""
  exit 1
fi
mysqlhost=$1
shift
sshuser=$1
shift
mysqluser=$1
shift
mysqlpass=$1
shift
mysqlcmd=$1
ssh root@piton -C "mysql -B -pwelcome0 -ucodendiadm codendi < /usr/share/codendi/codendi_tools/plugins/tests/functional/fixture.sql"
ssh root@piton -C "echo $mysqlcmd | mysql -B -p$mysqlpass -u$mysqluser codendi"
ssh root@piton -C "mysqldump -pwelcome0 -ucodendiadm codendi > /usr/share/codendi/codendi_tools/plugins/tests/functional/fixture.sql"

