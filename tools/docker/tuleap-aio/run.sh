#!/bin/bash

set -ex

pushd .

curdir=$(dirname $0)
if [ -d $curdir ]; then
    cd $curdir;
fi

TULEAP_INSTALL_TIME="false"
if [ ! -f /data/etc/tuleap/conf/local.inc ]; then
    TULEAP_INSTALL_TIME="true"

    # If tuleap directory is not in data, assume it's first boot and move
    # everything in the mounted dir
    ./boot-install.sh
fi

# Fix path
./boot-fixpath.sh

# Update php config
sed -i \
    -e "s%^short_open_tag = Off%short_open_tag = On%" \
    -e "s%^;date.timezone =%date.timezone = Europe/Paris%" \
    /etc/php.ini

# Update Postfix config
sed -i \
    -e "s%^#myhostname = host.domain.tld%myhostname = $VIRTUAL_HOST%" \
    -e "s%^alias_maps = hash:/etc/aliases%alias_maps = hash:/etc/aliases,hash:/etc/aliases.codendi%" \
    -e "s%^alias_database = hash:/etc/aliases%alias_database = hash:/etc/aliases,hash:/etc/aliases.codendi%" \
    -e "s%^#recipient_delimiter = %recipient_delimiter = %" \
    /etc/postfix/main.cf

# Update nscd config
perl -pi -e "s%enable-cache[\t ]+group[\t ]+yes%enable-cache group no%" /etc/nscd.conf

source mysql-utils.sh

start_mysql

if [ "$TULEAP_INSTALL_TIME" == "false" ]; then
    # It seems there is no way to have nscd in foreground
    /usr/sbin/nscd

    # DB upgrade (after config as we might depends on it)
    ./boot-upgrade.sh
fi

/usr/share/tuleap/tools/utils/php56/run.php

# Activate backend/crontab
/etc/init.d/tuleap start

stop_mysql

popd

exec supervisord -n -c $curdir/supervisord.conf
