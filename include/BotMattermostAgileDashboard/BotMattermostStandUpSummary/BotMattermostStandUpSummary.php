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

namespace Tuleap\BotMattermostAgileDashboard\BotMattermostStandUpSummary;

use Tuleap\BotMattermost\Bot\Bot;

class BotMattermostStandUpSummary
{
    private $bot;
    private $channels;
    private $project_id;
    private $send_time;

    public function __construct(
        Bot $bot,
        array $channels,
        $project_id,
        $send_time
    ) {
        $this->bot        = $bot;
        $this->channels   = $channels;
        $this->project_id = $project_id;
        $this->send_time  = $send_time;
    }

    public function getBot()
    {
        return $this->bot;
    }

    private function isAssigned()
    {
        return isset($this->send_time);
    }

    public function toArray()
    {
        return array(
            'id'             => $this->bot->getId(),
            'name'           => $this->bot->getName(),
            'webhook_url'    => $this->bot->getWebhookUrl(),
            'avatar'         => $this->bot->getAvatarUrl(),
            'channels'       => implode(', ', $this->channels),
            'project_id'     => $this->project_id,
            'send_time'      => date("H:i", strtotime($this->send_time)),
            'is_assigned'    => $this->isAssigned()
        );
    }

    /**
     * @return array
     */
    public function getChannels()
    {
        return $this->channels;
    }

    public function getSendTime()
    {
        return $this->send_time;
    }

    public function getProjectId()
    {
        return $this->project_id;
    }
}