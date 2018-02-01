_checkCommand() {
    for c in ${cmd[@]}; do
        if [ ! -f ${c} ]; then
            _errorMessage "${c}: command not found"
            exit 1
        fi
    done
}

_checkDatabase() {
    # ${1}: mysql user
    # ${2}: mysql password
    # ${3}: database

    if _mysqlExecute ${1} ${2} "$(_sqlShowDb)" | ${grep} --silent ${3}; then
        db_exist="true"
    fi
}

_checkFilePassword() {
    if [ -f ${password_file} ]; then
        ${mv} ${password_file} \
            ${password_file}.$(${date} +%Y-%m-%d_%H-%M-%S).bak
    fi
}

_checkLogFile() {
    if [ -f ${tuleap_log} ]; then
        ${mv} ${tuleap_log} ${tuleap_log}.$(${date} +%Y-%m-%d_%H-%M-%S)
    fi
}

_checkMandatoryOptions() {
    local -a mandatoryOptions=('\--server-name=' '\--mysql-server=')

    for option in ${mandatoryOptions[@]}; do
        if ! ${printf} "%s" ${@} | ${grep} --silent ${option}; then
            _errorMessage "The '${option//\\}' option is mandatory"
            exit 1
        fi
    done

}

_checkMysqlStatus() {
    # ${1}: mysql user
    # ${2}: mysql password

    if ! _mysqlExecute ${1} ${2} ";"; then
        _errorMessage "MySQL server is not accessible or bad password"
        exit 1
    else
        _infoMessage "MySQL server is accessible"
    fi
}

_checkMysqlMode() {
    # ${1}: mysql user
    # ${2}: mysql password

    local sql_mode=$(_mysqlExecute ${1} ${2} "$(_sqlShowMode)")

    if [[ ${sql_mode#* } =~ STRICT_.*_TABLES ]]; then
        _errorMessage "MySQL: unsupported sql_mode: ${sql_mode//sql_mode/}"
        _errorMessage "Please remove STRICT_ALL_TABLES or STRICT_TRANS_TABLES from my.cnf"
        exit 1
    else
        _infoMessage "Sql_mode : ${sql_mode//sql_mode/}"
    fi

}

_checkOsVersion() {
    if [ -e "${rh_release}" ]; then

        if ! ${grep} --silent "Red Hat.*7\|CentOS.*7" ${rh_release}; then
            _errorMessage "Sorry, ${script_name} is only for RedHat/CentOS 7"
            exit 1
        fi

        _infoMessage "$(${cat} ${rh_release})"

    else
        _errorMessage "Sorry, Tuleap is running only on RedHat/CentOS"
        exit 1
    fi
}

_checkSeLinux() {
    if [ $("${getenforce}") = "Enforcing" ]; then
        _warningMessage "Your SELinux is in enforcing mode"
        _warningMessage "Set your SELinux in permissive mode"
        _warningMessage \
            "To achieve this, use setenforce 0 to enter permissive mode"
        _warningMessage \
            "Edit ${sefile} file for a permanent change"
        _errorMessage \
            "Tuleap does not currently support SELinux in enforcing mode"
        exit 1
    else
        _infoMessage "SELinux in $(${getenforce}) mode"
    fi
}
