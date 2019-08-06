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

function _configureMailman() {
    if [ ! -f "/usr/lib/systemd/system/mailman-tuleap.service" ]; then
        return
    fi

    local mailman_conf="/etc/mailman/mm_cfg.py"
    local server_name=$(${awk} --field-separator="'" \
            '/^\$sys_default_domain/ {print $2}' ${tuleap_conf}/local.inc)

    local has_mailman_been_configured="false"

    if ${grep} --quiet "getfqdn()" "$mailman_conf"; then
        ${sed} --in-place --follow-symlinks "s@getfqdn()@'$server_name'@g" "$mailman_conf"
        has_mailman_been_configured="true"
    fi

    if ! ${grep} --quiet "IMAGE_LOGOS" "$mailman_conf"; then
        echo "IMAGE_LOGOS = 0" >> "$mailman_conf"
        has_mailman_been_configured="true"
    fi

    if ! ${grep} --quiet "DEFAULT_URL_PATTERN" "$mailman_conf"; then
        echo "DEFAULT_URL_PATTERN = 'https://%s/mailman/'" >> "$mailman_conf"
        has_mailman_been_configured="true"
    fi

    if ! ${grep} --quiet "PUBLIC_ARCHIVE_URL" "$mailman_conf"; then
        echo "PUBLIC_ARCHIVE_URL = 'https://%(hostname)s/pipermail/%(listname)s'" >> "$mailman_conf"
        has_mailman_been_configured="true"
    fi

    if ! /usr/lib/mailman/bin/list_lists --bare | ${grep} --quiet 'mailman'; then
        local mm_passwd="$(_setupRandomPassword)"
        local list_owner="tuleap-admin@$server_name"
        /usr/lib/mailman/bin/newlist -q mailman "$list_owner" "$mm_passwd" > /dev/null
        _logPassword "Mailman siteadmin: ${mm_passwd}"

            ${cat} << EOF >> /etc/aliases

## mailman mailing list
mailman:              "|/usr/lib/mailman/mail/mailman post mailman"
mailman-admin:        "|/usr/lib/mailman/mail/mailman admin mailman"
mailman-bounces:      "|/usr/lib/mailman/mail/mailman bounces mailman"
mailman-confirm:      "|/usr/lib/mailman/mail/mailman confirm mailman"
mailman-join:         "|/usr/lib/mailman/mail/mailman join mailman"
mailman-leave:        "|/usr/lib/mailman/mail/mailman leave mailman"
mailman-owner:        "|/usr/lib/mailman/mail/mailman owner mailman"
mailman-request:      "|/usr/lib/mailman/mail/mailman request mailman"
mailman-subscribe:    "|/usr/lib/mailman/mail/mailman subscribe mailman"
mailman-unsubscribe:  "|/usr/lib/mailman/mail/mailman unsubscribe mailman"

EOF

        echo "$list_owner" | /usr/lib/mailman/bin/add_members -r - mailman
        has_mailman_been_configured="true"
    fi

    if [ ${has_mailman_been_configured} = "true" ]; then
        ${tuleapcfg} systemctl enable "mailman-tuleap.service"
        ${tuleapcfg} systemctl restart "mailman-tuleap.service"
        ${tuleapcfg} systemctl restart "httpd.service"
        ${tuleapcfg} systemctl enable "httpd.service"
        _infoMessage "mailman-tuleap has been configured"
    else
        _infoMessage "mailman-tuleap is already configured"
    fi
}

function _configureNSSMySQL() {
    local has_libnss_mysql_been_configured="false"
    local libnss_conf="/etc/libnss-mysql.cfg"
    local libnss_conf_root="/etc/libnss-mysql-root.cfg"

    local dbauthuser_username=$(${awk} --field-separator="'" '/^\$sys_dbauth_user/ {print $2}' ${tuleap_conf}/local.inc)
    local dbauthuser_password=$(${awk} --field-separator="'" '/^\$sys_dbauth_passwd/ {print $2}' ${tuleap_conf}/local.inc)

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
