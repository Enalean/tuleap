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

use DataAccessObject;

class BotAgileDashboardDao extends DataAccessObject
{

    const SYSTEM_EVENT_INTERVAL = '00:30:00';

    public function searchTime($bot_id, $project_id)
    {
        $bot_id     = $this->da->escapeInt($bot_id);
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT send_time
                FROM plugin_botmattermost_agiledashboard
                WHERE bot_id = $bot_id
                    AND project_id = $project_id";

        return $this->retrieveFirstRow($sql);
    }

    public function updateBotsAgileDashboard(array $bots_ids, $project_id, $send_time)
    {
        $this->getDa()->startTransaction();

        if (!$this->deleteBotsForProject($project_id)) {
            $this->getDa()->rollback();

            return false;
        }

        if (count($bots_ids) > 0) {
            if (!$this->addBotsAgileDashboard($bots_ids, $project_id, $send_time)) {
                $this->getDa()->rollback();

                return false;
            }
        }

        return $this->getDa()->commit();
    }

    private function addBotsAgileDashboard(
        array $bots_ids,
        $project_id,
        $send_time
    ) {
        $sql = "INSERT INTO plugin_botmattermost_agiledashboard(bot_id, project_id, send_time)
                VALUES ";

        foreach ($bots_ids as $bot_id) {
            $sql .= $this->addBotAgileDashboardSql($bot_id, $project_id, $send_time).',';
        }

        return $this->update(trim($sql, ','));
    }

    private function addBotAgileDashboardSql($bot_id, $project_id, $send_time)
    {
        $bot_id     = $this->da->escapeInt($bot_id);
        $project_id = $this->da->escapeInt($project_id);
        $send_time  = $this->da->quoteSmart($send_time);

        return "($bot_id, $project_id, $send_time)";
    }

    public function deleteBotsForProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "DELETE FROM plugin_botmattermost_agiledashboard
                WHERE project_id = $project_id";

        return $this->update($sql);
    }

    public function searchAgileDashboardBotsForSummary()
    {
        $interval   = $this->da->quoteSmart(self::SYSTEM_EVENT_INTERVAL);

        $sql = "SELECT *
                FROM plugin_botmattermost_bot
                INNER JOIN plugin_botmattermost_agiledashboard
                ON plugin_botmattermost_bot.id = plugin_botmattermost_agiledashboard.bot_id
                WHERE SUBTIME(CURRENT_TIME(), $interval) < send_time
                  AND send_time <= CURRENT_TIME()";

        return $this->retrieve($sql);
    }
}
