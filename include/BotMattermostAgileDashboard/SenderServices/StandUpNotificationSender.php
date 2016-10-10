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

namespace Tuleap\BotMattermostAgileDashboard\SenderServices;

use Tuleap\BotMattermost\Exception\BotNotFoundException;
use Tuleap\BotMattermost\SenderServices\Sender;
use Tuleap\BotMattermostAgileDashboard\BotAgileDashboard\BotAgileDashboardFactory;

class StandUpNotificationSender
{
    private $bot_agiledashboard_factory;
    private $sender;

    public function __construct(BotAgileDashboardFactory $bot_agiledashboard_factory, Sender $sender)
    {
        $this->bot_agiledashboard_factory = $bot_agiledashboard_factory;
        $this->sender                     = $sender;
    }

    public function send()
    {
        try {
            $bots = $this->bot_agiledashboard_factory->getBotsForSummary();
            $this->sender->pushNotifications($bots);
        } catch (BotNotFoundException $e) {
            // Nothing to do
        }
    }
}