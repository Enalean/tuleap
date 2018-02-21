_phpPasswordHasher() {
    # ${1}: password

    "${php}" "${tools_dir}/utils/password_hasher.php" -p "${1}"
}

_phpActivePlugin() {
    # ${1}: plugin name
    # ${2}: user name

    "${su}" -c "${php_launcher} ${tools_dir}/utils/admin/activate_plugin.php ${1}" \
        -l ${2}
}

_phpForgeupgrade() {
    # ${1}: commands

    _infoMessage "Register buckets in forgeupgrade"
    "${php}" "${forgeupgrade_dir}/forgeupgrade.php" \
        --config="${forgeupgrade_conf}" "${1}" 2> >(_logCatcher)
}
