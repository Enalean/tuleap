_checkArgument() {
    # ${1}: option name
    # ${2}: option value

    if [ -z "${2}" ]; then
        _errorMessage "${1//--} is not defined"
        exit 1
    fi
}

_checkCommand() {
    for c in ${@}; do
        if [ ! -f ${c} ]; then
            _errorMessage "${c}: command not found"
            exit 1
        fi
    done
}

_checkFilePassword() {
    if [ -f ${password_file} ]; then
        ${mv} ${password_file} \
            ${password_file}.$(${date} +%Y-%m-%d_%H-%M-%S).bak
    fi
}

_checkIfTuleapInstalled() {
    if [ -f ${tuleap_conf}/${local_inc} ] && \
        [ -f ${tuleap_conf}/${database_inc} ]; then
        tuleap_installed="true"
    fi
}

_checkInstalledPlugins() {
    installed_plugins=($(rpm -aq tuleap-plugin-\* | \
        ${awk} -F"-" '!/pluginsadministration/ {print $3}'))
}

_checkLogFile() {
    if [ -f ${tuleap_log} ]; then
        ${mv} ${tuleap_log} ${tuleap_log}.$(${date} +%Y-%m-%d_%H-%M-%S)
    fi
}

_checkMandatoryOptions() {
    if [ "${mysql_password:-NULL}" = "NULL" -a "${mysql_server:-localhost}" = "localhost" ] || \
        [ "${mysql_password:-NULL}" = "NULL" -a "${mysql_server:-127.0.0.1}" = "127.0.0.1" ]; then
        local -a mandatoryOptions=('\--server-name=' '\--mysql-server=')
    else
        local -a mandatoryOptions=('\--server-name=' '\--mysql-server='
                               '\--mysql-password=')
    fi

    for option in ${mandatoryOptions[@]}; do
        if ! ${printf} "%s" ${@} | ${grep} --silent ${option}; then
            _errorMessage "The '${option//\\}' option is mandatory"
            exit 1
        fi
    done

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

_checkPluginsConfiguration() {
    for plugin in ${installed_plugins[@]}; do
        case ${plugin} in
            git) _pluginGit;;
            svn) _pluginSVN;;
            mediawiki) _pluginMediawiki;;
        esac
    done
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

_checkWebServerIp() {
    if [ "${mysql_server,,}" != "localhost" -a "${mysql_server}" != "127.0.0.1" -a "${web_server_ip:-NULL}" = "NULL" ]; then
       _errorMessage "You are running Tuleap with a remote mysql server"
       _errorMessage "You have to define the web server IP with --web-server-ip option"
       exit 1
    fi
}
