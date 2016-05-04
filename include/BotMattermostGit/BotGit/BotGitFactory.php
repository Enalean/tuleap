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
use Tuleap\BotMattermost\Exception\CannotAccessDataBaseException;
use Tuleap\BotMattermost\Exception\CannotCreateBotException;

class BotGitFactory extends BotFactory
{

    private $dao;

    public function __construct(BotGitDao $dao)
    {
        $this->dao = $dao;
    }

    public function save($repository_id, $bot_id)
    {
        $id = $this->dao->addBotGit($repository_id, $bot_id);
        if (!$id) {
            throw new CannotCreateBotException($GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_alert_error'));
        }
    }

    public function saveBotsAssignements($repository_id, array $bots_ids)
    {
        if (! $this->dao->updateBotsAssignements($repository_id, $bots_ids)) {
            throw new CannotCreateBotException($GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_alert_error'));
        }
    }

    /**
     * @return Bot[]
     */
    public function getBots()
    {
        $dar = $this->dao->searchBotsWithRepositoryId();
        if ($dar === false) {
            throw new CannotAccessDataBaseException($GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_alert_error'));
        }
        $bots = array();
        foreach ($dar as $row) {
            $bots[] = new BotGit(
                $row['id'],
                $row['name'],
                $row['webhook_url'],
                $row['repository_id']
            );
        }
        return $bots;
    }

    /**
     * @return Bot
     */
    public function getBotById($id)
    {
        $row = $this->dao->searchBotById($id);
        if ($row === false){
            throw new CannotAccessDataBaseException($GLOBALS['Language']->getText('plugin_botmattermost','configuration_alert_error'));
        }
        return $bot = new Bot(
            $row['id'],
            $row['name'],
            $row['webhook_url']
        );
    }

    public function getBotsIdsByRepositoryId($repository_id)
    {
        $dar = $this->dao->searchBotsIdsByRepositoryId($repository_id);
        foreach ($dar as $row) {
            $bots_ids[] = $row['bot_id'];
        }

        return $bots_ids;
    }
}