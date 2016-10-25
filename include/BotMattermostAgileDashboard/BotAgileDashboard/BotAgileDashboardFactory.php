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

use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermost\Exception\BotNotFoundException;
use Tuleap\BotMattermost\Exception\ChannelsNotFoundException;
use Tuleap\BotMattermost\Exception\CannotCreateBotException;
use Tuleap\BotMattermostAgileDashboard\BotAgileDashboard\BotAgileDashboard;
use Tuleap\BotMattermostAgileDashboard\BotAgileDashboard\BotAgileDashboardDao;

class BotAgileDashboardFactory
{
    private $dao;
    private $bot_factory;

    public function __construct(
        BotAgileDashboardDao $dao,
        BotFactory $bot_factory
    ) {
        $this->dao         = $dao;
        $this->bot_factory = $bot_factory;
    }

    public function saveBotsAgileDashboard(
        array $bots_ids,
        $project_id,
        $send_time
    ) {
        if (! $this->dao->updateBotsAgileDashboard($bots_ids, $project_id, $send_time)) {
            throw new CannotCreateBotException();
        }
    }

    /**
     * @return BotAgileDashboard[]
     */
    public function getBotsForTimePeriod($project_id)
    {
        $bots_agiledashboard = array();

        $bots = $this->bot_factory->getBots();
        foreach ($bots as $bot) {
            $row = $this->dao->searchTime($bot->getId(), $project_id);
            $bots_agiledashboard[] = new BotAgileDashboard(
                $bot,
                $project_id,
                $row['send_time']
            );
        }

        return $bots_agiledashboard;
    }
}