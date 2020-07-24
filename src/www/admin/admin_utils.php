<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

function site_admin_header($params)
{
    global $HTML, $Language;
    global $feedback;
    $HTML->header($params);
    echo html_feedback_top($feedback);
}

function site_admin_footer($vals = 0)
{
    global $HTML;
    echo html_feedback_bottom($GLOBALS['feedback']);
    $HTML->footer([]);
}

function site_admin_warnings(Tuleap\Admin\Homepage\NbUsersByStatus $nb_users_by_status)
{
    $warnings = [];
    EventManager::instance()->processEvent(
        Event::GET_SITEADMIN_WARNINGS,
        [
            'nb_users_by_status' => $nb_users_by_status,
            'warnings'           => &$warnings
        ]
    );

    if (! ForgeConfig::get('disable_forge_upgrade_warnings')) {
        $forgeupgrade_config = new ForgeUpgradeConfig(new System_Command());
        $forgeupgrade_config->loadDefaults();
        if (! $forgeupgrade_config->isSystemUpToDate()) {
            $warnings[] = '<div class="tlp-alert-warning alert alert-warning alert-block">' . $GLOBALS['Language']->getText('admin_main', 'forgeupgrade') . '</div>';
        }
    }

    return implode('', $warnings);
}
