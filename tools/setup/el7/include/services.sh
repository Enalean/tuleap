_serviceEnable() {
    ${systemctl} enable ${1}
}

_serviceMask() {
    ${systemctl} mask ${1}
}

_serviceStart() {
    ${systemctl} start ${1}
}
