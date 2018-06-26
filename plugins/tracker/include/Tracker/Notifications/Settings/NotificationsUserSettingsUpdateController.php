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
use Tuleap\Request\NotFoundException;

class NotificationsUserSettingsUpdateController implements DispatchableWithRequest
{
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var UserNotificationSettingsDAO
     */
    private $user_notification_settings_dao;

    public function __construct(
        TrackerFactory $tracker_factory,
        UserNotificationSettingsDAO $user_notification_settings_dao
    ) {
        $this->tracker_factory                = $tracker_factory;
        $this->user_notification_settings_dao = $user_notification_settings_dao;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $tracker = $this->tracker_factory->getTrackerById($variables['id']);
        if ($tracker === null) {
            throw new NotFoundException(dgettext('tuleap-tracker', 'That tracker does not exist.'));
        }

        $current_uri = $request->getFromServer('REQUEST_URI');

        (new \CSRFSynchronizerToken($current_uri))->check();

        $current_user = $request->getCurrentUser();

        if ($current_user->isLoggedIn() && $tracker->userCanView($current_user)) {
            $this->processUpdate($request, $layout, $tracker, $current_user);
        }

        $layout->redirect($current_uri);
    }

    private function processUpdate(HTTPRequest $request, BaseLayout $layout, \Tracker $tracker, \PFUser $user)
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
            case 'notify-me-status-change':
                $this->user_notification_settings_dao->enableNotifyOnStatusChangeMode($user->getId(), $tracker->getId());
                break;
        }

        $layout->addFeedback(
            \Feedback::INFO,
            dgettext('tuleap-tracker', 'Your notification settings have been successfully updated')
        );
    }
}
