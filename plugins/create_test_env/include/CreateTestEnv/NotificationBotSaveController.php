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
 *
 */

namespace Tuleap\CreateTestEnv;

use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;

class NotificationBotSaveController implements DispatchableWithRequest
{
    private $plugin_path;
    /**
     * @var NotificationBotDao
     */
    private $notification_bot_dao;

    public function __construct(NotificationBotDao $notification_bot_dao, $plugin_path)
    {
        $this->notification_bot_dao = $notification_bot_dao;
        $this->plugin_path          = $plugin_path;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array $variables
     * @return void
     */
    public function process(\HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            $layout->addFeedback(\Feedback::ERROR, dgettext('tuleap-create_test_env', 'You should be site administrator to access this page'));
            $layout->redirect('/');
            return;
        }

        $bot_id = (int) $request->getValidated('bot', 'uint', 0);
        if ($bot_id === 0) {
            $this->notification_bot_dao->remove();
            $layout->addFeedback(\Feedback::INFO, dgettext('tuleap-create_test_env', 'Mattermost notifications removed'));
        } else {
            $this->notification_bot_dao->save($bot_id);
            $layout->addFeedback(\Feedback::INFO, dgettext('tuleap-create_test_env', 'Mattermost notifications added'));
        }

        $layout->redirect($this->plugin_path . '/notification-bot');
    }
}
