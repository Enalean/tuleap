#!/usr/bin/env bash

set -eu

COMPOSE_PROJECT_NAME="$(basename $(pwd))"

if docker inspect tuleap_data > /dev/null 2>&1; then
    if ! docker run --rm -v tuleap_db-data:/stuff centos:6 test -d /stuff/mysql; then
        echo "Migrate legacy data containers to volumes"
        echo "Copy DB"
        docker run --rm -ti --volumes-from tuleap_db_data -v "${COMPOSE_PROJECT_NAME}_db-data":/copy/mysql centos:6 cp -ar /var/lib/mysql /copy
        echo "Copy LDAP"
        docker run --rm -ti --volumes-from tuleap_ldap_data -v "${COMPOSE_PROJECT_NAME}_ldap-data":/copy/data centos:6 cp -ar /data /copy
        echo "Copy Gerrit"
        docker run --rm -ti --volumes-from tuleap_gerrit_data -v "${COMPOSE_PROJECT_NAME}_gerrit-data":/copy/gerrit centos:6 cp -ar /home/gerrit /copy
        echo "Copy Tuleap"
        docker run --rm -ti --volumes-from tuleap_data -v "${COMPOSE_PROJECT_NAME}_tuleap-data":/copy/data centos:6 cp -ar /data /copy
    fi
fi
