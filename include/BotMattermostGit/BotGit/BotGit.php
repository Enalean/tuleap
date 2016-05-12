<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\BotMattermostGit\BotGit;

use Tuleap\BotMattermost\Bot\Bot;

class BotGit
{
    private $bot;
    private $repository_ids;

    public function __construct(
        Bot $bot,
        array $repository_ids
    ) {
        $this->bot            = $bot;
        $this->repository_ids = $repository_ids;
    }

    public function getBot()
    {
        $this->bot;
    }

    public function toArray($repository_id)
    {
        return array(
            'id'             => $this->bot->getId(),
            'name'           => $this->bot->getName(),
            'webhook_url'    => $this->bot->getWebhookUrl(),
            'avatar'         => $this->bot->getAvatarUrl(),
            'channels_names' => $this->bot->getChannelsNamesInOneRow(),
            'is_assigned'    => in_array($repository_id, $this->repository_ids)
        );
    }
}