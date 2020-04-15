#!/bin/bash
#
# Copyright (c) Enalean, 2018. All rights reserved
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
. ${include}/php.sh
. ${include}/core.sh
. ${include}/plugins.sh

# Main
###############################################################################
if [[ -z "${@}" ]]; then
    _usageSetup
fi

_checkLogFile
_optionsSelected "${@}"
${tuleapcfg} systemctl mask "php73-php-fpm.service"
_checkIfTuleapInstalled

mysql_app_user_grant="${sys_db_user}@localhost"
if [ "${web_server_ip:-NULL}" != "NULL" ]; then
    mysql_app_user_grant="${sys_db_user}@${web_server_ip}"
fi

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
    _checkWebServerIp
    _checkFilePassword

    if [ "${mysql_password:-NULL}" = "NULL" -a "${mysql_server,,}" = "localhost" ] || \
        [ "${mysql_password:-NULL}" = "NULL" -a "${mysql_server}" = "127.0.0.1" ]; then

        if ! ${mysql} ${my_opt} --host=${mysql_server} \
            --user=${mysql_user} --execute=";" 2> >(_logCatcher); then
            _errorMessage "Your database already have a password"
            _errorMessage "You need to use the '--mysql-password' option"
            exit 1
        fi

        _infoMessage "Generate MySQL password"
        mysql_password="$(_setupRandomPassword)"
        _infoMessage "Set MySQL password for ${mysql_user}"
        _setupMysqlPassword "${mysql_user}" ${mysql_password}
        _logPassword "MySQL system user password (${mysql_user}): ${mysql_password}"
    fi

    admin_password="$(_setupRandomPassword)"
    sys_db_password="$(_setupRandomPassword)"
    _logPassword "MySQL system user password (${sys_db_user}): ${sys_db_password}"
    _logPassword "Site admin password (${project_admin}): ${admin_password}"

    # Only needed for short term tests as futur test containers will have this created out of rpms
    if [ ! -d ${tuleap_conf} ]; then
        install -d -m 0750 -o root -g ${tuleap_unix_user} ${tuleap_dir}
        install -d -m 0750 -o ${tuleap_unix_user} -g ${tuleap_unix_user} ${tuleap_conf}
    fi

    ${tuleapcfg} setup:mysql-init \
        --host="${mysql_server}" \
        --admin-user="${mysql_user}" \
        --admin-password="${mysql_password}" \
        --db-name="${sys_db_name}" \
        --app-user="${mysql_app_user_grant}" \
        --app-password="${sys_db_password}"

    ${tuleapcfg} setup:mysql \
        --host="${mysql_server}" \
        --user="${sys_db_user}" \
        --dbname="${sys_db_name}" \
        --password="${sys_db_password}" \
        "${admin_password}" \
        "${server_name}"

    if [ -f "${tuleap_conf}/${local_inc}" ]; then
        _infoMessage "Saving ${local_inc} file"
        ${mv} "${tuleap_conf}/${local_inc}" \
            "${tuleap_conf}/${local_inc}.$(date +%Y-%m-%d_%H-%M-%S)"
    fi
    _setupLocalInc
    chown root:codendiadm "${tuleap_conf}/${local_inc}"
    chmod 00640 "${tuleap_conf}/${local_inc}"

    _setupForgeupgrade
    _phpActivePlugin "tracker" "${tuleap_unix_user}"
    _phpForgeupgrade "record-only"

    ${tuleapcfg} systemctl enable "${timers[@]}"
    ${tuleapcfg} systemctl start "${timers[@]}"

    ${tuleapcfg} site-deploy

    _phpConfigureModule "nginx,fpm"
    ${tuleapcfg} systemctl restart "nginx" "tuleap"
    ${tuleapcfg} systemctl enable "nginx"
    _endMessage
fi

if [ ${configure:-false} = "true" ]; then
    ${tuleapcfg} configure apache
    _configureMailman
    _checkInstalledPlugins
    _checkPluginsConfiguration
    _configureCVS
    if ${printf} '%s' ${plugins_configured[@]:-false} | \
        ${grep} --quiet "true"; then
        _phpConfigureModule "nginx"
        ${tuleapcfg} systemctl reload "nginx"
        ${tuleapcfg} systemctl restart "tuleap.service"
    fi
fi

for pwd in mysql_password dbpasswd admin_password; do
    unset ${pwd}
done
