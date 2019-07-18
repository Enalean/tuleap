#!/bin/bash

set -e

# It's a "reboot", just discard image default
[ -f /etc/aliases ]               && rm -f /etc/aliases
[ -f /etc/logrotate.d/httpd ]     && rm -f /etc/logrotate.d/httpd
[ -f /etc/my.cnf ]                && rm -f /etc/my.cnf
[ -f /etc/crontab ]               && rm -f /etc/crontab
[ -d /etc/tuleap ]                && rm -rf /etc/tuleap
[ -d /etc/httpd/conf ]            && rm -rf /etc/httpd/conf
[ -d /etc/httpd/conf.d ]          && rm -rf /etc/httpd/conf.d

[ -d /home/codendiadm ]  && rm -rf /home/codendiadm
[ -d /home/groups ]      && rm -rf /home/groups
[ -d /home/users ]       && rm -rf /home/users
[ -d /var/lib/mysql ]    && rm -rf /var/lib/mysql
[ -d /var/lib/tuleap ]   && rm -rf /var/lib/tuleap
[ -d /var/lib/gitolite ] && rm -rf /var/lib/gitolite

if [ -f /data/etc/ssh/ssh_host_key ]; then
    rm -f /etc/ssh/ssh_host_*

    ln -s /data/etc/ssh/ssh_host_dsa_key     /etc/ssh/ssh_host_dsa_key
    ln -s /data/etc/ssh/ssh_host_dsa_key.pub /etc/ssh/ssh_host_dsa_key.pub
    ln -s /data/etc/ssh/ssh_host_key         /etc/ssh/ssh_host_key
    ln -s /data/etc/ssh/ssh_host_key.pub     /etc/ssh/ssh_host_key.pub
    ln -s /data/etc/ssh/ssh_host_rsa_key     /etc/ssh/ssh_host_rsa_key
    ln -s /data/etc/ssh/ssh_host_rsa_key.pub /etc/ssh/ssh_host_rsa_key.pub
fi

# Update paths to refer to persistent storage
cd /etc
ln -s /data/etc/tuleap tuleap
ln -s /data/etc/aliases aliases
ln -s /data/etc/my.cnf my.cnf
ln -s /data/etc/crontab crontab

cd /etc/pki/tls/private
[ ! -f localhost.key ] && ln -s /data/etc/pki/tls/private/localhost.key localhost.key

cd /etc/ssl/certs
[ ! -f localhost.crt ] && ln -s /data/etc/ssl/certs/localhost.crt localhost.crt

cd /etc/logrotate.d
ln -s /data/etc/logrotate.d/httpd httpd

cd /etc/httpd
[ ! -f conf ] && ln -s /data/etc/httpd/conf conf
[ ! -f conf.d ] && ln -s /data/etc/httpd/conf.d conf.d

cd /home
[ ! -f codendiadm ] && ln -s /data/home/codendiadm codendiadm
[ ! -f users ] && ln -s /data/home/users users
[ ! -f groups ] && ln -s /data/home/groups groups

cd /var/lib
[ ! -f mysql ] && ln -s /data/lib/mysql mysql
[ ! -f tuleap ] && ln -s /data/lib/tuleap tuleap
[ -d /data/lib/gitolite ] && ln -s /data/lib/gitolite gitolite

if [ -d "/data/etc/nginx" ]; then
rm -rf /etc/nginx
ln -s /data/etc/nginx /etc/nginx
fi

if [ -d "/data/etc/opt/remi/php73/php-fpm.d" ]; then
rm -rf /etc/opt/remi/php73/php-fpm.d
ln -s /data/etc/opt/remi/php73/php-fpm.d /etc/opt/remi/php73/php-fpm.d
fi

mkdir -p /var/tmp/tuleap_cache/php/session /var/tmp/tuleap_cache/php/wsdlcache
chown codendiadm:codendiadm /var/tmp/tuleap_cache/php/session /var/tmp/tuleap_cache/php/wsdlcache
