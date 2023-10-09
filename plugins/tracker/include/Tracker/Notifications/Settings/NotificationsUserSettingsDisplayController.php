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
use TemplateRenderer;
use Tracker;
use TrackerFactory;
use TrackerManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

class NotificationsUserSettingsDisplayController implements DispatchableWithRequest
{
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
     * @var UserNotificationSettingsRetriever
     */
    private $user_notification_settings_retriever;

    public function __construct(
        TemplateRenderer $template_renderer,
        TrackerFactory $tracker_factory,
        TrackerManager $tracker_manager,
        UserNotificationSettingsRetriever $user_notification_settings_retriever,
    ) {
        $this->template_renderer                    = $template_renderer;
        $this->tracker_factory                      = $tracker_factory;
        $this->tracker_manager                      = $tracker_manager;
        $this->user_notification_settings_retriever = $user_notification_settings_retriever;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $tracker = $this->tracker_factory->getTrackerById($variables['id']);
        if ($tracker === null) {
            throw new NotFoundException(dgettext('tuleap-tracker', 'That tracker does not exist.'));
        }

        $current_user = $request->getCurrentUser();
        if ($current_user->isAnonymous()) {
            $layout->addFeedback(\Feedback::ERROR, dgettext('tuleap-tracker', 'Access denied. You don\'t have permissions to perform this action.'));
            $layout->redirect(TRACKER_BASE_URL . '/?tracker=' . urlencode($tracker->getId()));
        }

        $user_notification_settings = $this->user_notification_settings_retriever->getUserNotificationSettings(
            $current_user,
            $tracker
        );

        $current_uri = $request->getFromServer('REQUEST_URI');

        $tracker->displayHeader(
            $this->tracker_manager,
            dgettext('tuleap-tracker', 'Email Notifications Settings'),
            [
                ['title' => dgettext('tuleap-tracker', 'Email Notifications Settings'), 'url' => $current_uri],
            ],
        );

        $this->template_renderer->renderToPage(
            'user-notification-settings',
            new UserNotificationSettingsPresenter(
                new \CSRFSynchronizerToken($current_uri),
                $user_notification_settings,
                $tracker->getNotificationsLevel() === Tracker::NOTIFICATIONS_LEVEL_DISABLED
            )
        );
    }
}
