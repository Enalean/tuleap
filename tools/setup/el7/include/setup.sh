_setupDatabase() {
    if [ "${4}" = "true" ]; then
        _warningMessage "Database \033[1m${3}\033[0m already exists"

        if [ ${assumeyes} = "false" ]; then
            _questionMessage "Do you want to dump/drop database? [y/N] "
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

            _infoMessage \
                "Dump \033[1m${3}\033[0m database to /tmp/${3}.$(${date} \
                +%Y-%m-%d_%H-%M-%S).sql.gz"

            ${mysqldump} --user="${1}" \
                         --password="${2}" \
                         ${3} 2> >(_logCatcher) | ${gzip} > \
                         /tmp/${3}.$(${date} +%Y-%m-%d_%H-%M-%S).sql.gz

            _infoMessage "Drop \033[1m${3}\033[0m database"
            _mysqlConnect ${1} ${2} "$(_sqlDropDb ${3})" 2> >(_logCatcher)
        fi
    fi

    _infoMessage "Creating \033[1m${3}\033[0m database"
    _mysqlConnect ${1} ${2} "$(_sqlCreateDb ${3})" 2> >(_logCatcher)

}

_setupMysqlPrivileges() {
    _mysqlConnect ${1} ${2} "$(_sqlAllPrivileges ${project_admin} \
        ${web_server_ip:-localhost} ${admin_password})" 2> >(_logCatcher)

}

_setupRandomPassword() {
    < ${urandom} ${tr} -dc "@*?!+_a-zA-Z0-9" | ${head} -c${1:-16}
    ${printf} ""
}

_setupMysqlPassword() {
   ${mysqladmin} --user="${1}" password "${2}" 2> >(_logCatcher)
}
