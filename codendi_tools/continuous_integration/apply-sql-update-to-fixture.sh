#!/bin/sh
if [ $# -ne 4 ] ; then
  echo "Usage : % mysqlhost mysqluser mysqlpass \"mysqlcmd\""
  exit 1
fi
mysqlhost=$1
shift
mysqluser=$1
shift
mysqlpass=$1
shift
mysqlcmd=$1
mysql -h$mysqlhost -B -pwelcome0 -ucodendiadm codendi < codendi_tools/plugins/tests/functional/fixture.sql
echo $mysqlcmd | mysql -B -h$mysqlhost -p$mysqlpass -u$mysqluser codendi
mysqldump -h$mysqlhost -pwelcome0 -ucodendiadm codendi > codendi_tools/plugins/tests/functional/fixture.sql

