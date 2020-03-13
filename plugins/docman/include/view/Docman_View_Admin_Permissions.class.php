<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Docman\View\DocmanViewURLBuilder;

class Docman_View_Admin_Permissions extends Docman_View_Extra
{

    public function _title($params)
    {
        echo '<h2>' . $this->_getTitle($params) . ' - ' . dgettext('tuleap-docman', 'Manage Permissions') . '</h2>';
    }
    public function _content($params)
    {
        $content  = '';

        //{{{ Explanations
        $content .= '<div>';
        $content .= dgettext('tuleap-docman', 'Please select user groups that can administrate the document manager, in addition of project administrators:');
        $content .= '</div>';
        echo $content;
        //}}}

        $postUrl = DocmanViewURLBuilder::buildUrl($params['default_url'], array(
            'action' => 'admin_set_permissions'
        ));
        permission_display_selection_form("PLUGIN_DOCMAN_ADMIN", $params['group_id'], $params['group_id'], $postUrl);
    }
}
