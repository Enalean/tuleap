_serviceEnable() {
    # ${1}: service name

    ${systemctl} enable ${1}
}

_serviceMask() {
    # ${1}: service name

    ${systemctl} mask ${1}
}

_serviceStart() {
    # ${1}: service name

    ${systemctl} start ${1}
}
