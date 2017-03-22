<?php
/**
 * Copyright (c) Enalean, 2016-2017. All Rights Reserved.
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

namespace Tuleap\BotMattermost\Bot;

class Bot {

    private $id;
    private $name;
    private $webhook_url;
    private $avatar_url;

    public function __construct(
        $id,
        $name,
        $webhook_url,
        $avatar_url
    ) {
        $this->id             = $id;
        $this->name           = $name;
        $this->webhook_url    = $webhook_url;
        $this->avatar_url     = $avatar_url;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getWebhookUrl()
    {
        return $this->webhook_url;
    }

    public function getAvatarUrl()
    {
        return $this->avatar_url;
    }

    public function getChannelsNamesInOneRow()
    {
        return ''; // no more channels linked to the bot
    }
}
