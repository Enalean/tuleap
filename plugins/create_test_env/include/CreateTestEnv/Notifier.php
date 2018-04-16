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

class Notifier
{
    /**
     * @var NotificationBotDao
     */
    private $notification_bot_dao;

    public function __construct(NotificationBotDao $notification_bot_dao)
    {
        $this->notification_bot_dao = $notification_bot_dao;
    }

    /**
     * @param $text
     */
    public function notify($text)
    {
        $bot_id = (int) $this->notification_bot_dao->get();
        if ($bot_id > 0) {
            (new MattermostNotifier())->notify($bot_id, $text);
        }
    }
}
