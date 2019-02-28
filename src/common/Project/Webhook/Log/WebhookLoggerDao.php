<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Project\Webhook\Log;

class WebhookLoggerDao extends \DataAccessObject
{
    /**
     * @return bool
     */
    public function save($webhook_id, $created_on, $status)
    {
        $webhook_id = $this->da->escapeInt($webhook_id);
        $created_on = $this->da->escapeInt($created_on);
        $status     = $this->da->quoteSmart($status);

        $this->startTransaction();
        $sql_update     = "INSERT INTO project_webhook_log(webhook_id, created_on, status)
                VALUES ($webhook_id, $created_on, $status)";
        $has_been_saved = $this->update($sql_update);
        if (! $has_been_saved) {
            $this->rollBack();
            return false;
        }

        $sql_clean_logs = "DELETE FROM project_webhook_log WHERE webhook_id = $webhook_id AND created_on <= (
              SELECT created_on FROM (
                SELECT created_on FROM project_webhook_log WHERE webhook_id = $webhook_id ORDER BY created_on DESC LIMIT 1 OFFSET 10
              ) oldest_entry_to_keep
        )";
        $has_logs_been_cleaned = $this->update($sql_clean_logs);

        if (! $has_logs_been_cleaned) {
            $this->rollBack();
            return false;
        }

        $this->commit();
        return true;
    }

    /**
     * @return \DataAccessResult|false
     */
    public function searchLogsByWebhookId($webhook_id)
    {
        $webhook_id = $this->da->escapeInt($webhook_id);

        $sql = "SELECT * FROM project_webhook_log WHERE webhook_id = $webhook_id ORDER BY created_on DESC";

        return $this->retrieve($sql);
    }
}
