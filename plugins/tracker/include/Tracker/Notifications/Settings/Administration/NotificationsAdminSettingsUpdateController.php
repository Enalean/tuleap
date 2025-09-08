<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Notifications\Settings;

use HTTPRequest;
use TrackerFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use UserManager;

class NotificationsAdminSettingsUpdateController implements DispatchableWithRequest
{
    use NotificationsAdminSettingsControllerCommon;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(TrackerFactory $tracker_factory, UserManager $user_manager)
    {
        $this->tracker_factory = $tracker_factory;
        $this->user_manager    = $user_manager;
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $tracker      = $this->getTrackerFromTrackerID($this->tracker_factory, $variables['id']);
        $current_user = $request->getCurrentUser();

        $this->getCSRFToken($tracker)->check();

        if ($tracker->userIsAdmin($current_user)) {
            $this->getDateReminderManager($tracker)->processReminderUpdate($request);
            $this->getNotificationsManager($this->user_manager, $tracker)->processUpdate($request);
        }

        $layout->redirect($this->getURL($tracker));
    }
}
