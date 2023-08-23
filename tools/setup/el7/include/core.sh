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

function _configureNSSMySQL() {
    local has_libnss_mysql_been_configured="false"
    local libnss_conf="/etc/libnss-mysql.cfg"
    local libnss_conf_root="/etc/libnss-mysql-root.cfg"

    local dbauthuser_username=$(/usr/bin/tuleap config-get sys_dbauth_user)
    local dbauthuser_password=$(/usr/bin/tuleap config-get --reveal-secret sys_dbauth_passwd)

    if ! ${grep} --quiet "$dbauthuser_password" "$libnss_conf"; then
        ${cp} -f "$install_dir/src/etc/libnss-mysql.cfg.dist" "$libnss_conf"

        local mysql_host=$(${awk} --field-separator="'" '/^\$sys_dbhost/ {print $2}' ${tuleap_conf}/database.inc)
        local mysql_dbname=$(${awk} --field-separator="'" '/^\$sys_dbname/ {print $2}' ${tuleap_conf}/database.inc)
        ${sed} --in-place "s@%sys_dbhost%@$mysql_host@" "$libnss_conf"
        ${sed} --in-place "s@%sys_dbname%@$mysql_dbname@" "$libnss_conf"
        ${sed} --in-place "s@%sys_dbhost%@$mysql_host@" "$libnss_conf"
        ${sed} --in-place "s@%sys_dbauth_passwd%@$dbauthuser_password@" "$libnss_conf"
        has_libnss_mysql_been_configured="true"
    fi

    if ! ${grep} --quiet "$dbauthuser_password" "$libnss_conf_root"; then
        ${cp} -f "$install_dir/src/etc/libnss-mysql-root.cfg.dist" "$libnss_conf_root"

        ${sed} --in-place "s@%sys_dbauth_passwd%@$dbauthuser_password@" "$libnss_conf_root"
        has_libnss_mysql_been_configured="true"
    fi

    if ! ${grep} ^passwd  /etc/nsswitch.conf | ${grep} --quiet mysql; then
        ${sed} --in-place "/^passwd:/ s/$/ mysql/g" /etc/nsswitch.conf
        has_libnss_mysql_been_configured="true"
    fi


    if ! ${grep} ^shadow  /etc/nsswitch.conf | ${grep} --quiet mysql; then
        ${sed} --in-place "/^shadow:/ s/$/ mysql/g" /etc/nsswitch.conf
        has_libnss_mysql_been_configured="true"
    fi


    if ! ${grep} ^group  /etc/nsswitch.conf | ${grep} --quiet mysql; then
        ${sed} --in-place "/^group:/ s/$/ mysql/g" /etc/nsswitch.conf
        has_libnss_mysql_been_configured="true"
    fi

    if [ ${has_libnss_mysql_been_configured} = "true" ]; then
        _infoMessage "LibNSS MySQL has been configured"
    fi
}

function _configureCVS() {
    if [ ! -f "/usr/lib/systemd/system/cvs.socket" ]; then
        return
    fi
    local has_cvs_been_configured="false"

    if [ ! -L "/cvsroot" ]; then
        ${ln} --symbolic ${tuleap_data}/cvsroot /cvsroot
        has_cvs_been_configured="true"
    fi

    _configureNSSMySQL

    if ! $(${tuleapcfg} systemctl is-enabled "cvs.socket"); then
        ${tuleapcfg} systemctl enable "cvs.socket"
        has_cvs_been_configured="true"
    fi

    if [ ${has_cvs_been_configured} = "true" ]; then
        ${tuleapcfg} systemctl restart "cvs.socket"
        _infoMessage "CVS has been configured"
    else
        _infoMessage "CVS is already configured"
    fi
}
