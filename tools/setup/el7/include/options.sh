_optionsSelected() {
    local -a longOptions=('server-name:,' 'mysql-server:,' 'mysql-port:,'
                          'mysql-user:,' 'mysql-password:,' 'debug,'
                          'disable-check-server-name,' 'disable-auto-passwd,'
                          'disable-mysql-configuration,' 'help,' 'assumeyes,'
                         )
    local options=$(${getopt} --options hyd --longoptions \
                  $(${printf} "%s" ${longOptions[@]}) -- ${@})

    eval set -- "${options}"

    while true; do
        case "${1}" in
            --server-name)
                server_name=${2}
                shift 2
                ;;
            --web-server-ip)
                web_server_ip=${2}
                shift 2
                ;;
            --mysql-server)
                mysql_server=${2}
                shift 2
                ;;
            --mysql-port)
                mysql_port=${2}
                shift 2
                ;;
            --mysql-user)
                mysql_user=${2}
                shift 2
                ;;
            --mysql-password)
                mysql_password=${2}
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
