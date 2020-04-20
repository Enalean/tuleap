_optionsSelected() {
    local -a longOptions=('server-name:,' 'mysql-server:,'
                          'mysql-port:,' 'mysql-user:,' 'mysql-password:,'
                          'debug,' 'disable-check-server-name,'
                          'disable-auto-passwd,' 'disable-mysql-configuration,'
                          'reinstall', 'configure', 'help,' 'assumeyes,')
    local options=$(${getopt} --options hydcr --longoptions \
                  $(${printf} "%s" ${longOptions[@]}) -- ${@})

    eval set -- "${options}"

    while true; do
        case "${1}" in
            --server-name)
                _checkArgument "${1}" "${2}"
                server_name=${2}
                shift 2
                ;;
            --mysql-server)
                _checkArgument "${1}" "${2}"
                mysql_server=${2}
                shift 2
                ;;
            --mysql-port)
                _checkArgument "${1}" "${2}"
                mysql_port=${2}
                shift 2
                ;;
            --mysql-user)
                _checkArgument "${1}" "${2}"
                mysql_user=${2}
                shift 2
                ;;
            --mysql-password)
                _checkArgument "${1}" "${2}"
                mysql_password=${2}
                shift 2
                ;;
            --long-org-name)
                _checkArgument "${1}" "${2}"
                long_org_name=${2}
                shift 2
                ;;
            --org-name)
                _checkArgument "${1}" "${2}"
                org_name=${2}
                shift 2
                ;;
            --disable-auto-passwd)
                disable_auto_password="true"
                shift 1
                ;;
            --disable-mysql-conf)
                disable_mysql_conf="true"
                shift 1
                ;;
            --disable-check-server-name)
                disable_check_server_name="true"
                shift 1
                ;;
            -r | --reinstall)
                reinstall="true"
                shift 1
                ;;
            -c | --configure)
                configure="true"
                shift 1
                ;;
            -d | --debug)
                set -o xtrace
                shift 1
                ;;
            -h | --help)
                _usageSetup
                shift 1
                ;;
            -y | --assumeyes)
                assumeyes="true"
                shift 1
                ;;
            --)
                shift
                break
                ;;
        esac
    done
}
