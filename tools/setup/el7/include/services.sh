_serviceEnable() {
    # ${@}: services name

    ${systemctl} --quiet enable ${@}
}

_serviceIsActive() {
    # ${@}: services name

    ${systemctl} --quiet is-active ${@}
}

_serviceIsEnabled() {
    # ${@}: services name

    ${systemctl} --quiet is-enabled ${@}
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
