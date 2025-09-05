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

declare(strict_types=1);

namespace Tuleap\Tracker\Notifications\Settings;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use TemplateRenderer;
use TrackerFactory;
use TrackerManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Tracker;

final readonly class NotificationsUserSettingsDisplayController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public function __construct(
        private TemplateRenderer $template_renderer,
        private TrackerFactory $tracker_factory,
        private TrackerManager $tracker_manager,
        private UserNotificationSettingsRetriever $user_notification_settings_retriever,
        private NoGlobalNotificationLabelBuilder $global_notification_label_builder,
    ) {
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $tracker = $this->tracker_factory->getTrackerById($variables['id']);
        if ($tracker === null) {
            throw new NotFoundException(dgettext('tuleap-tracker', 'That tracker does not exist.'));
        }

        $current_user = $request->getCurrentUser();
        if ($current_user->isAnonymous()) {
            $layout->addFeedback(Feedback::ERROR, dgettext('tuleap-tracker', 'Access denied. You don\'t have permissions to perform this action.'));
            $layout->redirect(TRACKER_BASE_URL . '/?tracker=' . urlencode((string) $tracker->getId()));
        }

        $user_notification_settings = $this->user_notification_settings_retriever->getUserNotificationSettings(
            $current_user,
            $tracker
        );

        $current_uri = $request->getFromServer('REQUEST_URI');

        $title = dgettext('tuleap-tracker', 'Email Notifications Settings');
        $tracker->displayHeader(
            $this->tracker_manager,
            $title,
            [['title' => $title, 'url' => $current_uri]],
        );

        $this->template_renderer->renderToPage(
            'user-notification-settings',
            new UserNotificationSettingsPresenter(
                new CSRFSynchronizerToken($current_uri),
                $user_notification_settings,
                $tracker->getNotificationsLevel() === Tracker::NOTIFICATIONS_LEVEL_DISABLED,
                $this->global_notification_label_builder->getInputLabel()
            )
        );

        $this->tracker_manager->displayFooter($tracker->getProject());
    }
}
