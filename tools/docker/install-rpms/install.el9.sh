#!/bin/bash

set -ex -o pipefail

createrepo /rpms/RPMS/noarch/

systemctl start systemd-user-sessions.service
systemctl start mysqld

yum install -y tuleap-plugin-agiledashboard \
  tuleap-plugin-graphontrackers \
  tuleap-theme-burningparrot \
  tuleap-theme-flamingparrot \
  tuleap-plugin-svn

/usr/share/tuleap/tools/setup.el7.sh \
    --assumeyes \
    --configure \
    --mysql-server='localhost' \
    --server-name='tuleap.test'
