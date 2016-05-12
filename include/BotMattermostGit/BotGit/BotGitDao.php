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

use DataAccessObject;

class BotGitDao extends DataAccessObject
{

    public function searchBotsByRepositoryId($repository_id)
    {
        $repository_id  = $this->da->escapeInt($repository_id);

        $sql = "SELECT *
                FROM plugin_botmattermost_bot
                LEFT JOIN plugin_botmattermost_git
                ON plugin_botmattermost_bot.id = plugin_botmattermost_git.bot_id
                WHERE repository_id = $repository_id";

        return $this->retrieve($sql);
    }

    public function searchRepositoryIdByBotId($bot_id)
    {
        $id = $this->da->escapeInt($bot_id);

        $sql = "SELECT repository_id AS id
                FROM plugin_botmattermost_git
                WHERE bot_id = $id";

        return $this->retrieveIds($sql);
    }

    public function updateBotsAssignements($repository_id, array $bots_ids)
    {
        $this->da->startTransaction();

        $dar = $this->deleteBotsForRepository($repository_id);
        if ($dar === false) {
            $this->da->rollback();
            return false;
        }


        if (count($bots_ids) > 0) {
            $dar = $this->addBotsGit($repository_id, $bots_ids);
            if ($dar === false) {
                $this->da->rollback();
                return false;
            }
        }

        return $this->da->commit();
    }

    private function addBotsGit($repository_id, array $bots_ids)
    {
        $bots = array();
        foreach($bots_ids as $bot_id) {
            $bots[] = $this->getBotGitValueSql($repository_id, $bot_id);
        }

        $sql = "INSERT INTO plugin_botmattermost_git(repository_id, bot_id)
                VALUES ".implode(',', $bots);

        return $this->update($sql);
    }

    public function getBotGitValueSql($repository_id, $bot_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $bot_id        = $this->da->escapeInt($bot_id);

        return "($repository_id, $bot_id)";
    }

    private function deleteBotsForRepository($repository_id)
    {
        $id = $this->da->escapeInt($repository_id);

        $sql = "DELETE FROM plugin_botmattermost_git
                WHERE repository_id = $id";

        return $this->update($sql);
    }
}
