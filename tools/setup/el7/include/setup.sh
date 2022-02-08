_setupMysqlPassword() {
    # ${1}: mysql user
    # ${2}: mysql password

    ${mysqladmin} --user="${1}" password "${2}" 2> >(_logCatcher)
}

_setupRandomPassword() {
    (${tr} -dc 'a-zA-Z0-9' < ${urandom} | ${head} -c32) 2>/dev/null
    ${printf} ""
}
