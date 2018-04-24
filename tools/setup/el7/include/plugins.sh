_pluginGit() {
    if [ ! -d "${tuleap_data}/gitolite/admin" ]; then
        local -r git="/opt/rh/rh-git29/root/usr/bin/git"
        local -r gitolite="/usr/bin/gitolite"
        local -r git_group="gitolite"
        local -r git_user="gitolite"
        local -r git_home="/var/lib/gitolite"
        local -r sshkeygen="/usr/bin/ssh-keygen"

        _checkCommand ${git} ${sshkeygen}
        _infoMessage "Plugin Git configuration in progress..."

        ${install} --directory \
                   --group=${tuleap_unix_user} \
                   --owner=${tuleap_unix_user} \
                   --mode=750 \
                   ${tuleap_data}/gitolite/admin

        if [ -d "${git_home}/repositories" ]; then
            ${rm} --force --recursive ${git_home}/repositories
        fi

        if [ ! -L "${git_home}/repositories" ]; then
            ${ln} --symbolic ${tuleap_data}/gitolite/repositories \
                ${git_home}/repositories
        fi

        if [ -d "${git_home}/.gitolite" ]; then
            ${chmod} 750 ${git_home}/.gitolite
            ${chmod} 750 ${git_home}/.gitolite/*
        fi

        if [ ! -f "${tuleap_data}/.ssh" ]; then
            ${install} --directory \
                       --group=${tuleap_unix_user} \
                       --owner=${tuleap_unix_user} \
                       --mode=750 ${tuleap_data}/.ssh
        fi

        if [ ! -f "${tuleap_data}/.ssh/id_rsa_gl-adm" ]; then
            ${sshkeygen} -q -t rsa -f ${tuleap_data}/.ssh/id_rsa_gl-adm \
                -N "" -C "Tuleap / gitolite admin key"
            ${chown} ${tuleap_unix_user}. \
                ${tuleap_data}/.ssh/id_rsa_gl-{adm,adm.pub}
        fi

        ${awk} '{ gsub("/home/codendiadm", "'"${tuleap_data}"'"); print }' \
            "${tuleap_src_plugins}/git/etc/ssh.config.dist" >> \
            "${tuleap_data}/.ssh/config"
        ${chown} ${tuleap_unix_user}. "${tuleap_data}/.ssh/config"

        for user in ${git_user} ${tuleap_unix_user}; do
            ${su} --command \
                "${git} config --global user.name ${user}" --login ${user}
            ${su} --command \
                "${git} config --global user.email ${user}@localhost" \
                --login ${user}
        done

        ${cp} --force "${tuleap_data}/.ssh/id_rsa_gl-adm.pub" /tmp
        ${su} --command \
            "${gitolite} setup --pubkey /tmp/id_rsa_gl-adm.pub" \
            --login ${git_user}
        if [ -f /tmp/id_rsa_gl-adm.pub ]; then
            ${rm} --force /tmp/id_rsa_gl-adm.pub
        fi
        ${su} --command \
            "${git} clone ${git_user}@gl-adm:gitolite-admin \
            ${tuleap_data}/gitolite/admin" --login ${tuleap_unix_user}

        if [ ! -f "${tuleap_data}/gitolite/projects.list" ]; then
            ${touch} ${tuleap_data}/gitolite/projects.list
            ${chown} ${tuleap_unix_user}. ${tuleap_data}/gitolite/projects.list
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
        fi

        ${install} --group=${tuleap_unix_user} \
                   --owner=${tuleap_unix_user} \
                   --mode=644 \
                   "${tuleap_src_plugins}/git/etc/gitolite.conf.dist" \
                   "${tuleap_data}/gitolite/admin/conf/gitolite.conf"

        ${su} --command \
            "cd ${tuleap_data}/gitolite/admin && \
            ${git} add conf/gitolite.conf && \
            ${git} commit --message='Remove testing' && \
            ${git} push origin master" --login ${tuleap_unix_user}
        ${rm} --force --recursive \
            "${tuleap_data}/gitolite/repositories/testing.git"

        if [ ! -f "${git_home}/.gitolite/hooks/common/post-receive-gitolite" ]; then
            ${install} --group=${git_group} \
                       --owner=${git_user} \
                       --mode=755 \
                       ${tuleap_src_plugins}/git/hooks/post-receive-gitolite \
                       ${git_home}/.gitolite/hooks/common/post-receive-gitolite
        fi

        if [ -f "/usr/share/gitolite/hooks/common/post-receive" ]; then
            ${install} --group=${git_group} \
                       --owner=${git_user} \
                       --mode=755 \
                       ${tuleap_src_plugins}/git/hooks/post-receive-gitolite \
                       /usr/share/gitolite/hooks/common/post-receivea
        fi

        _serviceEnable "tuleap-process-system-events-git.timer"
        _serviceStart "tuleap-process-system-events-git.timer"
        _serviceRestart "tuleap.service"
    else
        _infoMessage "Plugin Git is already configured"
    fi
}
