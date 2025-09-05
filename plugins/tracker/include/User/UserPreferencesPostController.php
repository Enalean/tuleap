<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\User;

use Codendi_Mail_Interface;
use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\CSRFSynchronizerTokenInterface;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\User\Account\DisplayNotificationsController;
use Tuleap\User\ProvideCurrentUser;

final readonly class UserPreferencesPostController implements DispatchableWithRequest
{
    public const URL = TRACKER_BASE_URL . '/notifications/user';

    public function __construct(
        private ProvideCurrentUser $current_user_provider,
        private CSRFSynchronizerTokenInterface $csrf_synchronizer_token,
        private NotificationOnOwnActionSaver $own_action_saver,
        private NotificationOnAllUpdatesSaver $all_updates_saver,
    ) {
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $this->csrf_synchronizer_token->check(DisplayNotificationsController::URL, $request);

        $current_user = $this->current_user_provider->getCurrentUser();
        if ($current_user->isAnonymous()) {
            throw new NotFoundException();
        }

        $has_changed = $this->own_action_saver->save(
            new NotificationOnOwnActionPreference($request->get('user_notifications_own_actions_tracker') === '1'),
            $current_user
        );
        $has_changed = $this->all_updates_saver->save(
            new NotificationOnAllUpdatesPreference($request->get('user_notifications_all_updates_tracker') === '1'),
            $current_user
        ) || $has_changed;

        if ($request->exist('email_format')) {
            $format_email = $request->get('email_format') === Codendi_Mail_Interface::FORMAT_HTML ? Codendi_Mail_Interface::FORMAT_HTML : Codendi_Mail_Interface::FORMAT_TEXT;
            if ($format_email !== $current_user->getPreference(Codendi_Mail_Interface::PREF_FORMAT)) {
                $current_user->setPreference(Codendi_Mail_Interface::PREF_FORMAT, $format_email);
                $has_changed = true;
            }
        }

        if ($has_changed) {
            $layout->addFeedback(Feedback::INFO, dgettext('tuleap-tracker', 'Notifications preferences successfully updated'));
        } else {
            $layout->addFeedback(Feedback::INFO, dgettext('tuleap-tracker', 'Nothing has changed'));
        }

        $layout->redirect(DisplayNotificationsController::URL);
    }

    public static function getCSRFToken(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(self::URL);
    }
}
