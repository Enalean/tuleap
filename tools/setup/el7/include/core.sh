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
        _serviceEnable "mailman-tuleap.service"
        _serviceRestart "mailman-tuleap.service"
        _serviceReload "httpd.service"
        _infoMessage "mailman-tuleap has been configured"
    else
        _infoMessage "mailman-tuleap is already configured"
    fi
}

function _configureApache() {
    local has_apache_been_configured="false"

    if ! ${grep} --quiet "^User.*${tuleap_unix_user}" ${httpd_conf}; then
        ${sed} --in-place "s@^User.*@User ${tuleap_unix_user}@g" ${httpd_conf}
        has_apache_been_configured="true"
    fi

    if ! ${grep} --quiet "^Group.*${tuleap_unix_user}" ${httpd_conf}; then
        ${sed} --in-place "s@^Group.*@Group ${tuleap_unix_user}@g" ${httpd_conf}
        has_apache_been_configured="true"
    fi

    if ! ${grep} --quiet "Listen.*:8080" ${httpd_conf}; then
        _phpConfigureModule "apache"
        has_apache_been_configured="true"
    fi

    if [ -f ${httpd_conf_ssl} ] && \
        ${grep} --quiet "SSLEngine.*on" ${httpd_conf_ssl}; then
        ${sed} --in-place "s@^SSLEngine.*on@SSLEngine off@g" ${httpd_conf_ssl}
        has_apache_been_configured="true"
    fi

    if [ -f ${httpd_conf_ssl} ] && \
        ${grep} --quiet "^Listen" ${httpd_conf_ssl}; then
        ${sed} --in-place "s@^Listen@#Listen@g" ${httpd_conf_ssl}
        has_apache_been_configured="true"
    fi

    if [ ${has_apache_been_configured} = "true" ]; then
        _serviceRestart "httpd.service"
        _infoMessage "Apache has been configured"
    else
        _infoMessage "Apache is already configured"
    fi
}