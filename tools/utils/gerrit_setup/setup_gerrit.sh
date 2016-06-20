#!/bin/bash

set -ex

USER="gerrit-admin-28"

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
SSH_KEY=/home/codendiadm/.ssh/id_rsa-gerrit28
GIT=/opt/rh/git19/root/usr/bin/git

setup_gerrit_permissions() {
    /bin/rm -rf /tmp/gerrit_all_projects
    $GIT clone http://$GERRIT_SERVER:$GERRIT_PORT/All-Projects /tmp/gerrit_all_projects
    $GIT -C /tmp/gerrit_all_projects config user.email "$USEREMAIL"
    $GIT -C /tmp/gerrit_all_projects config user.name "Tuleap gerrit admin"
    cp project.config.tuleap /tmp/gerrit_all_projects/project.config
    $GIT -C /tmp/gerrit_all_projects add project.config
    $GIT -C /tmp/gerrit_all_projects commit -m "Add access control to operate with Tuleap"
    $GIT -C /tmp/gerrit_all_projects push http://$USER:$PASSWORD@$GERRIT_SERVER:$GERRIT_PORT/All-Projects HEAD:refs/meta/config
}

setup_user_and_groups() {
    ssh-keygen -P "" -f $SSH_KEY

    cat $SSH_KEY.pub | curl -XPOST --digest -u $USER:$PASSWORD -H 'Content-type: text/plain' $GERRIT_SERVER:$GERRIT_PORT/a/accounts/self/sshkeys -d @-

    curl --digest --user $USER:$PASSWORD -X PUT $GERRIT_SERVER:$GERRIT_PORT/a/accounts/self/emails/codendiadm@$TULEAP_SERVER -H "Content-Type: application/json;charset=UTF-8" -d'{"no_confirmation": true}'

    curl -H "Content-Type: application/json;charset=UTF-8" --digest --user $USER:$PASSWORD -X PUT $GERRIT_SERVER:$GERRIT_PORT/a/groups/$TULEAP_SERVER-replication -d '{"visible_to_all": true}'

    ssh -i $SSH_KEY -oStrictHostKeyChecking=no -p 29418 $USER@$GERRIT_SERVER gerrit version
}

setup_gerrit_permissions
setup_user_and_groups
