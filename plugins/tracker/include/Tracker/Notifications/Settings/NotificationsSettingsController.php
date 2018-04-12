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
use Tracker_DateReminderManager;
use Tracker_NotificationsManager;
use TrackerFactory;
use TrackerManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Notifications\CollectionOfUgroupToBeNotifiedPresenterBuilder;
use Tuleap\Tracker\Notifications\CollectionOfUserToBeNotifiedPresenterBuilder;
use Tuleap\Tracker\Notifications\GlobalNotificationsAddressesBuilder;
use Tuleap\Tracker\Notifications\NotificationListBuilder;
use Tuleap\Tracker\Notifications\UgroupsToNotifyDao;
use Tuleap\Tracker\Notifications\UsersToNotifyDao;
use UGroupDao;
use UGroupManager;
use UserManager;

class NotificationsSettingsController implements DispatchableWithRequest
{
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var TrackerManager
     */
    private $tracker_manager;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(TrackerFactory $tracker_factory, TrackerManager $tracker_manager, UserManager $user_manager)
    {
        $this->tracker_factory = $tracker_factory;
        $this->tracker_manager = $tracker_manager;
        $this->user_manager    = $user_manager;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $tracker = $this->tracker_factory->getTrackerById($variables['id']);
        if ($tracker === null) {
            throw new NotFoundException(dgettext('tuleap-tracker', 'That tracker does not exist.'));
        }

        $current_user = $request->getCurrentUser();
        if (! $current_user->isLoggedIn()) {
            $layout->addFeedback(\Feedback::ERROR, $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
            $layout->redirect(TRACKER_BASE_URL . '/?tracker=' . urlencode($tracker->getId()));
        }
        if ($request->isPost() && ! $tracker->userIsAdmin($current_user)) {
            $layout->addFeedback(\Feedback::ERROR, $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
            $layout->redirect(TRACKER_BASE_URL.'/?tracker='. $tracker->getId());
        }

        $tracker_date_reminder_manager = $this->getDateReminderManager($tracker);

        if ($request->get('func') === 'display_reminder_form') {
            print $tracker_date_reminder_manager->getDateReminderRenderer()->getNewDateReminderForm();
            return;
        }

        $tracker_date_reminder_manager->processReminder($this->tracker_manager, $request, $current_user);
        $this->getNotificationsManager($tracker)->process($this->tracker_manager, $request, $current_user);
    }

    /**
     * @return Tracker_DateReminderManager
     */
    private function getDateReminderManager(\Tracker $tracker)
    {
        return new Tracker_DateReminderManager($tracker);
    }

    /**
     * @return Tracker_NotificationsManager
     */
    private function getNotificationsManager(\Tracker $tracker)
    {
        $user_to_notify_dao        = new UsersToNotifyDao();
        $ugroup_to_notify_dao      = new UgroupsToNotifyDao();
        $notification_list_builder = new NotificationListBuilder(
            new UGroupDao(),
            new CollectionOfUserToBeNotifiedPresenterBuilder($user_to_notify_dao),
            new CollectionOfUgroupToBeNotifiedPresenterBuilder($ugroup_to_notify_dao)
        );
        return new Tracker_NotificationsManager(
            $tracker,
            $notification_list_builder,
            $user_to_notify_dao,
            $ugroup_to_notify_dao,
            new GlobalNotificationsAddressesBuilder(),
            $this->user_manager,
            new UGroupManager()
        );
    }
}
