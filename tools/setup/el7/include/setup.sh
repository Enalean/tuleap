_setupDatabaseInc() {
    ${awk} '{ gsub("%sys_dbpasswd%", "'"${sys_db_password}"'");
              gsub("%sys_dbuser%", "'"${sys_db_user}"'");
              gsub("%sys_dbname%", "'"${sys_db_name}"'");
              gsub("localhost", "'"${mysql_server}"'");
              print }' "${install_dir}/src/etc/${database_inc}.dist" \
                  > "${tuleap_conf}/${database_inc}"
}

_setupDirectory() {
    # ${1}: group ownership
    # ${2}: ownership
    # ${3}: permission mode
    # ${4}: directory

    ${install} --group=${1} --owner=${2} --mode=${3} --directory ${4}
}

_setupForgeupgrade() {
    _setupDirectory "${tuleap_unix_user}" "${tuleap_unix_user}" "0755" \
        "${tuleap_dir}/forgeupgrade"
    ${install} --group=${tuleap_unix_user} --owner=${tuleap_unix_user} \
        --mode=0644 "${forgeupgrade_dist}" "${forgeupgrade_conf}"
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
