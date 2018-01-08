_logPassword() {
    ${printf} "%s\n" "${@}" >> ${password_file}
}

_logCatcher() {
    local timeout=0.1
    while read -t ${timeout} stdin; do
        datelog=$(${date} --rfc-3339=seconds)
        ${printf} "%s" "[${datelog}] ${stdin}\n" >> ${tuleap_log}
    done
}

_logMessages() {
    local datelog=$(${date} --rfc-3339=seconds)
    ${printf} "[${datelog}] ${@}\n" >> ${tuleap_log}
}

