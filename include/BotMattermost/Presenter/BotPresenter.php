<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\BotMattermost\Presenter;

use Tuleap\BotMattermost\Bot\Bot;

/**
 * @psalm-immutable
 */
class BotPresenter
{
    private function __construct(
        public int $bot_id,
        public string $bot_name,
        public string $webhook_url,
        public string $avatar_url,
        public ?int $project_id,
        public string $edit_bot_url,
        public string $delete_bot_url,
    ) {
    }

    public static function buildFromBot(Bot $bot): self
    {
        $bot_id         = $bot->getId();
        $bot_project_id = $bot->getProjectId();

        if ($bot_project_id === null) {
            $edit_bot_url   = '?action=edit_bot';
            $delete_bot_url = '?action=delete_bot';
        } else {
            $edit_bot_url   = "/plugins/botmattermost/bot/$bot_id/edit";
            $delete_bot_url = "/plugins/botmattermost/bot/$bot_id/delete";
        }

        return new self(
            $bot_id,
            $bot->getName(),
            $bot->getWebhookUrl(),
            $bot->getAvatarUrl(),
            $bot_project_id,
            $edit_bot_url,
            $delete_bot_url
        );
    }
}
