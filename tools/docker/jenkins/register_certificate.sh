#!/usr/bin/env bash
#
# Copyright (c) Enalean, 2021-Present. All Rights Reserved.
#
# This file is a part of Tuleap.
#
# Tuleap is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Tuleap is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
#
#

set -ex
set -o pipefail

while [ ! -f /opt/reverse-proxy-cert/certs/localhost.cert.pem ]; do
    echo "Waiting for Reverse proxy certificate…"
    sleep 1
done

keytool -keystore $JAVA_HOME/lib/security/cacerts -import -trustcacerts -storepass changeit -noprompt -alias tuleap-web-dev -file /opt/reverse-proxy-cert/certs/localhost.cert.pem

cp -af /opt/reverse-proxy-cert/certs/localhost.cert.pem /usr/local/share/ca-certificates/tuleap.crt
update-ca-certificates
