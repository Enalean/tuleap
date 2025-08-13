#!/bin/bash

set -ex -o pipefail

createrepo /rpms/RPMS/noarch/

systemctl start systemd-user-sessions.service
systemctl start mysqld

dnf install -y tuleap-plugin-agiledashboard \
  tuleap-plugin-graphontrackers \
  tuleap-theme-burningparrot \
  tuleap-theme-flamingparrot \
  tuleap-plugin-svn \
  tuleap-plugin-git \
  tuleap-plugin-svn \
  tuleap-plugin-hudson\*

/usr/share/tuleap/tools/setup.sh \
    --assumeyes \
    --configure \
    --mysql-server='localhost' \
    --server-name='tuleap.test'
