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

use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermost\Bot\Bot;
use Tuleap\BotMattermost\Exception\BotNotFoundException;
use Tuleap\BotMattermost\Exception\ChannelsNotFoundException;
use Tuleap\BotMattermost\Exception\CannotCreateBotException;

class BotGitFactory
{

    private $dao;
    private $bot_factory;

    public function __construct(BotGitDao $bot_git_dao, BotFactory $bot_factory)
    {
        $this->dao         = $bot_git_dao;
        $this->bot_factory = $bot_factory;
    }

    public function saveBotsAssignements($repository_id, array $bots_ids)
    {
        if (! $this->dao->updateBotsAssignements($repository_id, $bots_ids)) {
            throw new CannotCreateBotException();
        }
    }

    /**
     * @return Bot[]
     */
    public function getBots()
    {
        $bots_git = array();
        try {
            $bots = $this->bot_factory->getBots();
            foreach ($bots as $bot) {
                $repository_ids = $this->dao->searchRepositoryIdByBotId($bot->getId());
                $bots_git[] = new BotGit($bot, $repository_ids);
            }

            return $bots_git;
        } catch (ChannelsNotFoundException $e) {
            throw $e;
        } catch (BotNotFoundException $e) {
            throw $e;
        }
    }

    public function getBotsByRepositoryId($repository_id)
    {
        $dar = $this->dao->searchBotsByRepositoryId($repository_id);
        foreach ($dar as $row) {
            $id = $row['id'];
            try {
                $bots[] = $this->bot_factory->getBotById($id);
            } catch (ChannelsNotFoundException $e) {
                throw $e;
            }
        }

        return $bots;
    }
}
