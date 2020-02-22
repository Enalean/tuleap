<?php
/**
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

namespace Tuleap\Dashboard\User;

use EventManager;
use PFUser;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Widget\MyWelcomeMessage;

class AtUserCreationDefaultWidgetsCreator
{
    /**
     * @var DashboardWidgetDao
     */
    private $dao;
    /**
     * @var EventManager
     */
    private $event_manager;

    public const DEFAULT_WIDGETS_FOR_NEW_USER = 'default_widgets_for_new_user';

    public function __construct(DashboardWidgetDao $dao, EventManager $event_manager)
    {
        $this->dao            = $dao;
        $this->event_manager = $event_manager;
    }

    public function createDefaultDashboard(PFUser $user)
    {
        $widgets = array(
            MyWelcomeMessage::NAME,
            'myprojects',
        );

        $this->event_manager->processEvent(self::DEFAULT_WIDGETS_FOR_NEW_USER, array('widgets' => &$widgets));

        $this->dao->createDefaultDashboardForUser($user->getId(), $widgets);
    }
}
