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
    /**
     * @var \ProjectHistoryDao
     */
    private $project_history_dao;

    public function __construct(
        TrackerFactory $tracker_factory,
        UserNotificationSettingsDAO $user_notification_settings_dao,
        \ProjectHistoryDao $project_history_dao
    ) {
        $this->tracker_factory                = $tracker_factory;
        $this->user_notification_settings_dao = $user_notification_settings_dao;
        $this->project_history_dao            = $project_history_dao;
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
        $notification_label = dgettext('plugin-tracker', 'Notify me on all updates of artifacts I\'m involved (assigned, submitter, cc, comment)');
        switch ($request->get('notification-mode')) {
            case 'no-notification':
                $notification_label = dgettext('plugin-tracker', 'No notifications at all');
                $this->user_notification_settings_dao->enableNoNotificationAtAllMode($user->getId(), $tracker->getId());
                break;
            case 'no-global-notification':
                $notification_label = dgettext('plugin-tracker', 'Notify me on all updates of artifacts I\'m involved (assigned, submitter, cc, comment)');
                $this->user_notification_settings_dao->enableNoGlobalNotificationMode($user->getId(), $tracker->getId());
                break;
            case 'notify-me-on-create':
                $notification_label = dgettext('plugin-tracker', 'Notify me when artifacts are created');
                $this->user_notification_settings_dao->enableNotifyOnArtifactCreationMode($user->getId(), $tracker->getId());
                break;
            case 'notify-me-every-change':
                $notification_label = dgettext('plugin-tracker', 'Notify me on every change');
                $this->user_notification_settings_dao->enableNotifyOnEveryChangeMode($user->getId(), $tracker->getId());
                break;
            case 'notify-me-status-change':
                $notification_label = dgettext('plugin-tracker', 'Notify me on status change');
                $this->user_notification_settings_dao->enableNotifyOnStatusChangeMode($user->getId(), $tracker->getId());
                break;
        }

        $this->project_history_dao->groupAddHistory(
            'user_notification_update',
            $notification_label,
            $tracker->getGroupId(),
            [$user->getName(), $user->getId(), $tracker->getName()]
        );

        $layout->addFeedback(
            \Feedback::INFO,
            dgettext('tuleap-tracker', 'Your notification settings have been successfully updated')
        );
    }
}
