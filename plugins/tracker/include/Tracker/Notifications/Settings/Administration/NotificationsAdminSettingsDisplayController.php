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
use TemplateRenderer;
use TemplateRendererFactory;
use TrackerFactory;
use TrackerManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use UserManager;

class NotificationsAdminSettingsDisplayController implements DispatchableWithRequest
{
    use NotificationsAdminSettingsControllerCommon;

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

    public function __construct(
        TrackerFactory $tracker_factory,
        TrackerManager $tracker_manager,
        UserManager $user_manager
    ) {
        $this->tracker_factory = $tracker_factory;
        $this->tracker_manager = $tracker_manager;
        $this->user_manager    = $user_manager;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $tracker = $this->getTrackerFromTrackerID($this->tracker_factory, $variables['id']);

        $current_user = $request->getCurrentUser();
        if (! $tracker->userIsAdmin($current_user)) {
            $layout->addFeedback(\Feedback::ERROR, $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
            $layout->redirect(TRACKER_BASE_URL . '/?tracker=' . urlencode($tracker->getId()));
        }

        $csrf_token = $this->getCSRFToken($tracker);

        if ($request->get('func') === 'display_reminder_form') {
            print $this->getDateReminderManager($tracker)->getDateReminderRenderer()->getNewDateReminderForm($csrf_token);
            return;
        }

        $tracker->displayAdminItemHeader($this->tracker_manager, 'editnotifications', $GLOBALS['Language']->getText('plugin_tracker_include_type', 'mail_notif'));
        $this->getNotificationsManager($this->user_manager, $tracker)->displayTrackerAdministratorSettings($request, $csrf_token);
        $tracker->displayFooter($this->tracker_manager);
    }
}
