<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All Rights Reserved.
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

use Tuleap\Widget\WidgetFactory;

/**
* WidgetLayoutManager
*
* Manage layouts for users, groups and homepage
*/

class WidgetLayoutManager
{
    const OWNER_TYPE_HOME  = 'h';

    /**
     * @var WidgetFactory
     */
    private $widget_factory;

    /**
     * @var EventManager
     */
    private $event_manager;


    public function __construct()
    {
        $this->event_manager  = EventManager::instance();
        $this->widget_factory = new WidgetFactory(
            UserManager::instance(),
            new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
            $this->event_manager
        );
    }
}
