<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\CVS\ViewVC;

require_once('viewvc_utils.php');
require_once('www/cvs/commit_utils.php');

use HTTPRequest;
use Project;

class TuleapViewVCProxy implements ViewVCProxy
{

    public function displayContent(Project $project, HTTPRequest $request)
    {
        if (! check_cvs_access(user_getname(), $project->getUnixName(), viewvc_utils_getfile("/cvs/viewvc.php"))) {
            exit_error(
                $GLOBALS['Language']->getText('cvs_viewvc', 'error_noaccess'),
                $GLOBALS['Language']->getText(
                    'cvs_viewvc',
                    'error_noaccess_msg',
                    session_make_url("/project/memberlist.php?group_id=" . urlencode($project->getID()))
                )
            );
        }

        viewvc_utils_track_browsing($project->getID(), 'cvs');

        $display_header_footer = viewvc_utils_display_header();

        if ($display_header_footer) {
            commits_header(array(
                'title'     => $GLOBALS['Language']->getText('cvs_viewvc', 'title'),
                'stylesheet'=> (array('/viewvc-static/styles.css')),
                'group'     => $project->getID()
            ));
        }

        viewvc_utils_passcommand();

        if ($display_header_footer) {
            site_footer(array());
        }
    }
}
