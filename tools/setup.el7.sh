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
. ${include}/mysqlcli.sh
. ${include}/sql.sh

# Main
###############################################################################
if [[ -z "${@}" ]]; then
    _usageSetup
fi

_checkLogFile
_optionsSelected "${@}"
_checkMandatoryOptions "${@}"
_infoMessage "Start Tuleap installation"
_checkOsVersion
_infoMessage "Checking all command line tools"
_checkCommand
_checkSeLinux
_optionMessages "${@}"

if [[ ${mysql_password} = "NULL" ]]; then

    if ! ${mysql} ${my_opt} --user=${mysql_user} --execute=";" 2> >(_logCatcher); then
        _errorMessage "Your database already have a password"
        _errorMessage "You need to use the '--mysql-password' option"
        exit 1
    fi

    _infoMessage "Generate MySQL password"
    mysql_password=$(_setupRandomPassword)
    _infoMessage "Set MySQL password for ${mysql_user}"
    _setupMysqlPassword "${mysql_user}" ${mysql_password}
    admin_password=$(_setupRandomPassword)
    _setupMysqlPrivileges "${mysql_user}" "${mysql_password}"
else
    admin_password=$(_setupRandomPassword)
    _checkMysqlStatus "${mysql_user}" "${mysql_password}"
fi

_checkFilePassword
_logPassword "MySQL user password (${mysql_user}): ${mysql_password}"
_logPassword "System user password (${project_admin}): ${admin_password}"
_checkMysqlMode "${mysql_user}" "${mysql_password}"
_checkDatabase "${mysql_user}" "${mysql_password}" "${db_name}"
_setupDatabase "${mysql_user}" "${mysql_password}" "${db_name}" "${db_exist}"
_infoMessage "Populating the Tuleap database..."
_setupSourceDb "${mysql_user}" "${mysql_password}" "${db_name}" \
    "${sql_structure}"

_setupInitValues $(_phpPasswordHasher "${admin_password}") "${server_name}" \
    "${sql_init}" | \
    $(_mysqlConnectDb "${mysql_user}" "${mysql_password}" "${db_name}")

_endMessage
