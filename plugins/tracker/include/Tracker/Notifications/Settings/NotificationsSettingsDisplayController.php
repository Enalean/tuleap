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

class NotificationsSettingsDisplayController implements DispatchableWithRequest
{
    use NotificationsSettingsControllerCommon;

    /**
     * @var TemplateRenderer
     */
    private $template_renderer;
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
    /**
     * @var UserNotificationSettingsRetriever
     */
    private $user_notification_settings_retriever;

    public function __construct(
        TemplateRenderer $template_renderer,
        TrackerFactory $tracker_factory,
        TrackerManager $tracker_manager,
        UserManager $user_manager,
        UserNotificationSettingsRetriever $user_notification_settings_retriever
    ) {
        $this->template_renderer                    = $template_renderer;
        $this->tracker_factory                      = $tracker_factory;
        $this->tracker_manager                      = $tracker_manager;
        $this->user_manager                         = $user_manager;
        $this->user_notification_settings_retriever = $user_notification_settings_retriever;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $tracker = $this->getTrackerFromTrackerID($this->tracker_factory, $variables['id']);

        $current_user = $request->getCurrentUser();
        if (! $current_user->isLoggedIn()) {
            $layout->addFeedback(\Feedback::ERROR, $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
            $layout->redirect(TRACKER_BASE_URL . '/?tracker=' . urlencode($tracker->getId()));
        }

        $csrf_token = $this->getCSRFToken($tracker);

        if ($request->get('func') === 'display_reminder_form') {
            print $this->getDateReminderManager($tracker)->getDateReminderRenderer()->getNewDateReminderForm($csrf_token);
            return;
        }

        $tracker->displayAdminItemHeader($this->tracker_manager, 'editnotifications');
        if ($tracker->userIsAdmin($current_user)) {
            $this->getNotificationsManager($this->user_manager, $tracker)->displayTrackerAdministratorSettings($request, $csrf_token);
        } else {
            $user_notification_settings = $this->user_notification_settings_retriever->getUserNotificationSettings(
                $current_user,
                $tracker
            );
            $this->template_renderer->renderToPage(
                'user-notification-settings',
                new UserNotificationSettingsPresenter($csrf_token, $user_notification_settings)
            );
        }
        $tracker->displayFooter($this->tracker_manager);
    }
}
