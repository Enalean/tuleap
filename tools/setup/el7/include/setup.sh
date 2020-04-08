_setupForgeupgrade() {
    # Only needed for short term tests as futur test containers will have this created out of rpms
    if [ ! -d "${tuleap_dir}/forgeupgrade" ]; then
        ${install} --group=${tuleap_unix_user} --owner=${tuleap_unix_user} --mode=0750 -d "${tuleap_dir}/forgeupgrade"
    fi
    ${install} --group=${tuleap_unix_user} --owner=${tuleap_unix_user} \
        --mode=0640 "${forgeupgrade_dist}" "${forgeupgrade_conf}"
}

_setupMysqlPassword() {
    # ${1}: mysql user
    # ${2}: mysql password

    ${mysqladmin} --user="${1}" password "${2}" 2> >(_logCatcher)
}

_setupRandomPassword() {
    (${tr} -dc 'a-zA-Z0-9' < ${urandom} | ${head} -c32) 2>/dev/null
    ${printf} ""
}

_setupLocalInc() {
    ${awk} '{ gsub("%sys_default_domain%","'"${server_name}"'");
              gsub("%sys_org_name%","'"${org_name}"'");
              gsub("%sys_long_org_name%","'"${long_org_name}"'");
              gsub("%sys_fullname%","'"${server_name}"'");
              gsub("codendiadm","'"${tuleap_unix_user}"'");
              gsub("sys_mail_secure_mode = 0","sys_mail_secure_mode = 1");
              gsub("sys_disable_subdomains = 0","sys_disable_subdomains = 1");
              gsub("sys_create_project_in_one_step = 0",
                  "sys_create_project_in_one_step = 1");
              gsub("sys_plugins_editable_configuration = 1",
                  "sys_plugins_editable_configuration = 0");
              gsub("/home/users","");
              gsub("/home/groups","");
              print }' "${install_dir}/src/etc/${local_inc}.dist" \
                  > "${tuleap_conf}/${local_inc}"
}
