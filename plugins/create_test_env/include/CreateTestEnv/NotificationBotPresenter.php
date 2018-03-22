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

use Tuleap\BotMattermost\Bot\Bot;

class NotificationBotPresenter
{
    public $bot_list = [];

    /**
     * @param Bot[] $all_bots
     * @param int   $selected_bot_id
     */
    public function __construct(array $all_bots, $selected_bot_id)
    {
        foreach ($all_bots as $bot) {
            $this->bot_list[] = [
                'id'          => $bot->getId(),
                'name'        => $bot->getName(),
                'is_selected' => $bot->getId() == $selected_bot_id,
            ];
        }
    }
}
