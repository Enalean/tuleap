_mysqlConnectDb() {
    ${mysql} ${my_opt} --host="${mysql_server:-localhost}" --user="${1}" \
        --password="${2}" --database="${3}" 2> >(_logCatcher)
}

_mysqlExecute() {
    ${mysql} ${my_opt} --host="${mysql_server:-localhost}" --user="${1}" \
        --password="${2}" --execute="${3}" 2> >(_logCatcher)
}
