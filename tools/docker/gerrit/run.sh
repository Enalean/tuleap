#!/usr/bin/env bash

set -e
set -o pipefail

if [ ! -d /data/gerrit/git ]; then
    echo "Generate an ssh key for replication"
    install -d -o gerrit -g gerrit -m 0700 /data/.ssh
    ssh-keygen -P "" -f /data/.ssh/id_rsa
    chown -R gerrit.gerrit /data/.ssh

    echo "Install gerrit"
    mkdir -p /data/gerrit/etc
    cp /*.config /data/gerrit/etc
    java -jar /var/gerrit/bin/gerrit.war init --batch --install-all-plugins --no-auto-start -d /data/gerrit
    java -jar /data/gerrit/bin/gerrit.war reindex -d /data/gerrit

    chown -R gerrit:gerrit /data/gerrit
fi

/bin/rm -f /var/gerrit/.ssh
ln -s /data/.ssh /var/gerrit/.ssh

sed -i -e "s/%SECRET%/$LDAP_MANAGER_PASSWORD/" /data/gerrit/etc/secure.config

su -l gerrit -c "ssh -oStrictHostKeyChecking=no gitolite@web info" || true

echo "GERRIT_SITE=/data/gerrit" > /etc/default/gerritcodereview
exec su -l gerrit -c "/data/gerrit/bin/gerrit.sh run"
