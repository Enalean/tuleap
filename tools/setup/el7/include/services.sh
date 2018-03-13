_serviceEnable() {
    # ${@}: services name

    ${systemctl} enable ${@}
}

_serviceMask() {
    # ${@}: services name

    ${systemctl} mask ${@}
}

_serviceStart() {
    # $@1}: services name

    ${systemctl} start ${@}
}
