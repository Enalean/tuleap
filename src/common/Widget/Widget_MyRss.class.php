<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2017-Present. All rights reserved
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuleap\Dashboard\User\UserDashboardController;

require_once('Widget_Rss.class.php');

/**
* Widget_MyRss
*
* Personal rss reader
*/
class Widget_MyRss extends Widget_Rss
{
    public function __construct()
    {
        parent::__construct(
            'myrss',
            UserManager::instance()->getCurrentUser()->getId(),
            UserDashboardController::LEGACY_DASHBOARD_TYPE
        );
    }

    public function getDescription()
    {
        return $GLOBALS['Language']->getText('widget_description_rss', 'description');
    }
}
