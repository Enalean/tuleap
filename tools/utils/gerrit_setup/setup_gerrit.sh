#!/bin/bash

set -ex

USER="gerrit-admin"

options=$(getopt -o h -l user:,useremail:,password: -- "$@")

eval set -- "$options"
while true
do
    case "$1" in
    --user)
        USER=$2;
        shift 2;;
    --useremail)
        USEREMAIL=$2;
        shift 2;;
    --password)
        PASSWORD=$2;
        shift 2;;
    --)
        shift 1; break ;;
    *)
        break ;;
    esac
done

if [ -z "$USER" ]; then
    echo "You must specify --user argument";
    exit 1;
fi

if [ -z "$USEREMAIL" ] || [ -z "$PASSWORD" ]; then
    echo "You must specify --useremail and --password arguments";
    exit 1;
fi

# Should be good by default
GERRIT_SERVER="tuleap-gerrit.gerrit-tuleap.docker"
TULEAP_SERVER="tuleap-web.tuleap-aio-dev.docker"
GERRIT_PORT=8080

# Hardly need to modify
SSH_KEY=/home/codendiadm/.ssh/id_rsa-gerrit
GIT=/opt/rh/sclo-git212/root/usr/bin/git

setup_gerrit_permissions() {
    echo 'You must set the following permissions to the All-Projects project (All-Projects -> General -> Edit Config):'
    cat project.config.tuleap
}

setup_user_and_groups() {
    ssh-keygen -P "" -f $SSH_KEY

    cat $SSH_KEY.pub | curl -XPOST --digest -u $USER:$PASSWORD -H 'Content-type: text/plain' $GERRIT_SERVER:$GERRIT_PORT/a/accounts/self/sshkeys -d @-

    curl -H "Content-Type: application/json;charset=UTF-8" --digest --user $USER:$PASSWORD -X PUT $GERRIT_SERVER:$GERRIT_PORT/a/groups/$TULEAP_SERVER-replication -d '{"visible_to_all": true}'

    ssh -i $SSH_KEY -oStrictHostKeyChecking=no -p 29418 $USER@$GERRIT_SERVER gerrit version
}

setup_user_and_groups
setup_gerrit_permissions
