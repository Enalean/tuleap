_serviceEnable() {
    # ${@}: services name

    ${systemctl} --quiet enable ${@}
}

_serviceMask() {
    # ${@}: services name

    ${systemctl} --quiet mask ${@}
}

_serviceStart() {
    # ${@}: services name

    ${systemctl} start ${@}
}

_serviceReload() {
    # ${@}: services name

    ${systemctl} reload ${@}
}

_serviceRestart() {
    # ${@}: services name

    ${systemctl} restart ${@}
}
