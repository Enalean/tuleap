_mysqlConnect() {
    ${mysql} ${my_opt} --user="${1}" --password="${2}" --execute="${3}"
}
