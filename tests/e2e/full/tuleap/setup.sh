#!/usr/bin/env bash

set -euxo pipefail

export DISPLAY_ERRORS=true

clean_up() {
    # Drop symlink to the file explaining we might to take a look at journalctl
    # It is a annoyance when we do a `docker compose cp ...:/var/log` at the end
    rm -f /var/log/README
}

setup_system_configuration() {
    echo '$sys_logger_level = "debug";' >> /etc/tuleap/conf/local.inc

    sudo -u codendiadm /usr/bin/tuleap config-set sys_project_approval 0
    sudo -u codendiadm /usr/bin/tuleap config-set project_admin_can_choose_visibility 1
    sudo -u codendiadm /usr/bin/tuleap config-set feature_flag_check_actions_context_in_transaction 1
}

enable_plugins() {
    sudo -u codendiadm /usr/bin/tuleap plugin:install \
        tracker \
        cardwall \
        agiledashboard \
        kanban \
        graphontrackersv5 \
        svn \
        git \
        docman \
        mediawiki \
        taskboard \
        crosstracker \
        timetracking \
        oauth2_server \
        mediawiki_standalone \
        projectmilestones  \
        testmanagement  \
        testplan  \
        program_management  \
        frs \
        botmattermost \
        botmattermost_agiledashboard \
        botmattermost_git \
        statistics \
        tracker_functions \
        artidoc \
        pullrequest \
        webauthn

    sudo -u codendiadm /usr/bin/tuleap -c
}

load_project() {
    base_dir=$1

    user_mapping="-m $base_dir/user_map.csv"
    if [ ! -f $base_dir/user_map.csv ]; then
        user_mapping="--automap=no-email,create:A"
    fi
    /usr/bin/tuleap import-project-xml \
        --use-lame-password \
        -u admin \
        -i $base_dir \
        $user_mapping
}

seed_data() {
    /usr/share/tuleap/tests/e2e/full/tuleap/init_data.php

    for project in $(find $TULEAP_SRC/tests/e2e/full/_fixtures/ -maxdepth 1 -mindepth 1 -type d) ; do
        load_project "$project"
    done

    for project in $(find $TULEAP_SRC/plugins/*/tests/e2e/cypress/_fixtures/ -maxdepth 1 -mindepth 1 -type d) ; do
        load_project "$project"
    done

    # System events have started httpd, let's stop it before supervisord takes the control back
    pkill -15 httpd
}

clean_up
setup_system_configuration
enable_plugins
seed_data
