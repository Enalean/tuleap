<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Docman\View\DocmanViewURLBuilder;

class Docman_View_Admin extends Docman_View_Extra
{

    public function _title($params)
    {
        echo '<h2>' . dgettext('tuleap-docman', 'Documents') . ' - ' . dgettext('tuleap-docman', 'Administration') . '</h2>';
    }
    public function _content($params)
    {
        $html = '';
        $html .= '<h3><a href="' . DocmanViewURLBuilder::buildUrl($params['default_url'], array('action' => 'admin_permissions')) . '">' . dgettext('tuleap-docman', 'Manage Permissions') . '</a></h3>';
        $html .= '<p>' . dgettext('tuleap-docman', 'Define who can administrate the document manager.') . '</p>';

        $html .= '<h3><a href="' . DocmanViewURLBuilder::buildUrl($params['default_url'], array('action' => 'admin_view')) . '">' . dgettext('tuleap-docman', 'Manage Display Preferences') . '</a></h3>';
        $html .= '<p>' . dgettext('tuleap-docman', 'Define the default view for the document manager.') . '</p>';

        $html .= '<h3><a href="' . DocmanViewURLBuilder::buildUrl($params['default_url'], array('action' => 'admin_metadata')) . '">' . dgettext('tuleap-docman', 'Manage Properties') . '</a></h3>';
        $html .= '<p>' . dgettext('tuleap-docman', 'Define the properties of your documents.') . '</p>';

        $html .= '<h3><a href="' . DocmanViewURLBuilder::buildUrl($params['default_url'], array('action' => 'report_settings')) . '">' . dgettext('tuleap-docman', 'Manage Search Report') . '</a></h3>';
        $html .= '<p>' . dgettext('tuleap-docman', 'Modify the scope of the reports.') . '</p>';

        $html .= '<h3><a href="' . DocmanViewURLBuilder::buildUrl($params['default_url'], array('action' => 'admin_obsolete')) . '">' . dgettext('tuleap-docman', 'Manage Obsolete Documents') . '</a></h3>';
        $html .= '<p>' . dgettext('tuleap-docman', 'View and update obsolete documents.') . '</p>';

        $html .= '<h3><a href="' . DocmanViewURLBuilder::buildUrl($params['default_url'], array('action' => 'admin_lock_infos')) . '">' . dgettext('tuleap-docman', 'Locked Documents') . '</a></h3>';
        $html .= '<p>' . dgettext('tuleap-docman', 'List of locked documents.') . '</p>';

        echo $html;
    }
}
