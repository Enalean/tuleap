<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2017. All rights reserved
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

use Tuleap\Dashboard\Project\ProjectDashboardController;

require_once('Widget_ImageViewer.class.php');

/**
* Widget_ProjectImageViewer
*
* Project image viewer
*
*/
class Widget_ProjectImageViewer extends Widget_ImageViewer
{
    public function __construct()
    {
        $request = HTTPRequest::instance();
        parent::__construct(
            'projectimageviewer',
            $request->get('group_id'),
            ProjectDashboardController::LEGACY_DASHBOARD_TYPE
        );
    }
}
