#!/usr/bin/env bash

set -ex

function generate_passwd {
    (tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c32) 2>/dev/null
    printf ""
}

mkdir -p /data/etc/httpd/
mkdir -p /data/home
mkdir -p /data/lib
mkdir -p /data/etc/logrotate.d
mkdir -p /data/root && chmod 700 /data/root

pushd . > /dev/null
cd /var/lib
[ -d /var/lib/gitolite ] && mv /var/lib/gitolite /data/lib && ln -s /data/lib/gitolite gitolite
popd > /dev/null

# Install Tuleap
/usr/share/tuleap/tools/setup.el7.sh \
    --assumeyes \
    --configure \
    --mysql-server=db \
    --mysql-password=$MYSQL_ROOT_PASSWORD \
    --server-name=$VIRTUAL_HOST

# Activate LDAP plugin
sudo -u codendiadm /usr/bin/tuleap plugin:install ldap
cp /usr/share/tuleap/tools/docker/tuleap-aio-dev/ldap.inc /etc/tuleap/plugins/ldap/etc/ldap.inc
echo '$sys_auth_type = "ldap";' >> /etc/tuleap/conf/local.inc

# Log level debug
echo '$sys_logger_level = "debug";' >> /etc/tuleap/conf/local.inc

chown -R codendiadm:codendiadm /etc/tuleap

# Create fake file to avoid error below when moving
touch /etc/aliases.codendi

# Ensure system will be synchronized ASAP
/usr/bin/tuleap queue-system-check

### Move all generated files to persistant storage ###

# Conf
mv /etc/httpd/conf            /data/etc/httpd
mv /etc/httpd/conf.d          /data/etc/httpd
mv /etc/tuleap                /data/etc
mv /etc/aliases               /data/etc
mv /etc/aliases.codendi       /data/etc
mv /etc/logrotate.d/httpd     /data/etc/logrotate.d
mv /etc/my.cnf                /data/etc
mv /root/.tuleap_passwd       /data/root

# Data
mv /var/lib/tuleap /data/lib

# Will be restored by boot-fixpath.sh later
[ -h /var/lib/gitolite ] && rm /var/lib/gitolite
