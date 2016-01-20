#!/bin/sh

#
# /!\ Prototype, do not use it /!\
#

exit 1

# Migrate a Tuleap CentOS 5 RPM install from PHP 5.1 to PHP 5.3

TULEAP_REPO_NAME=tuleap-local
TULEAP_REPO_CONF=/etc/yum.repos.d/$TULEAP_REPO_NAME.repo

if `grep '\$basearch-php53' $TULEAP_REPO_CONF`; then
  echo 'You are already using Tuleap with php53.'
  exit 1
fi

set +x

service httpd stop
/etc/init.d/codendi stop

repoquery --repoid=$TULEAP_REPO_NAME -a | xargs yum -y remove
sed -i 's/\$basearch$/\$basearch-php53/' $TULEAP_REPO_CONF
yum -y install tuleap-all

/etc/init.d/codendi start
service httpd start
