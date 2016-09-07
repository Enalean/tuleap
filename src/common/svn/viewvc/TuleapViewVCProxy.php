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

namespace Tuleap\SvnCore\ViewVC;

require_once('viewvc_utils.php');
require_once('www/svn/svn_utils.php');

use HTTPRequest;
use Project;
use Valid_WhiteList;

class TuleapViewVCProxy implements ViewVCProxy
{
    public function displayContent(Project $project, HTTPRequest $request)
    {
        $vRootType = new Valid_WhiteList('roottype', array('svn'));
        $vRootType->setErrorMessage($GLOBALS['Language']->getText('svn_viewvc', 'bad_roottype'));
        $vRootType->required();
        if (! $request->valid($vRootType)) {
            svn_header(array('title' => $GLOBALS['Language']->getText('svn_utils', 'browse_tree')));
            site_footer(array());
            return;
        }

        if (! svn_utils_check_access(
            user_getname(),
            $project->getSVNRootPath(),
            viewvc_utils_getfile("/svn/viewvc.php")
        )) {
            exit_error(
                $GLOBALS['Language']->getText('svn_viewvc', 'access_denied'),
                $GLOBALS['Language']->getText(
                    'svn_viewvc',
                    'acc_den_comment',
                    session_make_url("/project/memberlist.php?group_id=" . urlencode($project->getID()))
                )
            );
        }

        viewvc_utils_track_browsing($project->getID(), 'svn');

        $display_header_footer = viewvc_utils_display_header();

        if ($display_header_footer) {
            $prefix_title = '';
            if ($path = viewvc_utils_getfile("/svn/viewvc.php")) {
                $prefix_title = basename($path) . ' - ';
            }
            $GLOBALS['HTML']->addStylesheet('/viewvc-static/styles.css');
            svn_header(array(
                'title' => $prefix_title . $GLOBALS['Language']->getText('svn_utils', 'browse_tree'),
                'path' => '/' . urlencode(viewvc_utils_getfile("/svn/viewvc.php"))
            ));
        }

        viewvc_utils_passcommand();

        if ($display_header_footer) {
            site_footer(array());
        }
    }
}
