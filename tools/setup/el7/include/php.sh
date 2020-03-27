_phpActivePlugin() {
    # ${1}: plugin name
    # ${2}: user name

    "${su}" -c "${php_launcher} ${tools_dir}/utils/admin/activate_plugin.php ${1}" \
        -l ${2} 2> >(_logCatcher)
}

_phpForgeupgrade() {
    # ${1}: commands

    _infoMessage "Register buckets in forgeupgrade"
    "${php}" "${forgeupgrade_dir}/forgeupgrade.php" \
        --config="${forgeupgrade_conf}" "${1}" 2> >(_logCatcher)
}

_phpConfigureModule() {
    # ${1}: module name

    ${install_dir}/tools/utils/php73/run.php --module="${1}"
}
