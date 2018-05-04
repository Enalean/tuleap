_pluginGit() {
    local -r git="/opt/rh/rh-git29/root/usr/bin/git"
    local -r gitolite="/usr/bin/gitolite"
    local -r git_group="gitolite"
    local -r git_user="gitolite"
    local -r git_home="/var/lib/gitolite"
    local -r ssh="/usr/bin/ssh"
    local -r sshkeygen="/usr/bin/ssh-keygen"
    local plugin_git_configured="false"

    _checkCommand ${git} ${ssh} ${sshkeygen}

    if [ ! -d "${tuleap_data}/gitolite/admin" ]; then

        ${install} --directory \
                   --group=${tuleap_unix_user} \
                   --owner=${tuleap_unix_user} \
                   --mode=750 \
                   ${tuleap_data}/gitolite/admin
        plugin_git_configured="true"
    fi


    if [ ! -L "${git_home}/repositories" ]; then
        ${rm} --force --recursive ${git_home}/repositories
        ${ln} --symbolic ${tuleap_data}/gitolite/repositories \
            ${git_home}/repositories
        plugin_git_configured="true"
    fi

    if [ -d "${git_home}/.gitolite" ]; then
        ${chmod} 750 ${git_home}/.gitolite
        ${chmod} 750 ${git_home}/.gitolite/*
    fi

    if [ ! -d "${tuleap_data}/.ssh" ]; then
        ${install} --directory \
                   --group=${tuleap_unix_user} \
                   --owner=${tuleap_unix_user} \
                   --mode=750 ${tuleap_data}/.ssh
        plugin_git_configured="true"
    fi

    if [ ! -f "${tuleap_data}/.ssh/id_rsa_gl-adm" ]; then
        ${sshkeygen} -q -t rsa -f ${tuleap_data}/.ssh/id_rsa_gl-adm \
            -N "" -C "Tuleap / gitolite admin key"
        ${chown} ${tuleap_unix_user}. \
            ${tuleap_data}/.ssh/id_rsa_gl-{adm,adm.pub}
        plugin_git_configured="true"
    fi

    if [ ! -f ${tuleap_data}/.ssh/config ] || \
        ${grep} --quiet "/home/codendiadm" "${tuleap_data}/.ssh/config"; then
        ${awk} '{ gsub("/home/codendiadm", "'"${tuleap_data}"'"); print }' \
            "${tuleap_src_plugins}/git/etc/ssh.config.dist" >> \
            "${tuleap_data}/.ssh/config"
        ${chown} ${tuleap_unix_user}. "${tuleap_data}/.ssh/config"
        plugin_git_configured="true"
    fi

    if ! $(_serviceIsActive sshd); then
        _errorMessage "Start sshd service"
        exit 1
    fi

    if ! ${su} --command "${ssh} -q ${git_user}@gl-adm info" \
        --login ${tuleap_unix_user} 2>&1 >/dev/null; then
        ${cp} --force "${tuleap_data}/.ssh/id_rsa_gl-adm.pub" /tmp
        ${su} --command "${gitolite} setup --pubkey /tmp/id_rsa_gl-adm.pub" \
        --login ${git_user}
    fi

    if [ -f /tmp/id_rsa_gl-adm.pub ]; then
        ${rm} --force /tmp/id_rsa_gl-adm.pub
    fi

    for user in ${git_user} ${tuleap_unix_user}; do
        if [ "$(${su} --command "${git} config user.name" --login ${user})" != "${user}" ]; then
            ${su} --command \
                "${git} config --global user.name ${user}" --login ${user}
            plugin_git_configured="true"
        fi

        if [ "$(${su} --command "${git} config user.email" --login ${user})" != "${user}@localhost" ]; then
            ${su} --command \
                "${git} config --global user.email ${user}@localhost" \
                --login ${user}
                plugin_git_configured="true"
        fi
    done

    if [ ! -d "${tuleap_data}/gitolite/admin/.git" ]; then
        ${su} --command \
            "${git} clone ${git_user}@gl-adm:gitolite-admin \
            ${tuleap_data}/gitolite/admin" --login ${tuleap_unix_user}
        plugin_git_configured="true"
    fi

    if [ ! -f "${tuleap_data}/gitolite/projects.list" ]; then
        ${touch} ${tuleap_data}/gitolite/projects.list
        ${chown} ${tuleap_unix_user}. ${tuleap_data}/gitolite/projects.list
        plugin_git_configured="true"
    fi

    ${awk} '{ gsub("# GROUPLIST_PGM", "GROUPLIST_PGM");
              gsub(""'"ssh-authkeys"'"","#"'"ssh-authkeys"'",");
              gsub("#0,#0,#0,7", "0007"); print}' \
            ${tuleap_src_plugins}/git/etc/gitolite3.rc.dist > \
            ${git_home}/.gitolite.rc
    ${chown} ${git_user}:${git_group} ${git_home}/.gitolite.rc
    ${chmod} 640 ${git_home}/.gitolite.rc

    if [ ! -f "${git_home}/.profile" ]; then
        ${printf} "source /opt/rh/rh-git29/enable" > ${git_home}/.profile
        ${chown} ${git_user}:${git_group} ${git_home}/.profile
        plugin_git_configured="true"
    fi

    if [ ! -f "${tuleap_data}/gitolite/admin/conf/gitolite.conf" ]; then
        ${install} --group=${tuleap_unix_user} \
                   --owner=${tuleap_unix_user} \
                   --mode=644 \
                   "${tuleap_src_plugins}/git/etc/gitolite.conf.dist" \
                   "${tuleap_data}/gitolite/admin/conf/gitolite.conf"
        plugin_git_configured="true"
    fi

    if ! ${su} --command '/opt/rh/rh-git29/root/usr/bin/git \
        --git-dir="/var/lib/tuleap/gitolite/admin/.git"  \
        cat-file -e origin/master:conf/gitolite.conf' \
        --login ${tuleap_unix_user}; then
        ${su} --command \
            "cd ${tuleap_data}/gitolite/admin && \
            ${git} add conf/gitolite.conf && \
            ${git} commit --message='Remove testing' && \
            ${git} push origin master" --login ${tuleap_unix_user}
        plugin_git_configured="true"
    fi

    if [ -d "${tuleap_data}/gitolite/repositories/testing.git" ]; then
        ${rm} --force --recursive \
            "${tuleap_data}/gitolite/repositories/testing.git"
        plugin_git_configured="true"
    fi

    if [ ! -f "${git_home}/.gitolite/hooks/common/post-receive-gitolite" ]; then
        ${install} --group=${git_group} \
                   --owner=${git_user} \
                   --mode=755 \
                   ${tuleap_src_plugins}/git/hooks/post-receive-gitolite \
                   ${git_home}/.gitolite/hooks/common/post-receive-gitolite
        plugin_git_configured="true"
    fi

    if [ -f "/usr/share/gitolite/hooks/common/post-receive" ]; then
        ${install} --group=${git_group} \
                   --owner=${git_user} \
                   --mode=755 \
                   ${tuleap_src_plugins}/git/hooks/post-receive-gitolite \
                   /usr/share/gitolite/hooks/common/post-receivea
        plugin_git_configured="true"
    fi

    if ! $(_serviceIsEnabled tuleap-process-system-events-git.timer); then
        _serviceEnable "tuleap-process-system-events-git.timer"
        plugin_git_configured="true"
    fi

    if ! $(_serviceIsActive tuleap-process-system-events-git.timer); then
        _serviceStart "tuleap-process-system-events-git.timer"
        plugin_git_configured="true"
    fi

    if [ ${plugin_git_configured} = "true" ]; then
        _infoMessage "Plugin Git is configured"
        _serviceRestart "tuleap.service"
    else
        _infoMessage "Plugin Git is already configured"
    fi
}
