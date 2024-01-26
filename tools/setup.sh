#!/usr/bin/env bash
#
# Copyright (c) Enalean, 2018 - Present. All rights reserved
#
# This file is a part of Tuleap.
#
# Tuleap is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Tuleap is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Tuleap. If not, see <http://www.gnu.org/licenses/
#
###############################################################################
set -o errexit
set -o nounset
set -o pipefail

declare -r tools_dir="$(/usr/bin/dirname "${BASH_SOURCE[0]}")"
declare -r include="${tools_dir}/setup/el7/include"

. ${include}/define.sh
. ${include}/messages.sh
. ${include}/check.sh
. ${include}/setup.sh
. ${include}/options.sh
. ${include}/helper.sh
. ${include}/logger.sh
. ${include}/plugins.sh

# Main
###############################################################################
if [[ -z "${@}" ]]; then
    _usageSetup
fi

_checkLogFile
_optionsSelected "${@}"
${tuleapcfg} systemctl mask "php82-php-fpm.service"
_checkIfTuleapInstalled

if [ ${tuleap_installed:-false} = "false" ] || \
    [ ${reinstall:-false} = "true" ]; then
    _checkMandatoryOptions "${@}"
    _infoMessage "Start Tuleap installation"
    _infoMessage "All credentials are saved into /root/.tuleap_passwd"
    _checkOsVersion
    _infoMessage "Checking all command line tools"
    _checkCommand
    _checkSeLinux
    _optionMessages "${@}"
    _checkFilePassword

    if [ "${TULEAP_INSTALL_SKIP_DB:-false}" = "false" ]; then
        admin_password="$(_setupRandomPassword)"
        sys_db_password="$(_setupRandomPassword)"

        _logPassword "Site admin password (${project_admin}): ${admin_password}"

        ${tuleapcfg} setup:mysql-init \
            --host="${mysql_server}" \
            --admin-user="${mysql_user}" \
            --admin-password="${mysql_password:-}" \
            --db-name="${sys_db_name}" \
            --app-password="${sys_db_password}" \
            --tuleap-fqdn="${server_name}" \
            --site-admin-password="${admin_password}" \
            --log-password=${password_file}
    fi

    ${tuleapcfg} setup:tuleap --force --tuleap-fqdn="${server_name}"
fi

if [ ${configure:-false} = "true" ]; then
    ${tuleapcfg} configure apache
    _checkInstalledPlugins
    _checkPluginsConfiguration
    if ${printf} '%s' ${plugins_configured[@]:-false} | \
        ${grep} --quiet "true"; then

        ${tuleapcfg} site-deploy
        ${tuleapcfg} systemctl reload "nginx"
        ${tuleapcfg} systemctl restart "tuleap.service"
    fi
fi

for pwd in mysql_password dbpasswd admin_password; do
    unset ${pwd}
done
