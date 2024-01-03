#! /usr/bin/env bash

exec env -i PATH="$PATH" /opt/remi/php81/root/usr/sbin/php-fpm --nodaemonize --fpm-config /usr/share/tuleap/plugins/mediawiki_standalone/etc/php-fpm/mediawiki-tuleap.conf
