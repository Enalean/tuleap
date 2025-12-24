_pluginGit() {
    ${tuleapcfg} site-deploy:gitolite3-config
    ${tuleapcfg} site-deploy:gitolite3-hooks

    local plugin_git_configured="false"

    if ! $(${tuleapcfg} systemctl is-enabled tuleap-process-system-events-git.timer); then
        ${tuleapcfg} systemctl enable "tuleap-process-system-events-git.timer"
        plugin_git_configured="true"
    fi

    if ! $(${tuleapcfg} systemctl is-active tuleap-process-system-events-git.timer); then
        ${tuleapcfg} systemctl start "tuleap-process-system-events-git.timer"
        plugin_git_configured="true"
    fi

    if [ ${plugin_git_configured} = "true" ]; then
        _infoMessage "Plugin Git is configured"
        plugins_configured+=('true')
    else
        _infoMessage "Plugin Git is already configured"
    fi
}

_pluginSVN() {
    sudo -u codendiadm /usr/bin/tuleap plugin:install svn
    /usr/bin/tuleap setup:svn
}

_pluginMediawiki() {
    local server_name=$(/usr/bin/tuleap config-get sys_default_domain)

    ${tuleapcfg} setup:mysql-init \
        --tuleap-fqdn="${server_name}" \
        --host="${mysql_server}" \
        --admin-user="${mysql_user}" \
        --admin-password="${mysql_password}" \
        --mediawiki=per-project
}
