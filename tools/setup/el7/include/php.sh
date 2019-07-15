_phpPasswordHasher() {
    # ${1}: password

    "${php}" "${tools_dir}/utils/password_hasher.php" -p "${1}"
}

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

_phpImportTrackerTemplate() {
    for template in ${install_dir}/plugins/tracker/www/resources/templates/Tracker_*.xml; do
        "${php_launcher}" ${install_dir}/plugins/tracker/bin/import_tracker_xml_template.php ${template} \
            2> >(_logCatcher)
        echo "${install_dir}/plugins/tracker/bin/import_tracker_xml_template.php ${template}"
    done
}

_phpConfigureModule() {
    # ${1}: module name

    ${install_dir}/tools/utils/php73/run.php --module="${1}"
}
