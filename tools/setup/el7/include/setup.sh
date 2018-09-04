_setupDatabase() {
    # ${1}: mysql user
    # ${3}: mysql password
    # ${3}: database name
    # ${4}: the database is present (true or false)

    if [ "${4}" = "true" ]; then
        _warningMessage "Database \033[1m${3}\033[0m already exists"

        if [ ${assumeyes} = "false" ]; then
            _questionMessage "Do you want to dump and drop database? [y/N] "
            read answer
            local answer=${answer:-"n"}

            if [ ${answer} = "n" ]; then
                new_db="false"
                _errorMessage "User exit"
                exit 1
            fi

        else
            local answer="y"
        fi

        if [ ${answer,,} = "y" ]; then
            new_db="true"
            local date_dump=$(${date} +%Y-%m-%d_%H-%M-%S)

            _infoMessage \
                "Dump \033[1m${3}\033[0m database to ${tuleap_dump}/${3}.${date_dump}.sql.gz"

            _setupDirectory "root" "root" "700" "${tuleap_dump}"
            ${mysqldump} --host="${mysql_server:-localhost}" \
                         --user="${1}" \
                         --password="${2}" \
                         ${3} 2> >(_logCatcher) | ${gzip} > \
                         "${tuleap_dump}/${3}.${date_dump}.sql.gz"

            ${chmod} 400 "${tuleap_dump}/${3}.${date_dump}.sql.gz"
            _infoMessage "Drop \033[1m${3}\033[0m database"
            _mysqlExecute ${1} ${2} "$(_sqlDropDb ${3})"
        fi
    fi

    _infoMessage "Creating \033[1m${3}\033[0m database"
    _mysqlExecute ${1} ${2} "$(_sqlCreateDb ${3})"

}

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

_setupInitValues() {
    # ${1}: site admin password
    # ${2}: domain name

    ${awk} '{ gsub("SITEADMIN_PASSWORD","'"${1}"'");
              gsub("_DOMAIN_NAME_","'"${2}"'");
              print }' ${3}
}

_setupMysqlPassword() {
    # ${1}: mysql user
    # ${2}: mysql password

    ${mysqladmin} --user="${1}" password "${2}" 2> >(_logCatcher)
}

_setupMysqlPrivileges() {
    # ${1}: mysql user
    # ${2}: mysql password
    # ${3}: sys db password

    _mysqlExecute ${1} ${2} "$(_sqlAllPrivileges ${3} \
        ${web_server_ip:-localhost} ${4})"
}

_setupRandomPassword() {
    (${tr} -dc '@*?!+_a-zA-Z0-9' < ${urandom} | ${head} -c32) 2>/dev/null
    ${printf} ""
}

_setupSourceDb() {
    # ${1}: mysql user
    # ${2}: mysql password
    # ${3}: database name
    # ${4}: data sql

    _mysqlConnectDb ${1} ${2} ${3} < ${4}
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
