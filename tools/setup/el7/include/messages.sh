_errorMessage() {
    # ${1}: message to sent

    ${printf} "\033[31m * \033[0m${1}\n"
    _logMessages "Error: ${1}"

}

_infoMessage() {
    # ${1}: message to sent

    ${printf} "\033[32m * \033[0m${1}\n"
    _logMessages "Info: ${1}"
}

_optionMessages() {
    local -a selectedOptions=("${@}")

    _warningMessage "Options selected:  ${selectedOptions[*]/--mysql-password=*/--mysql-password=****}"

    if [ ${assumeyes} = "false" ]; then
        _questionMessage "Do you want to continue? [y/N] "
        read answer
        local answer=${answer:-"n"}
    else
        local answer="y"
    fi

    if [ ${answer,,} = "n" ]; then
        _errorMessage "User exit"
        exit 1
    fi
}

_questionMessage() {
    # ${1}: message to sent

    ${printf} "\033[34m * \033[0m${1}"
}

_warningMessage() {
    # ${1}: message to sent

    echo " * ${1}"
    _logMessages "Warning: ${1}"
}

_endMessage() {
    _infoMessage "Successful installation!"
}
