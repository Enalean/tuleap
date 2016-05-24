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

namespace Tuleap\BotMattermostAgileDashboard\BotAgileDashboard;

use Tuleap\BotMattermost\Bot\Bot;

class BotAgileDashboard
{
    private $bot;
    private $project_id;
    private $start_time;
    private $duration;

    public function __construct(
        Bot $bot,
        $project_id,
        $start_time,
        $duration
    ) {
        $this->bot        = $bot;
        $this->project_id = $project_id;
        $this->start_time = $start_time;
        $this->duration   = $duration;
    }

    public function getBot()
    {
        $this->bot;
    }

    private function isAssigned()
    {
        return $this->start_time && $this->duration;
    }

    public function toArray()
    {
        return array(
            'id'             => $this->bot->getId(),
            'name'           => $this->bot->getName(),
            'webhook_url'    => $this->bot->getWebhookUrl(),
            'avatar'         => $this->bot->getAvatarUrl(),
            'channels_names' => $this->bot->getChannelsNamesInOneRow(),
            'project_id'     => $this->project_id,
            'start_time'     => $this->start_time,
            'duration'       => $this->duration,
            'is_assigned'    => $this->isAssigned()
        );
    }

    public function getStartTime()
    {
        return $this->start_time;
    }

    public function getDuration()
    {
        return $this->duration;
    }
}