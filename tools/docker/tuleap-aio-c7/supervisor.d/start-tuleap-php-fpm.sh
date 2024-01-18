#! /usr/bin/env bash

for env_var in $(env | grep -v "^TULEAP_\|^PATH=" | cut -d "=" -f 1)
do
    unset "$env_var"
done

exec /opt/remi/php82/root/usr/sbin/php-fpm --nodaemonize
