#!/bin/bash

createrepo /rpms/RPMS/noarch/

systemctl start systemd-user-sessions.service
systemctl start rh-mysql80-mysqld

yum install -y tuleap-plugin-agiledashboard \
  tuleap-plugin-graphontrackers \
  tuleap-theme-burningparrot \
  tuleap-theme-flamingparrot \
  tuleap-plugin-git \
  tuleap-plugin-svn \
  tuleap-plugin-hudson\*

/usr/share/tuleap/tools/setup.el7.sh \
    --assumeyes \
    --configure \
    --mysql-server='localhost' \
    --server-name='tuleap.test'
