_serviceEnable() {
    # ${@}: services name

    ${systemctl} enable ${@}
}

_serviceMask() {
    # ${@}: services name

    ${systemctl} mask ${@}
}

_serviceStart() {
    # ${@}: services name

    ${systemctl} start ${@}
}

_serviceRestart() {
    # ${@}: services name

    ${systemctl} restart ${@}
}
