<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

class NotificationsSettingsUpdateController implements DispatchableWithRequest
{
    use NotificationsSettingsControllerCommon;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var UserNotificationSettingsDAO
     */
    private $user_notification_settings_dao;

    public function __construct(
        TrackerFactory $tracker_factory,
        UserManager $user_manager,
        UserNotificationSettingsDAO $user_notification_settings_dao
    ) {
        $this->tracker_factory                = $tracker_factory;
        $this->user_manager                   = $user_manager;
        $this->user_notification_settings_dao = $user_notification_settings_dao;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $tracker      = $this->getTrackerFromTrackerID($this->tracker_factory, $variables['id']);
        $current_user = $request->getCurrentUser();

        $this->getCSRFToken($tracker)->check();

        if ($tracker->userIsAdmin($current_user)) {
            $this->processAdminUpdate($request, $tracker);
        } else if ($current_user->isLoggedIn() && $tracker->userCanView($current_user)) {
            $this->processRegularUserUpdate($request, $layout, $tracker, $current_user);
        }

        $layout->redirect($this->getURL($tracker));
    }

    private function processAdminUpdate(HTTPRequest $request, \Tracker $tracker)
    {
        $this->getDateReminderManager($tracker)->processReminderUpdate($request);
        $this->getNotificationsManager($this->user_manager, $tracker)->processUpdate($request);
    }

    private function processRegularUserUpdate(HTTPRequest $request, BaseLayout $layout, \Tracker $tracker, \PFUser $user)
    {
        switch ($request->get('notification-mode')) {
            case 'no-notification':
                $this->user_notification_settings_dao->enableNoNotificationAtAllMode($user->getId(), $tracker->getId());
                break;
            case 'no-global-notification':
                $this->user_notification_settings_dao->enableNoGlobalNotificationMode($user->getId(), $tracker->getId());
                break;
            case 'notify-me-on-create':
                $this->user_notification_settings_dao->enableNotifyOnArtifactCreationMode($user->getId(), $tracker->getId());
                break;
            case 'notify-me-every-change':
                $this->user_notification_settings_dao->enableNotifyOnEveryChangeMode($user->getId(), $tracker->getId());
                break;
        }

        $layout->addFeedback(
            \Feedback::INFO,
            dgettext('tuleap-tracker', 'Your notification settings have been successfully updated')
        );
    }
}
